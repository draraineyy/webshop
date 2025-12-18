<?php
session_start();
require_once("../db.php");

if (!isset($_SESSION['customer_id'])) {
    header("Location: viewlogin.php");
    exit;
}

$isLoggedIn = true;
$username   = $_SESSION['username'] ?? '';
$lastSeenTs = $_SESSION['time'] ?? null;

// Punkte abfragen
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

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">

  <style>
    /* Navbar etwas nach unten schieben, damit Badges nicht abgeschnitten werden */
    .navbar {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }

    /* Badges über Icons positionieren */
    .navbar .nav-item.position-relative .badge {
        position: absolute;
        top: 0;
        right: 0;
        transform: translate(50%, -50%);
        font-size: 0.75rem;
        min-width: 1.2rem;
        height: 1.2rem;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Navbar auf kleinen Bildschirmen etwas größer */
    @media (max-width: 576px) {
        .navbar {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }
    }
  </style>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand" href="../index.php">PosterShop</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
            aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
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
          <span class="nav-link">Punkte: <?= $totalPoints ?></span>
        </li>

        <li class="nav-item position-relative">
          <a class="nav-link" href="viewcart.php">
            <i class="fa-solid fa-cart-shopping"></i>
            <span id="cartBadge" class="badge rounded-pill bg-danger">0</span>
          </a>
        </li>

        <li class="nav-item position-relative">
          <a class="nav-link" href="#">
            <i class="fa-solid fa-user-group"></i>
            <span id="onlineBadge" class="badge rounded-pill bg-info">0</span>
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

<!-- Carousel -->
<div class="container mt-4">
    <div id="posterCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="3000">
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
        <button class="carousel-control-prev" type="button" data-bs-target="#posterCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Zurück</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#posterCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Weiter</span>
        </button>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/cart.js"></script>

<script>
  // Warenkorb-Badge aktualisieren
  function updateCartBadge() {
    fetch("../backend/cartController.php?action=count")
      .then(res => res.json())
      .then(data => {
        const el = document.getElementById("cartBadge");
        if(el) el.innerText = data.count;
      })
      .catch(err => console.error(err));
  }
  window.addEventListener("pageshow", updateCartBadge);

  // Online-Status
  const ONLINE_POLL_INTERVAL = 20000;
  function updateOnlineBadge() {
    fetch("/webshop/backend/status/onlineHeartbeat.php?ts=" + Date.now())
      .then(res => res.json())
      .then(data => {
        const el = document.getElementById("onlineBadge");
        if(el && data.loggedIn) el.innerText = data.count;
      })
      .catch(err => console.error(err));
  }
  window.addEventListener("pageshow", updateOnlineBadge);
  setInterval(updateOnlineBadge, ONLINE_POLL_INTERVAL);
</script>

</body>
</html>
