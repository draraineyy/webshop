<?php
session_start();

if(!isset($_SESSION['customer_id'])){
    header('Location: viewlogin.php');
    exit;
}

require_once("../db.php");

// CSRF-Token bereitstellen
if(empty($_SESSION['csrf'])){
    $_SESSION['csrf']=bin2hex(random_bytes(32));
}
$csrf=$_SESSION['csrf'];
$customerId=(int)$_SESSION['customer_id'];

// Bestellungen laden
$stmt=$pdo->prepare("SELECT id, order_number, date, delivery, `sum` FROM orders WHERE customer_id = ? ORDER BY date DESC");
$stmt->execute([$customerId]);
$orders=$stmt->fetchAll(PDO::FETCH_ASSOC);

// Positionen je Bestellung laden
function loadPositions(PDO $pdo, int $orderId): array{
    $stmt=$pdo->prepare("
        SELECT op.product_id, op.quantity, op.price, op.discount, p.title, p.picture_path
        FROM order_position op
        JOIN products p ON p.id=op.product_id
        WHERE op.order_id = ?
        ORDER BY op.id ASC
    ");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Meine Bestellungen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Eigene CSS -->
    <link rel="stylesheet" href="../css/style.css">

    <style>
        .order-card {
            margin-bottom: 1.5rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fff;
            padding: 1rem;
        }
        .order-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .order-body {
            border-top: 1px solid #eee;
            padding-top: 0.75rem;
        }
        .pos-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .pos-item img {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 0.75rem;
        }
        .badge-delivery {
            background-color: #17a2b8;
            color: #fff;
            font-size: 0.75rem;
            padding: 0.35em 0.5em;
            border-radius: 0.25rem;
        }
        @media (max-width: 576px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .order-header div {
                margin-bottom: 0.5rem;
            }
            .pos-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .pos-item img {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <h2 class="mb-2 mb-sm-0">Meine Bestellungen</h2>
        <a href="viewproducts.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Zu den Produkten</a>
    </div>

    <?php if(empty($orders)): ?>
        <div class="alert alert-info">Du hast bisher keine Bestellungen.</div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php
                $positions = loadPositions($pdo, (int)$order['id']);
                $delivery = $order['delivery'];
                $sumStr = number_format((float)$order['sum'], 2, ',', '.');
            ?>
            <div class="order-card shadow-sm">
                <div class="order-header">
                    <div>
                        <strong>Bestellnummer:</strong> <?= htmlspecialchars($order['order_number']) ?>
                        <span class="ms-3 text-muted"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars($order['date']) ?></span>
                        <span class="ms-2 badge badge-delivery"><?= htmlspecialchars(strtoupper($delivery)) ?></span>
                    </div>
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="me-3"><strong>Summe:</strong> <?= $sumStr ?> €</span>
                        <form method="POST" action="../backend/reorder.php" class="d-inline">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-rotate"></i> Nochmals bestellen
                            </button>
                        </form>
                    </div>
                </div>

                <div class="order-body">
                    <?php foreach ($positions as $pos): ?>
                        <div class="pos-item">
                            <?php if (!empty($pos['picture_path'])): ?>
                                <img src="<?= htmlspecialchars($pos['picture_path']) ?>" alt="<?= htmlspecialchars($pos['title'] ?? 'Produkt') ?>">
                            <?php else: ?>
                                <div style="width:64px; height:64px; background:#eee; border:1px solid #ddd; border-radius:6px;"></div>
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <div><strong><?= htmlspecialchars($pos['title'] ?? 'Produkt #' . $pos['product_id']) ?></strong></div>
                                <div class="text-muted">
                                    Menge: <?= (int)$pos['quantity'] ?> &middot;
                                    Preis/Stk: <?= number_format((float)$pos['price'], 2, ',', '.') ?> € &middot;
                                    Rabatt: <?= (float)$pos['discount']*100 ?>%
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
