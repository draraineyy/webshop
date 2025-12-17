<?php
require_once("../db.php");
$stmt = $pdo->query("SELECT id, number, title, description, price, picture_path, stock FROM products");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>PosterShop - Artikelübersicht</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">

<div class="container mt-4">
  <!-- Überschrift + Warenkorb nebeneinander -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="mb-0">Unsere Poster</h2>

  <div>
    <!-- Zur Startseite -->
    <a href="../index.html" class="btn btn-outline-secondary me-2">
      <i class="fa-solid fa-house"></i> Startseite
    </a>

    <!-- Warenkorb-Button mit Badge -->
    <a class="nav-link" href="viewcart.php">
        <i class="fa-solid fa-cart-shopping"></i>
        <span id="cartBadge" class="badge bg-danger">0</span>
    </a>
  </div>
</div>

 

  <!-- Suchfeld -->
  <div class="mb-3">
    <input type="text" id="searchInput" class="form-control" placeholder="Suche nach Artikeln...">
  </div>

  <!-- Produktliste -->
  <div class="row" id="productList">
    <?php foreach ($products as $product): ?>
      <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
          <img src="<?php echo $product['picture_path']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['title']); ?>">
          <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($product['title']); ?></h5>
            <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
            <p class="card-text"><strong><?php echo number_format($product['price'], 2); ?> €</strong></p>
            <p class="card-text">Artikelnummer: <?php echo $product['number']; ?></p>
            <p class="card-text">Lagerbestand: <?php echo $product['stock']; ?></p>

            <!-- Menge auswählen -->
            <div class="input-group mb-3">
              <button class="btn btn-outline-secondary" type="button" onclick="changeQuantity(<?php echo $product['id']; ?>, -1)">-</button>
              <input type="number" id="qty-<?php echo $product['id']; ?>" class="form-control text-center" value="1" min="1">
              <button class="btn btn-outline-secondary" type="button" onclick="changeQuantity(<?php echo $product['id']; ?>, 1)">+</button>
            </div>

            <!-- In den Warenkorb -->
            <button class="btn btn-primary w-100" onclick="addToCart(<?php echo $product['id']; ?>, document.getElementById('qty-<?php echo $product['id']; ?>').value)">
              <i class="fa-solid fa-cart-shopping"></i> In den Warenkorb
            </button>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script src="js/cart.js"></script>
<script>
// Menge ändern
function changeQuantity(productId, delta) {
  let inputField = document.getElementById("qty-" + productId);
  let newValue = parseInt(inputField.value) + delta;
  if (newValue < 1) newValue = 1;
  inputField.value = newValue;
}

// Suchfunktion
document.getElementById("searchInput").addEventListener("keyup", function() {
  let filter = this.value.toLowerCase();
  let cards = document.querySelectorAll("#productList .card");

  cards.forEach(card => {
    let title = card.querySelector(".card-title").innerText.toLowerCase();
    let description = card.querySelector(".card-text").innerText.toLowerCase();

    if (title.includes(filter) || description.includes(filter)) {
      card.parentElement.style.display = "";
    } else {
      card.parentElement.style.display = "none";
    }
  });
});

function updateCartBadge() {
  fetch("../backend/cartController.php?action=count&ts=" + Date.now())
    .then(res => res.json())
    .then(data => {
      document.getElementById("cartBadge").innerText = data.count;
    });
}

function updateCartBadge() {
  fetch("../backend/cartController.php?action=count&ts=" + Date.now())
    .then(res => res.json())
    .then(data => {
      document.getElementById("cartBadge").innerText = data.count;
    });
}

function updateCartBadge() {
  fetch("../backend/cartController.php?action=count&ts=" + Date.now())
    .then(res => res.json())
    .then(data => {
      document.getElementById("cartBadge").innerText = data.count;
    });
}
//wird jedes Mal beim öffnen einer Seite oder zurückgehen zu einer Seite ausgeführt
window.addEventListener("pageshow", updateCartBadge);


</script>

</body>
</html>