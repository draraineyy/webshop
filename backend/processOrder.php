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
$orderSum = round($orderSum + $shippingCost - $appliedDiscount, 2);
$pdo->prepare("UPDATE orders SET sum=? WHERE id=?")->execute([$orderSum, $orderId]);

    $orderSum = round($orderSum + $shippingCost - $appliedDiscount, 2);
    $pdo->prepare("UPDATE orders SET sum=? WHERE id=?")->execute([$orderSum, $orderId]);

    $cart->clear();
    $pdo->commit();

    header("Location: ../frontend/thankyou.php?order=" . urlencode($orderNumber));
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    die("Bestellung fehlgeschlagen: " . $e->getMessage());
}