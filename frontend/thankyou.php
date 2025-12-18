<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>PosterShop - Danke</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../public/css/style.css">
</head>

<body class="bg-light">

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-8 col-lg-6">

      <div class="card shadow-sm p-4 text-center">
        <h2 class="mb-4">Vielen Dank für Ihre Bestellung!</h2>

        <?php $order = $_GET['order'] ?? null; ?>
        <?php if ($order): ?>
          <p class="mb-4">
            Ihre Bestellnummer lautet:<br>
            <strong><?= htmlspecialchars($order) ?></strong>
          </p>
        <?php else: ?>
          <p class="mb-4 text-danger">Bestellnummer nicht gefunden.</p>
        <?php endif; ?>

        <div class="d-grid gap-2 d-sm-flex justify-content-sm-between">
          <a href="viewproducts.php" class="btn btn-outline-secondary">
            Weiter einkaufen
          </a>
          <a href="viewcart.php" class="btn btn-success">
            Warenkorb ansehen
          </a>
        </div>

      </div>

    </div>
  </div>
</div>

</body>
</html>
