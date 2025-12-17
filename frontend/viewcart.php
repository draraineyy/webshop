<?php
session_start();
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Warenkorb</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
  <!-- Kopfzeile mit Warenkorb-Symbol -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Warenkorb</h2>

    <a href="viewproducts.php" class="btn btn-outline-secondary">
  <i class="fa-solid fa-arrow-left"></i> Zur Artikelübersicht
</a>

  </div>

  <!-- Anzeige der Anzahl Artikel und Gesamtpreis-->
 <p class="lead">
  Artikel im Warenkorb: <span id="cart-count">0</span><br>
  Gesamtpreis: <span id="cart-total">0,00 €</span>
 </p>


  <!-- Warenkorb-Tabelle -->
  <div id="cart-items" class="table-responsive"></div>

  <!-- Zur Kasse -->
  <div class="mt-3">
    <a href="checkout.php" class="btn btn-success">Zur Kasse</a>
  </div>
</div>

<script src="../frontend/js/cart.js"></script>

<script>
// Darstellung der Items im Warenkorb
function updateCartView(items) {
  // Gesamtanzahl berechnen (Summe aller Mengen)
  const count = Object.values(items).reduce((sum, item) => sum + parseInt(item.quantity), 0);
  document.getElementById("cart-count").innerText = count;

  // Tabelle aufbauen
  let output = "<table class='table table-bordered'>";
  output += "<thead><tr><th>Produkt</th><th>Menge</th><th>Rabatt</th><th>Preis/Stück</th><th>Artikel entfernen</th></tr></thead><tbody>";

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
         <td id="price-${productId}">-</td> <!-- Platzhalter für Preis -->
        <td>
          <button class="btn btn-sm btn-danger" onclick="removeFromCart(${productId})">X</button>
        </td>
      </tr>`;
  }

  output += "</tbody></table>";
  document.getElementById("cart-items").innerHTML = output; //Tabelle ins DOM schreiben

   //Preise nachladen, nachdem die Tabelle im DOM steht
  for (const [productId, item] of Object.entries(items)) {
    getProductPrice(productId); // füllt die <td id="price-..."> mit dem echten Preis
  }

}

// Warenkorb beim Laden aktualisieren
document.addEventListener("DOMContentLoaded", getCart);

function updateCartTotal() {
  fetch("../backend/cartController.php?action=total")
    .then(res => res.json())
    .then(data => {
      document.getElementById("cart-total").innerText = data.total.toFixed(2) + " €";
    })
    .catch(err => console.error("Fehler beim Laden des Gesamtpreises:", err));
}

// Beim Laden der Seite sofort ausführen
updateCartTotal();

// Beispiel: nach einem Produkt-Update erneut ausführen
document.addEventListener("DOMContentLoaded", () => {
  getCart();
  updateCartTotal();
});

//Preis abrufen
function getProductPrice(productId) {
  fetch("../backend/cartController.php?action=price&product_id=" + productId)
    .then(res => res.json())
    .then(data => {
      console.log("Preis für Produkt " + productId + ": " + data.price + " €");
      document.getElementById("price-" + productId).innerText = data.price + " €";
    });
}

</script>

</body>
</html>