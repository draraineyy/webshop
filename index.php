<?php
session_start();

// Status & Daten für Partials
$isLoggedIn = isset($_SESSION['user_id']);
$username   = $_SESSION['username'] ?? '';
$lastSeenTs = $_SESSION['time']     ?? null;

// Optional: Punkte nur laden, wenn eingeloggt (für Navbar)
if ($isLoggedIn) {
  require_once __DIR__ . "/db.php"; 
  $stmt = $pdo->prepare("SELECT COALESCE(SUM(points),0) FROM points WHERE customer_id=?");
  $stmt->execute([$_SESSION['user_id']]);
  $totalPoints = (int)$stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>PosterShop - Startseite</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Eigene CSS -->
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">PosterShop</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" 
            aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item"><a class="nav-link" href="frontend/viewproducts.php">Artikelübersicht</a></li>
        <?php if ($isLoggedIn): ?>
          <li class="nav-item"><span class="nav-link">Punkte: <?= $totalPoints ?></span></li>
        <?php endif; ?>
        <li class="nav-item position-relative">
          <a class="nav-link" href="frontend/viewcart.php">
            <i class="fa-solid fa-cart-shopping"></i>
            <span id="cartBadge" class="badge bg-danger position-absolute top-0 start-100 translate-middle">0</span>
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

<!-- Carousel -->
<div class="container mt-4">
  <div id="posterCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="3000">
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

<script>
// Carousel initialisieren
window.addEventListener("load", function () {
  new bootstrap.Carousel(document.querySelector('#posterCarousel'), {
    interval: 3000,
    ride: 'carousel',
    wrap: true,
    touch: true
  });
});

// Badge aktualisieren
function updateCartBadge() {
  fetch("backend/cartController.php?action=count&ts=" + Date.now())
    .then(res => res.json())
    .then(data => {
      const badge = document.getElementById("cartBadge");
      if (badge) badge.innerText = data.count;
    })
    .catch(err => console.error("Fehler beim Laden des Warenkorbs:", err));
}
window.addEventListener("pageshow", updateCartBadge);

// Online User Status
const ONLINE_POLL_INTERVAL = 20000;
function updateOnlineBadge() {
  fetch("backend/status/onlineHeartbeat.php?ts=" + Date.now())
    .then(res => res.json())
    .then(data => {
      const el = document.getElementById("onlineBadge");
      if (el && data.loggedIn) el.innerText = data.count;
    })
    .catch(err => console.error("Fehler beim Laden des Online-Status:", err));
}
window.addEventListener("pageshow", updateOnlineBadge);
setInterval(updateOnlineBadge, ONLINE_POLL_INTERVAL);
</script>
</body>
</html>
