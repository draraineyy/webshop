<?php
session_start();
require_once("../db.php");          // DB-Verbindung
require_once("models/cart.php");    // dein Model

// Customer-ID aus Session holen (falls eingeloggt)
$customerId = $_SESSION['customer_id'] ?? null;

// Cart-Objekt erstellen
$cart = new Cart($pdo, $customerId);

// Aktion aus Request lesen
$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case "add":
        $productId = $_POST['product_id'] ?? null;
        $quantity  = $_POST['quantity'] ?? 1;
        if ($productId) {
            $cart->add($productId, $quantity);

            // Für Gäste: Session speichern
            $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $quantity;

            // Für eingeloggte User: DB speichern
            if ($customerId) {
                $cart->saveToDb();
            }
        }
            
        
        echo json_encode(["success" => true, "items" => enrichItems($cart)]);
        break;

    case "remove":
        $productId = $_POST['product_id'] ?? null;
        if ($productId) {
            $cart->remove($productId);
            // Gäste: Session löschen
            unset($_SESSION['cart'][$productId]);

            //für eingeloggte User
            if ($customerId) {
                $cart->saveToDb();
            }
        
        }
        echo json_encode(["success" => true, "items" => enrichItems($cart)]);
        break;

    case "update":
        $productId = $_POST['product_id'] ?? null;
        $quantity  = $_POST['quantity'] ?? 1;
        if ($productId) {
            $cart->update($productId, $quantity);

             // Gäste: Session aktualisieren
            $_SESSION['cart'][$productId] = $quantity;
            //eingeloggte:
            if ($customerId) {
                $cart->saveToDb();

            }

        }
        echo json_encode(["success" => true, "items" => enrichItems($cart)]);
        break;

    case "get":
        // Für Gäste: Session-Warenkorb zurückgeben
        if (!$customerId && isset($_SESSION['cart'])) {
            $items = $_SESSION['cart'];
            $result = [];
            foreach ($items as $productId => $quantity) {
                $result[$productId] = [
                    "quantity" => $quantity,
                    "discount" => $cart->getDiscount($quantity)
                ];
            }
            echo json_encode(["items" => $result]);
        } else {
            echo json_encode(["items" => enrichItems($cart)]);
        }
        break;


    case "total": //auf preis bezogen
        $total = $cart->getTotal();
        echo json_encode(["total" => $total]);
        break;

    case "count":
    $count = $cart->getItemCount(); // summiert alle Mengen im Warenkorb
    echo json_encode(["count" => $count]);
    break;

    case "price":
    $productId = $_GET['product_id'] ?? $_POST['product_id'] ?? null;
    if ($productId) {
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id=?");
        $stmt->execute([$productId]);
        $price = $stmt->fetchColumn();
        echo json_encode(["price" => round($price, 2)]);
    } else {
        echo json_encode(["error" => "Keine Produkt-ID angegeben"]);
    }
    break;


    default:
        echo json_encode(["error" => "Ungültige Aktion"]);

}

// Items mit Rabatt anreichern
function enrichItems($cart) {
    $items = $cart->getItems();
    $result = [];
    foreach ($items as $productId => $quantity) {
        $result[$productId] = [
            "quantity" => $quantity,
            "discount" => $cart->getDiscount($quantity)
        ];
    }
    return $result;
}