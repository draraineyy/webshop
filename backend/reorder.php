<?php

declare(strict_types=1);

session_start();

if(!isset($_SESSION['customer_id'])){
    header('Location: ../frontend/viewlogin.php');
    exit;
}

header('Content-Type: text/html; charset=utf8');
require_once("../db.php");
require_once("models/cart.php");

//CSRF prüfen
if(empty($_POST['csrf'])||empty($_SESSION['csrf'])||!hash_equals($_SESSION['csrf'], $_POST['csrf'])){
    http_response_code(400);
    die("Ungültiger Request.");
}

$customerId=(int)$_SESSION['customer_id'];
$orderIdSrc=(int)($_POST['order_id']??0);
if($orderIdSrc <=0){
    http_response_code(400);
    die("Ungültige Bestell-ID.");
}

// Quelle-Bestellung prüfen: gehört sie dem Benutzer?
$stmt=$pdo->prepare("SELECT id, order_number, delivery FROM orders WHERE id=? AND customer_id=?");
$stmt->execute([$orderIdSrc, $customerId]);
$srcOrder=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$srcOrder){
    http_response_code(403);
    die("Bestellung nicht gefunden oder nicht berechtigt.");
}
$delivery=$srcOrder['delivery']??'dhl';

// Position der Quelle
$stmtPos=$pdo->prepare("SELECT product_id, quantity FROM order_position WHERE order_id = ?");
$stmtPos->execute([$orderIdSrc]);
$srcPositions=$stmtPos->fetchAll(PDO::FETCH_ASSOC);
if(!$srcPositions){
    http_response_code(400);
    die("Quelle_Bestellung hat keine Positionen.");
}

// Versandkosten-Funktion (wie bei dir)
function shippingCost(string $method): float {
    if ($method === 'express') return 16.90; // DHL 6.90 + 10
    if ($method === 'dpd')     return 11.90; // DHL 6.90 + 5
    return 6.90;                            // DHL Standard
}

$orderNumber = uniqid("ORDER-");

// Transaktion: neue Bestellung anlegen
$pdo->beginTransaction();
try {
    // neue Bestellung
    $stmt = $pdo->prepare("INSERT INTO orders (customer_id, order_number, date, delivery, `sum`) VALUES (?, ?, NOW(), ?, 0.00)");
    $stmt->execute([$customerId, $orderNumber, $delivery]);
    $newOrderId = (int)$pdo->lastInsertId();

    $cart = new Cart($pdo, $customerId); // für getDiscount()
    $orderSum = 0.0;

    // jede Position neu berechnen mit aktuellen Preisen
    $stmtPrice = $pdo->prepare("SELECT price, stock, title FROM products WHERE id = ? FOR UPDATE");
    $stmtIns   = $pdo->prepare("INSERT INTO order_position (order_id, product_id, quantity, price, discount) VALUES (?, ?, ?, ?, ?)");
    $stmtStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($srcPositions as $pos) {
        $productId = (int)$pos['product_id'];
        $qty       = max(1, (int)$pos['quantity']);

        // aktuellen Preis & Lager prüfen
        $stmtPrice->execute([$productId]);
        $row = $stmtPrice->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new RuntimeException("Produkt #{$productId} nicht gefunden.");
        }
        $basePrice = (float)$row['price'];
        $stock     = (int)$row['stock'];
        if ($stock < $qty) {
            throw new RuntimeException("Nicht genug Lagerbestand für Produkt #{$productId} (verfügbar: {$stock}, benötigt: {$qty}).");
        }

        $discount  = (float)$cart->getDiscount($qty); // z. B. 0.10
        $unitPrice = round($basePrice * (1.0 - max(0.0, min(1.0, $discount))), 2);
        $lineTotal = $unitPrice * $qty;

        // Position speichern
        $stmtIns->execute([$newOrderId, $productId, $qty, $unitPrice, $discount]);
        // Lager reduzieren
        $stmtStock->execute([$qty, $productId]);

        $orderSum += $lineTotal;
    }

    // Versandkosten hinzufügen
    $shippingCost = shippingCost($delivery);
    $orderSum = round($orderSum + $shippingCost, 2);

    // Summe setzen
    $pdo->prepare("UPDATE orders SET `sum` = ? WHERE id = ?")->execute([$orderSum, $newOrderId]);

    // Punkte gutschreiben (50 Punkte für Einkauf)
    $pdo->prepare("INSERT INTO points (customer_id, activity, points, `date`) VALUES (?, 'Einkauf', 50, NOW())")->execute([$customerId]);

    $pdo->commit();

    // Rechnungsmail senden
    // (Positionen für Mail laden – mit Titel)
    $stmtMailPos = $pdo->prepare("
        SELECT op.product_id, op.quantity, op.price, op.discount, p.title
        FROM order_position op
        JOIN products p ON p.id = op.product_id
        WHERE op.order_id = ?
    ");
    $stmtMailPos->execute([$newOrderId]);
    $mailPositions = $stmtMailPos->fetchAll(PDO::FETCH_ASSOC);

    // Kundendaten
    $stmtCust = $pdo->prepare("SELECT email, name FROM customer WHERE id = ?");
    $stmtCust->execute([$customerId]);
    $cust = $stmtCust->fetch(PDO::FETCH_ASSOC);
    $customerEmail = $cust['email'] ?? null;
    $customerName  = $cust['name']  ?? '';

    if ($customerEmail) {
        require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/src/SMTP.php';
        require_once __DIR__ . '/PHPMailer/src/Exception.php';

        $fmt = fn(float $n) => number_format($n, 2, ',', '.');
        $rowsHtml = '';
        foreach ($mailPositions as $mp) {
            $title = htmlspecialchars($mp['title'] ?? ('Produkt #'.$mp['product_id']), ENT_QUOTES, 'UTF-8');
            $qty   = (int)$mp['quantity'];
            $unit  = $fmt((float)$mp['price']);
            $disc  = (float)$mp['discount'] * 100;
            $rowsHtml .= <<<HTML
            <tr>
                <td class="cell name">{$title}</td>
                <td class="cell qty">{$qty}</td>
                <td class="cell price">{$unit}</td>
                <td class="cell disc">{$disc}%</td>
            </tr>
            HTML;
        }

        $safeName  = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');
        $safeOrder = htmlspecialchars($orderNumber,   ENT_QUOTES, 'UTF-8');
        $shippingStr = $fmt($shippingCost);
        $sumStr      = $fmt($orderSum);

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'postershop.info@gmail.com';
            $mail->Password   = 'veyo lyyy twbl rhal';
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->isHTML(true);
            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';

            $mail->setFrom($mail->Username, 'PosterShop');
            $mail->addAddress($customerEmail, $customerName ?: $customerEmail);

            // Optional: Logo einbetten
            $logoPath = __DIR__ . '/../images/logo.png';
            if (is_readable($logoPath)) {
                $mail->addEmbeddedImage($logoPath, 'shoplogo', 'logo.png', 'base64', 'image/png');
            }

            $mail->Subject = "Bestellbestätigung {$safeOrder} - PosterShop";
            $mail->Body = <<<HTML
            <!DOCTYPE html>
            <html lang="de">
            <head>
                <meta charset="UTF-8">
                <style>
                    body{margin:0;padding:0;background:#f6f7fb;color:#222;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
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
                    .btn{display:inline-block;background:#0ea5e9;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;font-weight:600;margin-top:12px}
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="card">
                        <div class="header">
                            cid:shoplogo
                            <div class="brand">PosterShop</div>
                        </div>
                        <div class="content">
                            <h1>Bestellbestätigung</h1>
                            <p>Hallo {$safeName},</p>
                            <p>Vielen Dank für deine Bestellung beim <strong>PosterShop</strong>.</p>
                            <div class="meta">
                                <p><strong>Bestellnummer:</strong> {$safeOrder}</p>
                                <p><strong>Versandart:</strong> {$delivery}</p>
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
                                <div class="label">Gesamtsumme:</div>
                                <div class="value"><strong>{$sumStr} €</strong></div>
                            </div>
                        </div>
                        <div class="footer">
                            <p>&copy; PosterShop</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            HTML;

            $mail->AltBody = "Bestellbestätigung {$orderNumber}\nVersand: {$delivery}\nVersandkosten: {$shippingStr} €\nGesamt: {$sumStr} €";
            $mail->send();
        } catch (\PHPMailer\PHPMailer\Exception $me) {
            error_log("reorder: Mailer Error: " . $me->getMessage());
        }
    }

    // Redirect auf Danke-Seite mit neuer Bestellnummer
    header("Location: ../frontend/thankyou.php?order=" . urlencode($orderNumber));
    exit;

} catch (\Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("reorder error: " . $e->getMessage());
    http_response_code(500);
    die("Erneute Bestellung fehlgeschlagen: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}