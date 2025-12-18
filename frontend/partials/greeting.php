<?php
// Erwartet: $isLoggedIn (bool), $username (string|null), $lastSeenTs (int|null)
?>
<div class="container mt-4">
  <?php if (!empty($isLoggedIn) && !empty($username)): ?>
    <h2>Herzlich Willkommen <?php echo htmlspecialchars($username); ?>.</h2>
    <?php if (!empty($lastSeenTs)): ?>
      <p>Sie waren zuletzt am <?php echo date("d.m.Y H:i:s", (int)$lastSeenTs); ?> online.</p>
    <?php endif; ?>
  <?php else: ?>
    <h2>Willkommen, Besucher!</h2>
  <?php endif; ?>
</div>