<?php
session_start();

// Status prüfen
$isLoggedIn = isset($_SESSION['customer_id']);
$username   = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Warenkorb</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* Optional: Inputs auf mobile kleiner */
    input.form-control-sm {
      max-width: 60px;
    }
    /* Kartenlayout bei mobile */
    @media (max-width: 767px) {
      .cart-card {
        margin-bottom: 15px;
        padding: 10px;
      }
    }
  </style>
</head>
<body class="bg-light">

<div class="container mt-4">
  <!-- Kopfzeile -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Warenkorb</h2>
    <a href="viewproducts.php" class="btn btn-outline-secondary">
      <i class="fa-solid fa-arrow-left"></i> Zur Artikelübersicht
    </a>
  </div>

  <!-- Anzeige Anzahl & Gesamtpreis -->
  <p class="lead">
    Artikel im Warenkorb: <span id="cart-count">0</span><br>
    Gesamtpreis: <span id="cart-total">0,00 €</span>
  </p>

  <!-- Container für Warenkorb-Items -->
  <div id="cart-items"></div>

  <!-- Zur Kasse -->
  <div class="mt-3">
    <?php if ($isLoggedIn): ?>
      <a href="checkout.php" class="btn btn-success">Zur Kasse</a>
    <?php else: ?>
      <p class="text-muted">Bitte <a href="viewlogin.php">melden Sie sich an</a>, um zur Kasse zu gehen.</p>
    <?php endif; ?>
  </div>
</div>

<!-- JS -->
<script src="js/cart.js"></script>

</body>
</html>
