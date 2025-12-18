<?php
session_start();
if(!isset($_SESSION['customer_id'])){
    header("Location: viewlogin.php");
    exit;
}

if(empty($_SESSION['csrf'])){
    $_SESSION['csrf']=bin2hex(random_bytes(32));
}
$csrf=$_SESSION['csrf'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Erstes Login – Passwort ändern</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6">

            <div class="card shadow-sm">
                <div class="card-body p-4">

                    <h2 class="h4 mb-3 text-center">Neues Passwort festlegen</h2>

                    <?php if (!empty($_GET['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_GET['error']==='csrf'
                                ? 'Ungültiges Formular (CSRF).'
                                : ($_GET['error']==='invalid'
                                    ? 'Passwörter ungültig oder stimmen nicht.'
                                    : 'Serverfehler.') ?>
                        </div>
                    <?php endif; ?>

                    <form method="post"
                          action="../backend/first_login_save.php"
                          onsubmit="return checkPW();"
                          novalidate>

                        <div class="mb-3">
                            <label for="current_pw" class="form-label">Startpasswort</label>
                            <input type="password" id="current_pw" name="current_pw" class="form-control" required>
                            <div class="form-text">
                                Passwort aus der Registrierungs-E-Mail.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="pw1" class="form-label">Neues Passwort</label>
                            <input type="password" id="pw1" name="pw1" class="form-control" required>
                            <div class="form-text">
                                Mindestens 9 Zeichen, Groß-/Kleinbuchstaben und Zahl.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="pw2" class="form-label">Passwort wiederholen</label>
                            <input type="password" id="pw2" name="pw2" class="form-control" required>
                        </div>

                        <input type="hidden" name="csrf"
                               value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Passwort speichern
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
function checkPW(){
    const p1 = document.getElementById('pw1').value;
    const p2 = document.getElementById('pw2').value;
    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{9,}$/;

    if(!regex.test(p1)) {
        alert('Passwort muss mindestens 9 Zeichen, Groß-/Kleinbuchstaben und Zahl enthalten.');
        return false;
    }
    if(p1 !== p2){
        alert('Passwörter stimmen nicht überein.');
        return false;
    }
    return true;
}
</script>

</body>
</html>
