// cart.js – AJAX-Funktionen für Warenkorb

function addToCart(productId, quantity = 1) {
    fetch("../backend/cart.php?action=add", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(res => res.json())
    .then(data => {
        console.log("Cart after add:", data.items);
        updateCartView(data.items);
    });
}

function removeFromCart(productId) {
    fetch("../backend/cart.php?action=remove", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `product_id=${productId}`
    })
    .then(res => res.json())
    .then(data => {
        console.log("Cart after remove:", data.items);
        updateCartView(data.items);
    });
}

function updateCart(productId, quantity) {
    fetch("../backend/cart.php?action=update", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(res => res.json())
    .then(data => {
        console.log("Cart after update:", data.items);
        updateCartView(data.items);
    });
}

function getCart() {
    fetch("../backend/cart.php?action=get")
    .then(res => res.json())
    .then(data => {
        console.log("Current cart:", data.items);
        updateCartView(data.items);
    });
}

// Darstellung aktualisieren
function updateCartView(items) {
    // Anzahl Artikel berechnen
    const count = Object.keys(items).length;

    // Badge in Navbar aktualisieren
    const badge = document.getElementById("cartBadge");
    if (badge) {
        badge.innerText = count;
    }

    // Optional: Warenkorb-Liste auf viewcart.php aktualisieren
    const cartItemsDiv = document.getElementById("cart-items");
    if (cartItemsDiv) {
        let output = "<ul>";
        for (const [productId, quantity] of Object.entries(items)) {
            output += `<li>Product ${productId}: ${quantity} pcs</li>`;
        }
        output += "</ul>";
        cartItemsDiv.innerHTML = output;
    }
}