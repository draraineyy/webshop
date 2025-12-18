<?php
// Sitzung starten, damit wir auf Session-Variablen zugreifen können
session_start();

// DB-Verbindung einbinden
require_once("../db.php");

// Sicherheitscheck: Nur eingeloggte User dürfen hier rein
if (!isset($_SESSION['customer_id'])) {
    header("Location: viewlogin.php"); // Redirect zur Login-Seite
    exit; // Script beenden
}

// Variablen für Partials vorbereiten
$isLoggedIn = true; // Wir wissen: User ist eingeloggt
$username   = $_SESSION['username'] ?? ''; // Name/Email aus Session
$lastSeenTs = $_SESSION['time']     ?? null; // Zeitpunkt des letzten Logins

// Punkte aus der Datenbank laden
$stmt = $pdo->prepare("SELECT COALESCE(SUM(points),0) FROM points WHERE customer_id=?");
$stmt->execute([$_SESSION['customer_id']]);
$totalPoints = (int)$stmt->fetchColumn(); // Ergebnis als Integer
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>PosterShop - Account</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome für Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Eigenes CSS -->
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

  <!-- Navbar direkt eingebaut -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <!-- Logo -->
    <a class="navbar-brand" href="../index.php">PosterShop</a>

    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <!-- Link zur Artikelübersicht -->
        <li class="nav-item">
          <a class="nav-link" href="viewproducts.php">Artikelübersicht</a>
        </li>

        <!-- Punkteanzeige -->
        <li class="nav-item">
          <span class="nav-link">Punkte: <?php echo $totalPoints; ?></span>
        </li>

        <!-- Warenkorb mit Badge -->
        <li class="nav-item">
          <a class="nav-link" href="viewcart.php">
            <i class="fa-solid fa-cart-shopping"></i>
            <span id="cartBadge" class="badge bg-danger">0</span>
          </a>
        </li>

        <!-- Online-User Anzeige -->
        <li class="nav-item">
          <a class="nav-link" href="#">
            <i class="fa-solid fa-user-group"></i>
            <span id="onlineBadge" class="badge bg-info">0</span>
          </a>
        </li>

        <!-- Logout -->
        <li class="nav-item">
          <a class="nav-link" href="logout.php">Abmelden</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<?php 

    // Begrüßung einbinden (zeigt personalisierte Nachricht mit Name und letztem Login)
    include __DIR__ . "/partials/greeting.php"; 
  ?>

  <!-- Carousel mit 3 Bildern -->
  <div class="container mt-4">
    <div id="posterCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="3000">
      <div class="carousel-inner">
        <!-- Erstes Bild aktiv -->
        <div class="carousel-item active">
          <img src="../images/tropen.png" class="d-block w-100" alt="Poster 1">
        </div>
        <!-- Zweites Bild -->
        <div class="carousel-item">
          <img src="../images/skyline.png" class="d-block w-100" alt="Poster 2">
        </div>
        <!-- Drittes Bild -->
        <div class="carousel-item">
          <img src="../images/alpen.png" class="d-block w-100" alt="Poster 3">
        </div>
      </div>

      <!-- Steuerung: Zurück -->
      <button class="carousel-control-prev" type="button" data-bs-target="#posterCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Zurück</span>
      </button>

      <!-- Steuerung: Weiter -->
      <button class="carousel-control-next" type="button" data-bs-target="#posterCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Weiter</span>
      </button>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Eigenes JS -->
  <script src="js/cart.js"></script>

  <script>
    // Warenkorb-Badge aktualisieren
    function updateCartBadge() {
      fetch("../backend/cartController.php?action=count") // AJAX-Request an Controller
        .then(res => res.json()) // Antwort als JSON parsen
        .then(data => {
          const el = document.getElementById("cartBadge"); // Badge-Element holen
          if (el) el.innerText = data.count; // Zahl setzen
        })
        .catch(err => console.error("Fehler beim Laden des Warenkorbs:", err));
    }
    // Beim Anzeigen der Seite sofort laden
    window.addEventListener("pageshow", updateCartBadge);

    // Online-Status aktualisieren
    const ONLINE_POLL_INTERVAL = 20000; // alle 20 Sekunden
    function updateOnlineBadge() {
      fetch("/webshop/backend/status/onlineHeartbeat.php?ts=" + Date.now()) // Heartbeat-Endpunkt aufrufen
        .then(res => res.json()) // Antwort als JSON parsen
        .then(data => {
          const el = document.getElementById("onlineBadge"); // Badge-Element holen
          if (el && data.loggedIn) el.innerText = data.count; // Zahl setzen
        })
        .catch(err => console.error("Fehler beim Laden des Online-Status:", err));
    }
    // Beim Anzeigen der Seite sofort laden
    window.addEventListener("pageshow", updateOnlineBadge);
    // Alle 20 Sekunden erneut laden
    setInterval(updateOnlineBadge, ONLINE_POLL_INTERVAL);
  </script>

</body>
</html>