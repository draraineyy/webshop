<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
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
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity  = max(1, (int)($_POST['quantity'] ?? 1));
        if($productId <=0){
            http_response_code(400);
            echo json_encode(["error" => "invalid_product_id"]);
            exit;
        }

        $cart->add($productId, $quantity);
        /*if ($productId>0) {
            $cart->add($productId, $quantity);

            // Für Gäste: Session speichern
            $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $quantity;

            // Für eingeloggte User: DB speichern*/
        if ($customerId) {
            $cart->saveToDb();
        }
            
        
        echo json_encode(["success" => true, "items" => enrichItems($cart)]);
        break;

    case "remove":
        $productId = (int)($_POST['product_id'] ?? 0);
        if($productId <=0){
            http_response_code(400);
            echo json_encode(['error' => 'invalid_product_id']);
            exit;
        }

        $cart->remove($productId);

        if(!$customerId){
            //Gäste: aus Session entfernen
            unset($_SESSION['cart'][$productId]);
        }
        else{
            // für eingeloggte User
            $cart->saveToDb();
        }
        echo json_encode(["success" => true, "items" => enrichItems($cart)]);
        break;

    case "update":
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity  = max(1, (int)($_POST['quantity'] ?? 1));

        if($productId <=0){
            http_response_code(400);
            echo json_encode(['error'=> 'invalid_product_id']);
            exit;
        }

        $cart->update($productId, $quantity);

        if(!$customerId){
            // Gäste: Session aktualisieren
            $_SESSION['cart'][$productId] = $quantity;
        }
        else{
            // eingeloggte:
            $cart->saveToDb();
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