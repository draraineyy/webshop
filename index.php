<?php
session_start();

// Login-Status
$isLoggedIn = isset($_SESSION['customer_id']);
$username   = $_SESSION['username'] ?? '';
$lastSeenTs = $_SESSION['time']     ?? null;

// Punkte laden, falls eingeloggt
if ($isLoggedIn) {
    require_once __DIR__ . "/db.php";
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(points),0) FROM points WHERE customer_id=?");
    $stmt->execute([$_SESSION['customer_id']]);
    $totalPoints = (int)$stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>PosterShop - Startseite</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">

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

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">

    <a class="navbar-brand" href="index.php">PosterShop</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav ms-auto align-items-center">

        <li class="nav-item">
          <a class="nav-link" href="frontend/viewproducts.php">Artikelübersicht</a>
        </li>

        <?php if ($isLoggedIn): ?>
          <li class="nav-item">
            <span class="nav-link">Punkte: <?= $totalPoints ?></span>
          </li>
        <?php endif; ?>

        <!-- Warenkorb -->
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="frontend/viewcart.php">
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

        <?php if ($isLoggedIn): ?>
          <li class="nav-item"><a class="nav-link" href="frontend/logout.php">Abmelden</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="frontend/viewlogin.php">Anmelden</a></li>
          <li class="nav-item"><a class="nav-link" href="frontend/register.php">Registrieren</a></li>
        <?php endif; ?>

      </ul>
    </div>

  </div>
</nav>

<!-- Begrüßung -->
<?php include __DIR__ . "/frontend/partials/greeting.php"; ?>

<!-- Logout Meldung -->
<div class="container mt-2" id="logoutMessage"></div>

<script>
const params = new URLSearchParams(window.location.search);
if (params.has("logout")) {
  document.getElementById("logoutMessage").innerHTML =
    "<div class='alert alert-success'>Sie haben sich erfolgreich abgemeldet.</div>";
}
</script>

<!--  CAROUSEL (Slides Only) -->
<div class="container mt-4">
  <div id="carouselExampleSlidesOnly" class="carousel slide" data-bs-ride="carousel" data-bs-interval="2000">
    <div class="carousel-inner">

      <div class="carousel-item active">
        <img src="images/tropen.png" class="d-block w-100" alt="Poster 1">
      </div>

      <div class="carousel-item">
        <img src="images/skyline.png" class="d-block w-100" alt="Poster 2">
      </div>

      <div class="carousel-item">
        <img src="images/alpen.png" class="d-block w-100" alt="Poster 3">
      </div>

    </div>
  </div>
</div>

<!-- Über uns -->
<div class="container mt-4">
  <div class="p-4 bg-white rounded shadow-sm">
    <h3 class="mb-3">Über uns</h3>
    <p>
      Wir sind zwei Wirtschaftsinformatik-Studentinnen, die ihre Leidenschaft für Fotografie und moderne Bildgestaltung
      in ein eigenes Projekt verwandelt haben. Mit PosterShop bauen wir uns Schritt für Schritt etwas Eigenes auf –
      authentisch, kreativ und mit dem Anspruch, hochwertige Motive für jeden Raum zugänglich zu machen.
    </p>
    <p>
      Unser Ziel ist es, Technik und Ästhetik zu verbinden: klare Prozesse, faire Preise und Bilder, die Emotionen wecken.
      Schön, dass du hier bist und unsere Reise ein Stück begleitest.
    </p>
  </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Warenkorb-Badge
function updateCartBadge() {
  fetch("backend/cartController.php?action=count&ts=" + Date.now())
    .then(res => res.json())
    .then(data => {
      const badge = document.getElementById("cartBadge");
      if (badge) badge.innerText = data.count;
    });
}
window.addEventListener("pageshow", updateCartBadge);

// Online-Status
function updateOnlineBadge() {
  fetch("backend/status/onlineHeartbeat.php?ts=" + Date.now(), {
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