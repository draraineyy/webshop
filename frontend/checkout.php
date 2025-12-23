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

        <!-- Gutscheincode 
        <h5 class="mt-4">Gutscheincode</h5>
        <div class="input-group mb-3">
          <input type="text" id="couponInput" class="form-control" placeholder="Gutscheincode">
          <button class="btn btn-outline-primary" type="button" onclick="applyCoupon()">Einlösen</button>
        </div>
        <ul id="couponList" class="list-group mb-3"></ul>

         Gesamtsumme
        <h5 class="mt-4">Gesamtsumme</h5>
        <p class="lead">Gesamt: <span id="order-total">0,00 €</span></p>-->

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

        <!--Hidden Inputs für POST -->
        <input type="hidden" name="loyalty_points" id="loyalty_points_post" value="0">
        <input type="hidden" name="loyalty_value_eur" id="loyalty_value_post" value="0.00">

      </form>
    </div>

    
    <!-- WARENKORB RECHTS -->
    <div class="col-lg-4">
      <div class="card shadow-sm p-3">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-3">Ihr Warenkorb</h5>
            <span id="cartBadge" class="badge bg-primary">0</span>
          </div>

          <!-- Items Liste -->
          <div id="cart-items" class="mb-2"></div>

          <hr>

          <!-- Zusammenfassung -->
          <div class="d-flex justify-content-between">
            <span class="text-muted">Zwischensumme</span>
            <strong id="summary-subtotal">0,00 €</strong>
          </div>
          <div class="d-flex justify-content-between">
            <span class="text-muted">Versand</span>
            <strong id="summary-shipping">0,00 €</strong>
          </div>
          <div id="promo-lines" class="mt-1">
          <!-- Dynamisch: Promo-Zeilen werden hier eingefügt -->
          </div>
          <div class="d-flex justify-content-between mt-2 border-top pt-2">
            <span class="fw-semibold">Gesamt</span>
            <strong id="summary-total" class="fw-bold">0,00 €</strong>
          </div>

          <!-- Promo-Code Eingabe rechts -->
          <div class="input-group mt-3">
            <input type="text" id="couponInputRight" class="form-control" placeholder="Gutscheincode">
            <button class="btn btn-outline-primary" type="button" id="redeemRight">Einlösen</button>
          </div>

          <!-- Stammkunden Bonus -->
           <div class="mt-3 card card-body">
            <div class="d-flex justify-content-between align-items-center">
              <strong>Stammkunden-Bonus</strong>
              <span id="loyalty-balance" class="badge bg-info">0 Punkte</span>
            </div>
            <div class="mt-2">
              <label for="loyaltyRedeem" class="form-label">
                Punkte einlösen <small class="text-muted">(50 Punkte = 0,10€)</small>
              </label>
              <div class="input-group">
                <input type="number" id="loyaltyRedeem" class="form-control" min="0" step="1" value="0">
                <button type="button" class="btn btn-outline-primary" id="applyLoyalty">Einlösen</button>
              </div>
              <div class="form-text">
                Wert: <strong id="loyalty-value">0,00 €</strong>
              </div>
            </div>
          </div>

          <!-- Versteckte Liste für POST (wird per JS gepflegt) -->
          <ul id="couponList" class="list-group mt-2 d-none"></ul>
        </div>
      </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
  
<script>
  // Kreditkartenfelder nur anzeigen, wenn Kreditkarte gewählt ist
  document.querySelectorAll('input[name="payment"]').forEach(el => {
    el.addEventListener('change', e => {
      document.getElementById('creditcard-fields')
        .classList.toggle('d-none', e.target.value !== 'creditcard');
    });
  });

  let cartTotal = 0;   // Zwischensumme (ohne Versand, ohne Coupons)
  let coupons   = [];  // [{code, discount}, ...]
  let loyaltyRedeemPoints = 0;
  const LOYALTY_EUR_PER_POINT = 0.002; // Umrechnungsfaktor für Punkte

  // EUR-Format mit geschütztem Leerzeichen
  const fmtEUR = (n) =>
    (Number(n) || 0).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '\u00A0€';

  // Versandkosten gemäß Anforderungen
  function getShippingCost() {
    const shipping = document.getElementById("shipping").value;
    switch (shipping) {
      case "express": return 16.90; // DHL 6,90 + 10
      case "dpd":     return 11.90; // DHL 6,90 + 5
      case "dhl":
      default:        return 6.90;
    }
  }

  // Rechte Zusammenfassung aktualisieren
  function updateRightSummary() {
    const shipping   = getShippingCost();
    const couponSum  = coupons.reduce((sum, c) => sum + (Number(c.discount) || 0), 0);
    const loyaltyVal = loyaltyRedeemPoints * LOYALTY_EUR_PER_POINT;
    const total      = (Number(cartTotal) || 0) + shipping - couponSum - loyaltyVal;

    const elSubtotal = document.getElementById('summary-subtotal');
    const elShipping = document.getElementById('summary-shipping');
    const elTotal    = document.getElementById('summary-total');

    if (elSubtotal) elSubtotal.innerText = fmtEUR(cartTotal);
    if (elShipping) elShipping.innerText = fmtEUR(shipping);
    if (elTotal)    elTotal.innerText    = fmtEUR(total);

    // Promo-Zeilen rechts
    const promoBox = document.getElementById('promo-lines');
    if (promoBox) {
      promoBox.innerHTML = '';
      //Gutscheine auflisten
      coupons.forEach(c => {
        const row = document.createElement('div');
        row.className = 'd-flex justify-content-between text-success';
        row.innerHTML = `<span>Gutschein: ${c.code}</span><strong>- ${fmtEUR(c.discount)}</strong>`;
        promoBox.appendChild(row);
      });
      //Loyalität auflisten (falls > 0 eingelöst)
      if (loyaltyRedeemPoints > 0){
        const row = document.createElement('div');
        row.className='d-flex justify-content-between text-success';
        const loyaltyVal = loyaltyRedeemPoints * LOYALTY_EUR_PER_POINT;
        row.innerHTML = `<span>Stammkunden-Bonus (${loyaltyRedeemPoints} Punkte)</span><strong>-${fmtEUR(loyaltyVal)}</strong>`;
        promoBox.appendChild(row);
      }
    }
  }

  // Promo rechts anwenden
  function applyCouponRight() {
    const input = document.getElementById("couponInputRight");
    const code  = (input.value || "").trim().toUpperCase();
    if (!code) return;

    let discount = 0;
    if (code === "HIGH5")  discount = 5;
    if (code === "NEWYEAR") discount = 2;

    if (discount === 0)    { alert("Ungültiger Gutscheincode"); return; }
    if (coupons.some(c => c.code === code)) { alert("Gutschein schon eingelöst"); return; }

    coupons.push({ code, discount });

    // Hidden-Input für POST
    const hidden = document.createElement('input');
    hidden.type  = 'hidden';
    hidden.name  = 'coupons[]';
    hidden.value = code;
    document.querySelector('form').appendChild(hidden);

    input.value = '';
    updateRightSummary();
  }

  // Warenkorb-Items laden und rechts rendern
  function loadCartItems() {
    // Zwischensumme laden
    fetch("../backend/cartController.php?action=total", { credentials: "include" })
      .then(res => res.json())
      .then(data => {
        cartTotal = Number(data.total) || 0;
        updateRightSummary();
      })
      .catch(err => console.error("Fehler beim Laden des Gesamtpreises:", err));

    // Einzelne Positionen laden
    fetch("../backend/cartController.php?action=list", { credentials: "include" })
      .then(res => res.json())
      .then(items => {
        const container = document.getElementById("cart-items");
        if (!container) return;
        container.innerHTML = "";

        // Badge
        const count = Array.isArray(items)
          ? items.reduce((s, it) => s + Number(it.quantity || 0), 0)
          : 0;
        const badge = document.getElementById("cartBadge");
        if (badge) badge.innerText = count;

        if (!items || (Array.isArray(items) && items.length === 0)) {
          container.innerHTML = "<p class='text-muted mb-0'>Ihr Warenkorb ist leer.</p>";
          cartTotal = 0;
          updateRightSummary();
          return;
        }

        const frag = document.createDocumentFragment();
        items.forEach(it => {
          const row = document.createElement("div");
          row.className = "d-flex justify-content-between align-items-center py-1";

          const left = document.createElement("span");
          const pct  = it.discount ? ` (−${(it.discount * 100).toFixed(0)}%)` : "";
          left.textContent = `${it.title} × ${it.quantity}${pct}`;

          const posSum = (it.position_sum != null)
            ? Number(it.position_sum)
            : (Number(it.unit_price) * Number(it.quantity));

          const right = document.createElement("strong");
          right.textContent = fmtEUR(posSum);

          row.appendChild(left);
          row.appendChild(right);
          frag.appendChild(row);
        });

        container.appendChild(frag);
      })
      .catch(err => console.error("Fehler beim Laden der Warenkorb-Items:", err));
  }

  // Stammkunden-Bonus: Punkte vom Backend laden
  async function loadLoyaltyBalance(){
    try{
      const res = await fetch("../backend/loyaltyController.php?action=balance", {credentials: "include"});
      const data = await res.json();
      loyaltyBalance = Number(data.points)||0;

      //UI aktualisieren
      const balEl = document.getElementById("loyalty-balance");
      if (balEl) balEl.innerText = `${loyaltyBalance} Punkte`;
    } catch (err){
      console.warn("Konnte Loyalitätspunkte nicht laden: ", err);
    }
  }

  // Stammkunden-Bonus: UI & Hidden Inputs aktualisieren
  function updateLoyaltyUI(){
    const value = loyaltyRedeemPoints * LOYALTY_EUR_PER_POINT;
    const valEl = document.getElementById("loyalty-value");
    const ptsPost = document.getElementById("loyalty_points_post");
    const eurPost = document.getElementById("loyalty_value_post");

    if(valEl) valEl.innerText = fmtEUR(value);
    if(ptsPost) ptsPost.value = String(loyaltyRedeemPoints);
    if(eurPost) eurPost.value = value.toFixed(2);
  }

  //Stammkunden-Bonus: Einlösen-Button
  function applyLoyaltyRight(){
    const input = document.getElementById("loyaltyRedeem");
    const pts = Math.floor(Number(input.value)||0);

    if (pts < 0){
      alert ("Punkte dürfen nicht negativ sein.");
      return;
    }
    if (pts > loyaltyBalance){
      alert("Sie können nicht mehr Punkte einlösen, als vorhanden sind.");
      return;
    }

    loyaltyRedeemPoints = pts;
    updateLoyaltyUI();
    updateRightSummary();
  }

  // Events
  document.getElementById("shipping").addEventListener("change", updateRightSummary);
  document.getElementById("redeemRight").addEventListener("click", applyCouponRight);
  document.getElementById("applyLoyalty").addEventListener("click", applyLoyaltyRight);

  // Init
  document.addEventListener("DOMContentLoaded", () => {
    loadCartItems();
    loadLoyaltyBalance();
    updateRightSummary();
  });
</script>

</body>
</html>
