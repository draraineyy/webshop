<?php
session_start();
require_once("../db.php");

// Login prüfen
if (!isset($_SESSION['customer_id'])) {
    header("Location: viewlogin.php");
    exit;
}

$isLoggedIn = true;
$username   = $_SESSION['username'] ?? '';
$lastSeenTs = $_SESSION['time'] ?? null;

// Punkte laden
$stmt = $pdo->prepare("SELECT COALESCE(SUM(points),0) FROM points WHERE customer_id=?");
$stmt->execute([$_SESSION['customer_id']]);
$totalPoints = (int)$stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>PosterShop - Account</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">

  <style>
    /* Icon + Badge nebeneinander */
    .navbar .nav-link i {
        font-size: 1.2rem;
        margin-right: 4px;
    }

    .navbar .badge {
        position: static !important;
        transform: none !important;
        font-size: 0.75rem;
        padding: 3px 6px;
        margin-left: 2px;
    }

    /* Mobile */
    @media (max-width: 576px) {
        .navbar .nav-link i {
            font-size: 1.4rem;
        }
        .navbar .badge {
            font-size: 0.85rem;
            padding: 4px 7px;
        }
    }
  </style>
</head>

<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">

    <a class="navbar-brand" href="../index.php">PosterShop</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav ms-auto align-items-center">

        <li class="nav-item">
          <a class="nav-link" href="vieworders.php">Meine Bestellungen</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="viewproducts.php">Artikelübersicht</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="viewpoints.php">Punkte: <?= $totalPoints ?></a>
        </li>

        <!-- Warenkorb -->
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="viewcart.php">
            <i class="fa-solid fa-cart-shopping"></i>
            <span id="cartBadge" class="badge bg-danger">0</span>
          </a>
        </li>

        <!-- Online-User -->
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="#">
            <i class="fa-solid fa-user-group"></i>
            <span id="onlineBadge" class="badge bg-info">0</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="logout.php">Abmelden</a>
        </li>

      </ul>
    </div>

  </div>
</nav>

<!-- Begrüßung -->
<?php include __DIR__ . "/partials/greeting.php"; ?>

<!-- CAROUSEL (Slides only) -->
<div class="container mt-4">
  <div id="carouselAccount" class="carousel slide" data-bs-ride="carousel" data-bs-interval="2000">
    <div class="carousel-inner">

      <div class="carousel-item active">
        <img src="../images/tropen.png" class="d-block w-100" alt="Poster 1">
      </div>

      <div class="carousel-item">
        <img src="../images/skyline.png" class="d-block w-100" alt="Poster 2">
      </div>

      <div class="carousel-item">
        <img src="../images/alpen.png" class="d-block w-100" alt="Poster 3">
      </div>

    </div>
  </div>
</div>

<!-- Über uns -->
<div class="container mt-4">
  <div class="p-4 bg-white rounded shadow-sm">
    <h3 class="mb-3">Willkommen im PosterShop</h3>
    <p>
      Hinter diesem Shop stehen wir – zwei Wirtschaftsinformatik-Studentinnen mit dem Wunsch, neben dem Studium ein
      eigenes Herzensprojekt aufzubauen. Unsere Leidenschaft für Fotografie, Farben und besondere Momente hat uns dazu
      gebracht, PosterShop ins Leben zu rufen.
    </p>
    <p>
      Dass du hier eingeloggt bist, bedeutet uns viel. Du unterstützt nicht nur ein kleines, wachsendes Projekt,
      sondern auch zwei junge Frauen, die ihre Vision Schritt für Schritt Realität werden lassen.
    </p>
  </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/cart.js"></script>

<script>
  // Carousel sicher initialisieren
  document.addEventListener("DOMContentLoaded", function () {
      var myCarousel = document.querySelector('#carouselAccount');
      new bootstrap.Carousel(myCarousel, {
          interval: 3000,
          ride: 'carousel',
          wrap: true
      });
  });

  // Warenkorb-Badge
  function updateCartBadge() {
    fetch("../backend/cartController.php?action=count")
      .then(res => res.json())
      .then(data => {
        const el = document.getElementById("cartBadge");
        if (el) el.innerText = data.count;
      });
  }
  window.addEventListener("pageshow", updateCartBadge);

  // Online-Status
  function updateOnlineBadge() {
    fetch("/webshop/backend/status/onlineHeartbeat.php?ts=" + Date.now(), {
        credentials: "include"
    })
      .then(res => res.json())
      .then(data => {
        const el = document.getElementById("onlineBadge");
        if (el && data.loggedIn) el.innerText = data.count;
      });
  }
  window.addEventListener("pageshow", updateOnlineBadge);
  setInterval(updateOnlineBadge, 20000);
</script>

</body>
</html>