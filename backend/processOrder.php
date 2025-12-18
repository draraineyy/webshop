<?php
session_start(); // Session starten, damit $_SESSION verfügbar ist

require_once("../db.php");
require_once("models/cart.php");

// 1) Eingeloggt?
if (!isset($_SESSION['customer_id'])) {
    die("Bitte einloggen, um zu bestellen.");
}

// 2) Datenschutz bestätigt?
if (!isset($_POST['privacy'])) {
    die("Bitte Datenschutzbestimmungen akzeptieren!");
}

// 3) Versandkosten berechnen
function shippingCost($method) {
    if ($method === 'express') return 16.90; // DHL 6.90 + 10
    if ($method === 'dpd') return 11.90;     // DHL 6.90 + 5
    return 6.90; // DHL
}
$delivery = $_POST['shipping'] ?? 'dhl';
$shippingCost = shippingCost($delivery);

// 4) Warenkorb laden
$cart = new Cart($pdo, $_SESSION['customer_id']);
$items = $cart->getItems();
if (empty($items)) die("Warenkorb ist leer.");

// Produktpreise laden
$productIds = array_keys($items);
$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$stmt = $pdo->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
$stmt->execute($productIds);
$prices = [];
foreach ($stmt->fetchAll() as $row) {
    $prices[$row['id']] = $row['price'];
}

// 5) Bestellung speichern
$orderNumber = uniqid("ORDER-");
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("INSERT INTO orders (customer_id, order_number, date, delivery, sum)
                           VALUES (?, ?, NOW(), ?, 0.00)");
    $stmt->execute([$_SESSION['customer_id'], $orderNumber, $delivery]);
    $orderId = $pdo->lastInsertId();

    $orderSum = 0.0;
    //für jede Position der Bestellung durchlaufen
    foreach ($items as $productId => $quantity) {
        $basePrice = $prices[$productId];
        $discount = $cart->getDiscount($quantity);
        $unitPrice = round($basePrice * (1 - $discount), 2);
        $positionTotal = $unitPrice * $quantity;
        $orderSum += $positionTotal;
        //Position speichern
        $stmtPos = $pdo->prepare("INSERT INTO order_position (order_id, product_id, quantity, price, discount)
                                  VALUES (?, ?, ?, ?, ?)");
        $stmtPos->execute([$orderId, $productId, $quantity, $unitPrice, $discount]);

        //Lagerbestand reduzieren
        $stmtStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmtStock->execute([$quantity, $productId]);

    }

    // --- Gutscheincode prüfen ---
    $appliedDiscount = 0.0;
    if (!empty($_POST['coupons'])) {
        foreach ($_POST['coupons'] as $code) {
            if ($code === "HIGH5") {
                $appliedDiscount += 5.00;
            } elseif ($code === "NEWYEAR") {
                $appliedDiscount += 2.00;
            }
        }
    }

// Endsumme inkl. Versand minus Rabatt
$orderSum = round($orderSum + $shippingCost - $appliedDiscount, 2);
$pdo->prepare("UPDATE orders SET sum=? WHERE id=?")->execute([$orderSum, $orderId]);
// Endsumme inkl. Versand minus Rabatt
//$orderSum = round($orderSum + $shippingCost - $appliedDiscount, 2);
//$pdo->prepare("UPDATE orders SET sum=? WHERE id=?")->execute([$orderSum, $orderId]);

//$orderSum = round($orderSum + $shippingCost - $appliedDiscount, 2);
//$pdo->prepare("UPDATE orders SET sum=? WHERE id=?")->execute([$orderSum, $orderId]);

$cart->clear();
$pdo->commit();

// Bestätigungsemail versenden
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Kundendaten laden
$stmtCust=$pdo->prepare("SELECT email, name FROM customer WHERE id=?");
$stmtCust->execute([$_SESSION['customer_id']]);
$customer=$stmtCust->fetch(PDO::FETCH_ASSOC);
$customerEmail=$customer['email']??null;
$customerName=$customer['name']??'';

// Positionen für Mail laden
$stmtPos=$pdo->prepare("SELECT op.product_id, op.quantity, op.price, op.discount, p.title
    FROM order_position op
    JOIN products p ON p.id=op.product_id
    WHERE op.order_id=?"
);
$stmtPos->execute([$orderId]);
$positions=$stmtPos->fetchAll(PDO::FETCH_ASSOC);

//Versandlabel erzeugen
$shippingLabel=($delivery==='express')?'DHL Express'
    :(($delivery==='dpd')?'DPD'
    :'DHL Standard');

// Werte für Anzeige formatieren
$fmt=fn(float $n)=> number_format($n, 2, ',', '.');
$shippingStr=$fmt($shippingCost);
$sumStr=$fmt($orderSum);

// Row-HTML erzeugen
$rowsHtml='';
foreach ($positions as $pos){
    $title=htmlspecialchars($pos['title']??('Produkt #' .$pos['product_id']), ENT_QUOTES, 'UTF-8');
    $qty=(int)$pos['quantity'];
    $unit=$fmt((float)$pos['price']);
    $disc=(float)$pos['discount']*100;
    $rowsHtml .= <<<HTML
    <tr>
        <td class="cell name">{$title}</td>
        <td class="cell qty">{$qty}</td>
        <td class="cell price">{$unit}</td>
        <td class="cell disc">{$disc}</td>
    </tr>
    HTML;
}

if($customerEmail){
    $mail=new PHPMailer(true);
    try{
        //SMTP
        $mail->isSMTP();
        $mail->Host='smtp.gmail.com';
        $mail->SMTPAuth=true;
        $mail->Username='postershop.info@gmail.com';
        $mail->Password='veyo lyyy twbl rhal';
        $mail->SMTPSecure=PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port=587;

        //HTML & Charset
        $mail->isHTML(true);
        $mail->CharSet='UTF-8';
        $mail->Encoding='base64';

        //Absender/Empfänger
        $mail->setFrom($mail->Username, 'PosterShop');
        $mail->addAddress($customerEmail, $customerName);

        //Optional Logo
        $logoPath=__DIR__ .'/../images/logo.png';
        if(is_readable($logoPath)){
            $mail->addEmbeddedImage($logoPath, 'shoplogo', 'logo.png', 'base64', 'image/png');
        }

        $safeName=htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');
        $safeOrder=htmlSpecialchars($orderNumber, ENT_QUOTES, 'UTF-8');
        $mail->Subject="Bestellbestätigung {$safeOrder} - PosterShop";

        //HTML
        $mail->Body=<<<HTML
        <!DOCTYPE html>
        <html lang="de">
            <head>
                <meta charset="UTF-8">
                <style>
                    body{margin:0;padding:0;background:#f6f7fb;color:#222;font-family:system-ui, -apple-system,Segoe UI,Roboto,Arial,sans-serif}
                    .container{max-width:680px;margin:0 auto;padding:24px}
                    .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 2px 6px rgba(0,0,0,.06);overflow:hidden}
                    .header{display:flex;align-items:center;gap:12px;padding:16px 20px;border-bottom:1px solid #eef0f5}
                    .brand{font-weight:700;font-size:18px}
                    .content{padding:20px}
                    h1{font-size:20px;margin:0 0 8px}
                    p{margin:0 0 12px;line-height:1.5}
                    .meta{background:#fafafa;border:1px solid #eef0f5;border-radius:10px;padding:12px;margin:12px 0}
                    .table{width:100%;border-collapse:collapse;margin-top:12px}
                    .table th{font-weight:600;text-align:left;padding:10px;border-bottom:1px solid #e8e8ef;font-size:14px;color:#555}
                    .table td{padding:10px;border-bottom:1px solid #f0f1f6;font-size:14px}
                    .cell.qty,.cell.price,.cell.disc{text-align:right}
                    .total{display:flex;justify-content:flex-end;margin-top:16px;font-size:16px}
                    .total .label{margin-right:12px;font-weight:700}
                    .footer{padding:16px 20px;border-top:1px solid #eef0f5;color:#6b7280;font-size:12px}
                    .btn{display:inline-block;background:#0ea5e9;color:#fff;text-decoration:none;padding:10px 14px;;border-radius:8px;font-weight:600;margin-top:12px}
                </style>
            </head>

            <body>
                <div class="container">
                    <div class="card">
                        <div class="header">
                            cid:shoplogo
                            <div class="brand">
                                PosterShop
                            </div>
                        </div>
                        <div class="content">
                            <h1>Bestellbestätigung</h1>
                            <p>Hallo {$safeName},</p>
                            <p>Vielen Dank für deine Bestellung beim <strong>PosterShop</strong>.</p>
                            <div class="meta">
                                <p><strong>Bestellnummer:</strong> {$safeOrder}</p>
                                <p><strong>Versandart:</strong> {$shippingLabel}</p>
                                <p><strong>Versandkosten:</strong> {$shippingStr} €</p>
                            </div>
                            <table class="table" role="presentation">
                                <thead>
                                    <tr>
                                        <th>Artikel</th>
                                        <th style="text-align:right;">Menge</th>
                                        <th style="text-align:right;">Preis/Stk</th>
                                        <th style="text-align:right;">Rabatt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {$rowsHtml}
                                </tbody>
                            </table>
                            <div class="total">
                                <div class="label">
                                    Gesamtsumme:
                                </div>
                                <div class="value">
                                    <strong>{$sumStr} €</strong>
                                </div>
                            </div>
                            https://example.com/orders/{$safeOrder}Bestellung anzeigen</a>
                        </div>
                        <div class="footer">
                            <p>&copy; PosterShop</p>
                        </div>
                    </div>
                </div>
            </body>
        </html>
        HTML;

        $mail->AltBody="Bestellbestätigung {$orderNumber}\nVersand: {$shippingLabel}\nVersandkosten: {$shippingStr} €\nGesamt: {$sumStr} €";
        $mail->send();
    } catch (Exception $e){
        error_log("processOrder: Mailer Error: " .$mail->ErrorInfo);
    }
}

try{
    $mail->isSMTP();
    $mail->Host=getenv
}

// Weiterleitung auf Danke-Seite
header("Location: ../frontend/thankyou.php?order=" . urlencode($orderNumber));
exit;

    
} catch (Exception $e) {
    $pdo->rollBack();
    die("Bestellung fehlgeschlagen: " . $e->getMessage());
}