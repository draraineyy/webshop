<?php
session_start();

// Status & Daten für Partials
$isLoggedIn = isset($_SESSION['user_id']);
$username   = $_SESSION['username'] ?? '';
$lastSeenTs = $_SESSION['time']     ?? null;

// Optional: Punkte nur laden, wenn eingeloggt (für Navbar)
if ($isLoggedIn) {
  require_once __DIR__ . "../db.php"; 
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

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Eigene CSS -->
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

 <?php
    // Partials einbinden
    // Hinweis: Pfade relativ zur index.php – passe sie an, falls deine Struktur abweicht.
    include __DIR__ . "/frontend/partials/navbar.php";
    include __DIR__ . "/frontend/partials/greeting.php";
  ?>

  
  <!-- Logout-Meldung -->
  <div id="logoutMessage"></div>

  <script>
    // URL-Parameter auslesen
    const params = new URLSearchParams(window.location.search);

    // Prüfen, ob logout=1 gesetzt ist
    if (params.has("logout")) {
      document.getElementById("logoutMessage").innerHTML =
        "<p style='color:green'>Sie haben sich erfolgreich abgemeldet.</p>";
    }
  </script>

  
<!-- Bootstrap Carousel (Fade) -->
<div class="container mt-4">
  <div id="posterCarousel"
       class="carousel slide carousel-fade"
       data-bs-ride="carousel"
       data-bs-interval="3000">

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

    <button class="carousel-control-prev" type="button"
            data-bs-target="#posterCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Zurück</span>
    </button>

    <button class="carousel-control-next" type="button"
            data-bs-target="#posterCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Weiter</span>
    </button>

  </div>
</div>


  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Eigene JS -->
  <script src="js/main.js"></script>



  <!-- Carousel automatisch starten -->
  <script>
    
  window.addEventListener("load", function () {
    const carouselElement = document.querySelector('#posterCarousel');

    new bootstrap.Carousel(carouselElement, {
      interval: 3000,
      ride: 'carousel',
      wrap: true,
      touch: true
    });
  });



    //Badge aktualisieren
     function updateCartBadge() {
    fetch("backend/cartController.php?action=count")
      .then(res => res.json())
      .then(data => {
        document.getElementById("cartBadge").innerText = data.count;
      })
      .catch(err => console.error("Fehler beim Laden des Warenkorbs:", err));
  }

  window.addEventListener("pageshow", updateCartBadge);


  //ONLINE USER STATUS

  const ONLINE_POLL_INTERVAL = 20000; // alle 20 Sekunden

  function updateOnlineBadge() {
    fetch("backend/status/onlineHeartbeat.php?ts=" + Date.now())  //AJAX Request an Heartbeat-Endpunkt im backend wie viele User aktuell in der Tabelle online_status stehen
      .then(res => res.json()) //// Antwort als JSON parsen
      .then(data => {
        const el = document.getElementById("onlineBadge"); //DOM-Element mit der ID "onlineBadge" holen
        if (el && data.loggedIn) { //// Nur wenn das Element existiert UND der User eingeloggt ist
          el.innerText = data.count;  //die Zahl der Online‑User aufs Badge setzen
        }
      })
      .catch(err => console.error("Fehler beim Laden des Online-Status:", err));
  }

  // beim Laden und regelmäßig
  window.addEventListener("pageshow", updateOnlineBadge);
  setInterval(updateOnlineBadge, ONLINE_POLL_INTERVAL);  //Funktion ruft alle 20s erneut auf


  </script>
</body>
</html>