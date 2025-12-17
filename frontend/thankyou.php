<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>PosterShop - Danke</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="bg-light">

<div class="container mt-4">
  <!-- Überschrift -->
  <h2 class="mb-4">Vielen Dank für Ihre Bestellung!</h2>

  <!-- Bestellnummer -->
  <?php $order = $_GET['order'] ?? null; ?>
  <div class="card shadow-sm p-4">
    <?php if ($order): ?>
      <p class="mb-3">Ihre Bestellnummer lautet: <strong><?php echo htmlspecialchars($order); ?></strong></p>
    <?php else: ?>
      <p class="mb-3">Bestellnummer nicht gefunden.</p>
    <?php endif; ?>

    <!-- Buttons -->
    <div class="d-flex justify-content-between">
      <a href="viewproducts.php" class="btn btn-outline-secondary">Weiter einkaufen</a>
      <a href="viewcart.php" class="btn btn-success">Warenkorb ansehen</a>
    </div>
  </div>
</div>

</body>
</html>