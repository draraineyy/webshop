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

    foreach ($items as $productId => $quantity) {
        $basePrice = $prices[$productId];
        $discount = $cart->getDiscount($quantity);
        $unitPrice = round($basePrice * (1 - $discount), 2);
        $positionTotal = $unitPrice * $quantity;
        $orderSum += $positionTotal;

        $stmtPos = $pdo->prepare("INSERT INTO order_position (order_id, product_id, quantity, price, discount)
                                  VALUES (?, ?, ?, ?, ?)");
        $stmtPos->execute([$orderId, $productId, $quantity, $unitPrice, $discount]);
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
        $logoPath=__DIR__ .'/assets/logo.png
    }
}

try{
    $mail->isSMTP();
    $mail->Host=getenv
}

$smtpHost='smtp.gmail.com';
$smtpUser='postershop.info@gmail.com';
$smtpPass='veyo lyyy twbl rhal';
$smtpPort=587;



    header("Location: ../frontend/thankyou.php?order=" . urlencode($orderNumber));
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    die("Bestellung fehlgeschlagen: " . $e->getMessage());
}