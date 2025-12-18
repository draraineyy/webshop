// cart.js – AJAX-Funktionen für Warenkorb

function addToCart(productId, quantity = 1) {
    fetch("../backend/cartController.php?action=add", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `product_id=${productId}&quantity=${quantity}`

        
    }) 
    .then(res => res.json())
    .then(data => {
        console.log("Cart after add:", data.items);
        updateCartView(data.items);
        updateCartTotal();
    });
}

function removeFromCart(productId) {
    fetch("../backend/cartController.php?action=remove", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `product_id=${productId}`
    })
    .then(res => res.json())
    .then(data => {
        console.log("Cart after remove:", data.items);
        updateCartView(data.items);
        updateCartTotal();
    });
}

function updateCartView(items) {
    // Gesamtanzahl berechnen (Summe aller Mengen)
    const count = Object.values(items).reduce((sum, item) => sum + parseInt(item.quantity), 0);

    // Badge in Navbar aktualisieren
    const badge = document.getElementById("cartBadge");
    if (badge) {
        badge.innerText = count;
    }

    // Warenkorb-Liste auf viewcart.php aktualisieren
    const cartItemsDiv = document.getElementById("cart-items");
    if (cartItemsDiv) {
        let output = "<table class='table table-bordered'>";
        output += "<thead><tr><th>Produkt</th><th>Menge</th><th>Rabatt</th></tr></thead><tbody>";

        for (const [productId, item] of Object.entries(items)) {
            let discountText = item.discount > 0 ? (item.discount * 100) + "%" : "-";

            output += `
              <tr>
                <td>Produkt ${productId}</td>
                <td>${item.quantity} Stück</td>
                <td>${discountText}</td>
              </tr>`;
        }

        output += "</tbody></table>";
        cartItemsDiv.innerHTML = output;
    }
}

function getCart() {
    fetch("../backend/cartController.php?action=get")
    .then(res => res.json())
    .then(data => {
        console.log("Current cart:", data.items);
        updateCartView(data.items);
    });
}

function updateCart(productId, quantity) {  // wenn ich in viewcart die Zahl im Inputfeld ändere, wird ein update-request an den controller geschickt
    fetch("../backend/cartController.php?action=update", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(res => res.json())
    .then(data => {
        console.log("Cart after update:", data.items);
        updateCartView(data.items);   // Ansicht aktualisieren
        updateCartTotal();            // Gesamtpreis aktualisieren
    });
}


function updateCartTotal() {
  fetch("../backend/cartController.php?action=total")
    .then(res => res.json())
    .then(data => {
      document.getElementById("cart-total").innerText = data.total.toFixed(2) + " €";
    })
    .catch(err => console.error("Fehler beim Laden des Gesamtpreises:", err));
}


// Beispiel: nach einem Produkt-Update erneut ausführen
document.addEventListener("DOMContentLoaded", () => {
  getCart();
  updateCartTotal();
});

// Preis abrufen
function getProductPrice(productId) {
  fetch("../backend/cartController.php?action=price&product_id=" + productId)
    .then(res => res.json())
    .then(data => {
      console.log("Preis für Produkt " + productId + ": " + data.price + " €");
      document.getElementById("price-" + productId).innerText = data.price + " €";
    });
}

//Anzahl neben Warenkorbsymbol aktualisieren
function updateCartBadge() {
  fetch("../backend/cartController.php?action=count&ts=" + Date.now())
    .then(res => res.json())
    .then(data => {
      document.getElementById("cartBadge").innerText = data.count;
    });
}