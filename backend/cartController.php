<?php
session_start();
require_once("../db.php");          // DB-Verbindung
require_once("../models/Cart.php"); // dein Model

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
            $cart->saveToDb(); // persistieren, falls User eingeloggt
        }
        echo json_encode(["success" => true, "items" => $cart->getItems()]);
        break;

    case "remove":
        $productId = $_POST['product_id'] ?? null;
        if ($productId) {
            $cart->remove($productId);
            $cart->saveToDb();
        }
        echo json_encode(["success" => true, "items" => $cart->getItems()]);
        break;

    case "update":
        $productId = $_POST['product_id'] ?? null;
        $quantity  = $_POST['quantity'] ?? 1;
        if ($productId) {
            $cart->update($productId, $quantity);
            $cart->saveToDb();
        }
        echo json_encode(["success" => true, "items" => $cart->getItems()]);
        break;

    case "get":
        echo json_encode(["items" => $cart->getItems()]);
        break;

    default:
        echo json_encode(["error" => "Unknown action"]);
}