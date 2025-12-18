<?php
session_start();
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>PosterShop - Bestellung abschließen</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../public/css/style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="bg-light">

<div class="container my-4">
  <h2 class="mb-4 text-center">Bestellung abschließen</h2>

  <div class="row g-4">
    <!-- FORMULAR LINKS -->
    <div class="col-lg-8">
      <form method="POST" action="../backend/processOrder.php" class="card shadow-sm p-4">

        <!-- Rechnungsadresse -->
        <h5 class="mb-3">Rechnungsadresse</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label for="name" class="form-label">Name</label>
            <input type="text" id="name" name="name" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label for="street" class="form-label">Straße & Hausnummer</label>
            <input type="text" id="street" name="street" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label for="zip" class="form-label">PLZ</label>
            <input type="text" id="zip" name="zip" class="form-control" required>
          </div>
          <div class="col-md-8">
            <label for="city" class="form-label">Stadt</label>
            <input type="text" id="city" name="city" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label for="country" class="form-label">Land</label>
            <input type="text" id="country" name="country" class="form-control" required>
          </div>
        </div>

        <!-- Versandart -->
        <h5 class="mt-4 mb-3">Versandart</h5>
        <select id="shipping" name="shipping" class="form-select mb-3" required>
          <option value="dhl">DHL (6,90 €)</option>
          <option value="express">DHL Express (+10 €)</option>
          <option value="dpd">DPD (+5 €)</option>
        </select>

        <!-- Zahlungsdetails -->
        <h5 class="mt-4 mb-3">Zahlungsdetails</h5>
        <div class="mb-3">
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="payment" id="paypal" value="paypal" required>
            <label class="form-check-label" for="paypal">PayPal</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="payment" id="creditcard" value="creditcard" required>
            <label class="form-check-label" for="creditcard">Kreditkarte</label>
          </div>
        </div>

        <div id="creditcard-fields" class="d-none row g-3">
          <div class="col-md-6">
            <label for="cardnumber" class="form-label">Kartennummer</label>
            <input type="text" id="cardnumber" name="cardnumber" class="form-control">
          </div>
          <div class="col-md-3">
            <label for="expiry" class="form-label">Ablaufdatum</label>
            <input type="text" id="expiry" name="expiry" class="form-control">
          </div>
          <div class="col-md-3">
            <label for="cvc" class="form-label">CVC</label>
            <input type="text" id="cvc" name="cvc" class="form-control">
          </div>
        </div>

        <!-- Gutscheincode -->
        <h5 class="mt-4">Gutscheincode</h5>
        <div class="input-group mb-3">
          <input type="text" id="couponInput" class="form-control" placeholder="Gutscheincode">
          <button class="btn btn-outline-primary" type="button" onclick="applyCoupon()">Einlösen</button>
        </div>
        <ul id="couponList" class="list-group mb-3"></ul>

        <!-- Gesamtsumme -->
        <h5 class="mt-4">Gesamtsumme</h5>
        <p class="lead">Gesamt: <span id="order-total">0,00 €</span></p>

        <!-- Datenschutz -->
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="privacy" name="privacy" required>
          <label class="form-check-label" for="privacy">
            Ich akzeptiere die Datenschutzbestimmungen
          </label>
        </div>

        <div class="d-flex justify-content-between">
          <a href="viewcart.php" class="btn btn-outline-secondary">Zurück zum Warenkorb</a>
          <button type="submit" class="btn btn-success">Bezahlen</button>
        </div>

      </form>
    </div>

    <!-- WARENKORB RECHTS -->
    <div class="col-lg-4">
      <div class="card shadow-sm p-3">
        <h5 class="card-title mb-3">Ihr Warenkorb</h5>
        <div id="cart-items"></div>
        <hr>
        <p class="mb-1">Zwischensumme:</p>
        <p class="fw-bold" id="cart-total">0,00 €</p>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Kreditkartenfelder nur anzeigen, wenn Kreditkarte gewählt ist
  document.querySelectorAll('input[name="payment"]').forEach(el => {
    el.addEventListener('change', () => {
      document.getElementById('creditcard-fields').classList.toggle('d-none', el.value !== 'creditcard');
    });
  });

  let cartTotal = 0;
  let coupons = [];

  fetch("../backend/cartController.php?action=total")
    .then(res => res.json())
    .then(data => {
      cartTotal = data.total;
      updateOrderTotal();
    })
    .catch(err => console.error("Fehler beim Laden des Gesamtpreises:", err));

  function getShippingCost() {
    const shipping = document.getElementById("shipping").value;
    switch (shipping) {
      case "dhl": return 6.90;
      case "express": return 10.00;
      case "dpd": return 5.00;
      default: return 0;
    }
  }

  function applyCoupon() {
    const code = document.getElementById("couponInput").value.trim().toUpperCase();
    if (!code) return;

    let discount = 0;
    if (code === "HIGH5") discount = 5;
    if (code === "NEWYEAR") discount = 2;
    if (discount === 0) { alert("Ungültiger Gutscheincode"); return; }
    if (coupons.some(c => c.code === code)) { alert("Gutschein schon eingelöst"); return; }

    coupons.push({code, discount});
    const li = document.createElement("li");
    li.className = "list-group-item d-flex justify-content-between align-items-center";
    li.innerHTML = `${code} <span>- ${discount.toFixed(2)} €</span>`;
    document.getElementById("couponList").appendChild(li);

    const hidden = document.createElement("input");
    hidden.type = "hidden";
    hidden.name = "coupons[]";
    hidden.value = code;
    document.querySelector("form").appendChild(hidden);

    updateOrderTotal();
    document.getElementById("couponInput").value = "";
  }

  function updateOrderTotal() {
    const shippingCost = getShippingCost();
    const couponTotal = coupons.reduce((sum, c) => sum + c.discount, 0);
    const total = cartTotal + shippingCost - couponTotal;
    document.getElementById("order-total").innerText = total.toFixed(2) + " €";
  }

  document.getElementById("shipping").addEventListener("change", updateOrderTotal);
</script>

</body>
</html>
