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

  <!-- Tabelle -->
  <div id="cart-items" class="table-responsive"></div>

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
<script src="../frontend/js/cart.js"></script>
<script>
  // Warenkorb-Items darstellen
  function updateCartView(items) {
    const count = Object.values(items).reduce((sum, item) => sum + parseInt(item.quantity), 0);
    document.getElementById("cart-count").innerText = count;

    let output = "<table class='table table-bordered'>";
    output += "<thead><tr><th>Produkt</th><th>Menge</th><th>Rabatt</th><th>Preis/Stück</th><th>Entfernen</th></tr></thead><tbody>";

    for (const [productId, item] of Object.entries(items)) {
      let discountText = item.discount > 0 ? (item.discount * 100) + "%" : "-";
      output += `
        <tr>
          <td>Produkt ${productId}</td>
          <td>
            <input type="number" min="1" value="${item.quantity}"
                   onchange="updateCart(${productId}, this.value)"
                   class="form-control form-control-sm" style="width:80px;">
          </td>
          <td>${discountText}</td>
          <td id="price-${productId}">-</td>
          <td>
            <button class="btn btn-sm btn-danger" onclick="removeFromCart(${productId})">X</button>
          </td>
        </tr>`;
    }

    output += "</tbody></table>";
    document.getElementById("cart-items").innerHTML = output;

    // Preise nachladen
    for (const [productId, item] of Object.entries(items)) {
      getProductPrice(productId);
    }
  }

  // Warenkorb laden
  document.addEventListener("DOMContentLoaded", () => {
    getCart();       // Items
    updateCartTotal(); // Gesamtpreis
  });

  // Gesamtpreis
  function updateCartTotal() {
    fetch("../backend/cartController.php?action=total")
      .then(res => res.json())
      .then(data => {
        document.getElementById("cart-total").innerText = data.total.toFixed(2) + " €";
      })
      .catch(err => console.error("Fehler beim Laden des Gesamtpreises:", err));
  }

  // Preis pro Produkt
  function getProductPrice(productId) {
    fetch("../backend/cartController.php?action=price&product_id=" + productId)
      .then(res => res.json())
      .then(data => {
        document.getElementById("price-" + productId).innerText = data.price + " €";
      });
  }
</script>

</body>
</html>