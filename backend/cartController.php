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

    case "list":
        if(!$customerId){
            $sessItems=$_SESSION['cart']??[];
            if(empty($sessItems)){
                echo json_encode([]);
                break;
            }
            //Preise/Titel aus DB holen
            $ids=array_map('intval', array_keys($sessItems));
            $placeholders=implode(',', array_fill(0, count($ids),'?'));
            $stmt=$pdo->prepare("SELECT id, title, price FROM products WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
            $byId=[];
            foreach($rows as$r){
                $byId[(int)$r['id']]=$r;
            }
            $result=[];
            foreach($sessItems as $productId => $qty){
                $p=$byId[(int)$productId]??null;
                if(!$p)continue;
                $discount=$cart->getDiscount((int)$qty);
                $unit=round((float)$p['price']*(1-(float)$discount), 2);
                $result[]=[
                    "product_id"=>(int)$productId,
                    "title"=>(string)$p['title'],
                    "quantity"=>(int)$qty,
                    "discount"=>(float)$discount,
                    "unit_price"=>(float)$unit,
                    "position_sum"=>(float)round($unit*(int)$qty, 2)
                ];
            }
            echo json_encode($result);
            break;
        }
        //Eingeloggte Nutzer: aus offenem Warenkorb laden
        $stmt=$pdo->prepare("
            SELECT cp.product_id, cp.quantity, p.title, p.price
            FROM cart c
            JOIN cart_position cp ON cp.cart_id=c.id
            JOIN products p ON p.id=cp.product_id
            WHERE c.customer_id=? AND c.status='open'
            ORDER BY cp.id DESC
        ");
        $stmt->execute([$customerId]);
        $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);

        $result=[];
        foreach($rows as $r){
            $qty=(int)$r['quantity'];
            $discount=$cart->getDiscount($qty);
            $unit=round((float)$r['price']*(1-(float)$discount), 2);
            $result[]=[
                "product_id"=>(int)$r['product_id'],
                "title"=>(string)$r['title'],
                "quantity"=>$qty,
                "discount"=>(float)$discount,
                "unit_price"=>(float)$unit,
                "position_sum"=>(float)round($unit * $qty, 2)
            ];
        }
        echo json_encode($result);
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