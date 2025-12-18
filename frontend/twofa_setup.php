<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>2FA einrichten</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6">

            <div class="card shadow-sm p-4 text-center">
                <h2 class="h5 mb-4">Zwei-Faktor-Authentisierung (2FA) einrichten</h2>

                <p class="text-muted text-start">1. Öffne Google Authenticator und scanne diesen QR-Code:</p>
                <img
                    src="<?= htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8')?>"
                    alt="2FA QR"
                    class="img-fluid mb-3"
                />

                <p class="text-start">2. Gib einen aktuellen 6-stelligen Code aus der App ein, um die Einrichtung zu bestätigen:</p>

                <form method="post" id="twofasetupform" action="../backend/twofa_verify.php" class="text-start">
                    <div class="mb-3">
                        <label class="form-label" for="code">2FA Code</label>
                        <input type="text" class="form-control" id="code" name="code" maxlength="6" inputmode="numeric" pattern="^[0-9]{6}$" required>
                    </div>

                    <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8')?>">

                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary">2FA aktivieren</button>
                    </div>
                </form>

                <a href="viewlogin.php" class="d-block mb-3">Zum Login</a>

                <?php if(!empty($_GET['error'])):?>
                    <div class="alert alert-danger mt-3 text-start">
                        <?= $_GET['error']==='invalid'?'Der eingegebene Code ist ungültig.'
                            :($_GET['error']==='csrf' ? 'Ungültiges Formular (CSRF).'
                            :'Fehler bei der Aktivierung.') ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

</body>
</html>
