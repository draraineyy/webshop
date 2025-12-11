<?php
class Cart {
    private $pdo;
    private $customerId;

    public function __construct($pdo, $customerId = null) {
        $this->pdo = $pdo;
        $this->customerId = $customerId;

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    // --- Gast-Warenkorb (Session) ---
    public function add($productId, $quantity = 1) {
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $quantity;
    }

    public function remove($productId) {
        unset($_SESSION['cart'][$productId]);
    }

    public function update($productId, $quantity) {
        if ($quantity <= 0) {
            $this->remove($productId);
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
    }

    public function getItems() {
        return $_SESSION['cart'];
    }

    public function clear() {
        $_SESSION['cart'] = [];
    }

    // --- Persistenter Warenkorb (DB) ---
    public function saveToDb() {
        if (!$this->customerId) return; // nur für eingeloggte User

        $stmt = $this->pdo->prepare("SELECT id FROM cart WHERE customer_id=? AND status='open'");
        $stmt->execute([$this->customerId]);
        $cartId = $stmt->fetchColumn();

        if (!$cartId) {
            $stmt = $this->pdo->prepare("INSERT INTO cart (customer_id, status, created_at) VALUES (?, 'open', NOW())");
            $stmt->execute([$this->customerId]);
            $cartId = $this->pdo->lastInsertId();
        }

        $stmt = $this->pdo->prepare("DELETE FROM cart_position WHERE cart_id=?");
        $stmt->execute([$cartId]);

        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $stmt = $this->pdo->prepare("INSERT INTO cart_position (cart_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$cartId, $productId, $quantity]);
        }
    }

    public function loadFromDb() {
        if (!$this->customerId) return;

        $stmt = $this->pdo->prepare("SELECT id FROM cart WHERE customer_id=? AND status='open'");
        $stmt->execute([$this->customerId]);
        $cartId = $stmt->fetchColumn();

        if ($cartId) {
            $stmt = $this->pdo->prepare("SELECT product_id, quantity FROM cart_position WHERE cart_id=?");
            $stmt->execute([$cartId]);
            $items = $stmt->fetchAll();

            $_SESSION['cart'] = [];
            foreach ($items as $row) {
                $_SESSION['cart'][$row['product_id']] = $row['quantity'];
            }
        }
    }
} 