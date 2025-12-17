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
</head>
<body class="bg-light">

<div class="container mt-4">
  <!-- Überschrift -->
  <h2 class="mb-4">Bestellung abschließen</h2>

  <!-- Formular -->
  <form method="POST" action="../backend/processOrder.php" class="card shadow-sm p-4">

    <!-- Rechnungsadresse -->
    <h5 class="mb-3">Rechnungsadresse</h5>
    <div class="mb-3">
      <label for="name" class="form-label">Name</label>
      <input type="text" id="name" name="name" class="form-control" required> <!-- Name -->
    </div>
    <div class="mb-3">
      <label for="street" class="form-label">Straße & Hausnummer</label>
      <input type="text" id="street" name="street" class="form-control" required> <!-- Straße -->
    </div>
    <div class="mb-3">
      <label for="zip" class="form-label">PLZ</label>
      <input type="text" id="zip" name="zip" class="form-control" required> <!-- PLZ -->
    </div>
    <div class="mb-3">
      <label for="city" class="form-label">Stadt</label>
      <input type="text" id="city" name="city" class="form-control" required> <!-- Stadt -->
    </div>
    <div class="mb-3">
      <label for="country" class="form-label">Land</label>
      <input type="text" id="country" name="country" class="form-control" required> <!-- Land -->
    </div>

    <!-- Versandart -->
    <h5 class="mt-4 mb-3">Versandart</h5>
    <div class="mb-3">
      <label for="shipping" class="form-label">Versandart wählen:</label>
      <select id="shipping" name="shipping" class="form-select" required>
        <option value="dhl">DHL (6,90 €)</option>
        <option value="express">DHL Express (+10 €)</option>
        <option value="dpd">DPD (+5 €)</option>
      </select>
    </div>

    <!-- Zahlungsdetails -->
    <h5 class="mt-4 mb-3">Zahlungsdetails</h5>
    <div class="mb-3">
      <label class="form-label">Zahlungsart:</label>
      <div class="form-check">
        <input class="form-check-input" type="radio" name="payment" id="paypal" value="paypal" required>
        <label class="form-check-label" for="paypal">PayPal</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="radio" name="payment" id="creditcard" value="creditcard" required>
        <label class="form-check-label" for="creditcard">Kreditkarte</label>
      </div>
    </div>

    <!-- Kreditkartenfelder (werden nur angezeigt, wenn Kreditkarte gewählt ist) -->
    <div id="creditcard-fields" class="d-none">
      <div class="mb-3">
        <label for="cardnumber" class="form-label">Kartennummer</label>
        <input type="text" id="cardnumber" name="cardnumber" class="form-control">
      </div>
      <div class="mb-3">
        <label for="expiry" class="form-label">Ablaufdatum (MM/YY)</label>
        <input type="text" id="expiry" name="expiry" class="form-control">
      </div>
      <div class="mb-3">
        <label for="cvc" class="form-label">CVC</label>
        <input type="text" id="cvc" name="cvc" class="form-control">
      </div>
    </div>

    <!-- Gutscheincode -->
    <h5 class="mt-4">Gutscheincode</h5>
    <div class="input-group mb-3">
      <input type="text" id="couponInput" class="form-control" placeholder="Gutscheincode eingeben">
      <button class="btn btn-outline-primary" type="button" onclick="applyCoupon()">Einlösen</button>
    </div>
    <ul id="couponList" class="list-group mb-3"></ul> <!-- Liste eingelöster Codes -->

    <!-- Gesamtsumme -->
    <h5 class="mt-4">Gesamtsumme</h5>
    <p class="lead">Gesamt: <span id="order-total">0,00 €</span></p>

    <!-- Datenschutz -->
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" id="privacy" name="privacy" required>
      <label class="form-check-label" for="privacy">
        Ich akzeptiere die Datenschutzbestimmungen gemäß DSGVO
      </label>
    </div>

    <!-- Buttons -->
    <div class="d-flex justify-content-between">
      <a href="viewcart.php" class="btn btn-outline-secondary">Zurück zum Warenkorb</a>
      <button type="submit" class="btn btn-success">Bezahlen</button>
    </div>
  </form>
</div>

<script>
  // Kreditkartenfelder nur anzeigen, wenn Kreditkarte gewählt ist
  document.querySelectorAll('input[name="payment"]').forEach(el => {
    el.addEventListener('change', () => {
      document.getElementById('creditcard-fields').classList.toggle('d-none', el.value !== 'creditcard');
    });
  });

  let cartTotal = 0;    // Warenkorb-Gesamt ohne Versand
  let coupons = [];     // Eingelöste Gutscheine

 // Warenkorb-Gesamt aus Backend laden
 fetch("../backend/cartController.php?action=total")
  .then(res => res.json())
  .then(data => {
    cartTotal = data.total; // Warenkorb-Summe speichern
    updateOrderTotal();     // Anzeige aktualisieren
  })
  .catch(err => console.error("Fehler beim Laden des Gesamtpreises:", err));

  // Versandkosten je nach Auswahl
  function getShippingCost() {
    const shipping = document.getElementById("shipping").value;
    switch (shipping) {
      case "dhl": return 6.90;
      case "express": return 10.00;
      case "dpd": return 5.00;
      default: return 0.00;
    }
  }

function applyCoupon() {
  // Eingabe immer in Großbuchstaben wandeln
  const code = document.getElementById("couponInput").value.trim().toUpperCase();

  if (code === "HIGH5" || code === "NEWYEAR") {
    let discount = (code === "HIGH5") ? 5.00 : 2.00;

    // Doppelcodes verhindern
    if (coupons.some(c => c.code === code)) {
      alert("Dieser Gutscheincode wurde bereits eingelöst.");
      document.getElementById("couponInput").value = "";
      return;
    }

    // Rabatt ins Array aufnehmen
    coupons.push({code, discount});

    // Anzeige in der Liste
    const li = document.createElement("li");
    li.className = "list-group-item d-flex justify-content-between align-items-center";
    li.innerHTML = `${code} <span>- ${discount.toFixed(2)} €</span>`;
    document.getElementById("couponList").appendChild(li);

    // Hidden Input für POST
    const hidden = document.createElement("input");
    hidden.type = "hidden";
    hidden.name = "coupons[]";
    hidden.value = code;
    document.querySelector("form").appendChild(hidden);

    updateOrderTotal();
  } else {
    alert("Ungültiger Gutscheincode");
  }

  document.getElementById("couponInput").value = "";
}


  // Endsumme berechnen
  function updateOrderTotal() {
    const shippingCost = getShippingCost();
    const couponTotal = coupons.reduce((sum, c) => sum + c.discount, 0);
    const total = cartTotal + shippingCost - couponTotal;
    document.getElementById("order-total").innerText = total.toFixed(2) + " €";
  }

  // Versandart ändern → neu berechnen
  document.getElementById("shipping").addEventListener("change", updateOrderTotal);
  
</script>

</body>
</html>