<?php
session_start();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Warenkorb</title>
    <!-- CSS  -->
    <link rel="stylesheet" href="../frontend/css/style.css">
</head>
<body>

<h1>Your Cart</h1>

<!-- Anzeige der Anzahl Artikel -->
<p>Items in cart: <span id="cart-count">0</span></p>

<!-- Beispiel-Buttons für Test -->
<button onclick="addToCart(1, 1)">Add Product 1</button>
<button onclick="addToCart(2, 3)">Add Product 2 (3 pcs)</button>
<button onclick="removeFromCart(1)">Remove Product 1</button>
<button onclick="updateCart(2, 5)">Update Product 2 to 5 pcs</button>
<button onclick="getCart()">Refresh Cart</button>

<!-- Hier kannst du später eine Tabelle oder Liste für die Warenkorb-Items einbauen -->
<div id="cart-items"></div>

<!-- Einbindung von cart.js -->
<script src="../frontend/js/cart.js"></script>

<script>
// Beispiel: Darstellung der Items im Warenkorb
function updateCartView(items) {
    // Anzahl Artikel aktualisieren
    document.getElementById("cart-count").innerText = Object.keys(items).length;

    // Liste der Items anzeigen
    let output = "<ul>";
    for (const [productId, quantity] of Object.entries(items)) {
        output += `<li>Product ${productId}: ${quantity} pcs</li>`;
    }
    output += "</ul>";
    document.getElementById("cart-items").innerHTML = output;
}
</script>

</body>
</html>