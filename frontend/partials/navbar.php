<?php
// Erwartet: $isLoggedIn (bool), optional $totalPoints (int)
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">PosterShop</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">

        <!-- Artikelübersicht -->
        <li class="nav-item">
          <a class="nav-link" href="frontend/viewproducts.php">Artikelübersicht</a>
        </li>

        <?php if (!empty($isLoggedIn)): ?>
          <!-- Punkteanzeige (nur eingeloggte User) -->
          <li class="nav-item">
            <span class="nav-link">
              Punkte: <?php echo isset($totalPoints) ? (int)$totalPoints : 0; ?>
            </span>
          </li>

          <!-- Warenkorb mit Badge -->
          <li class="nav-item">
            <a class="nav-link" href="frontend/viewcart.php">
              <i class="fa-solid fa-cart-shopping"></i>
              <span id="cartBadge" class="badge bg-danger">0</span>
            </a>
          </li>

          <!-- Online-User Anzeige (nur eingeloggte User) -->
          <li class="nav-item">
            <a class="nav-link" href="#">
              <i class="fa-solid fa-user-group"></i>
              <span id="onlineBadge" class="badge bg-info">0</span>
            </a>
          </li>

          <!-- Logout -->
          <li class="nav-item">
            <a class="nav-link" href="frontend/logout.php">Abmelden</a>
          </li>
        <?php else: ?>
          <!-- Warenkorb auch für Gäste sichtbar -->
          <li class="nav-item">
            <a class="nav-link" href="frontend/viewcart.php">
              <i class="fa-solid fa-cart-shopping"></i>
              <span id="cartBadge" class="badge bg-danger">0</span>
            </a>
          </li>

          <!-- Login/Registrierung für Gäste -->
          <li class="nav-item">
            <a class="nav-link" href="frontend/viewlogin.php">Anmelden</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="frontend/register.php">Registrieren</a>
          </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>