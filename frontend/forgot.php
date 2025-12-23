<?php
session_start();
if(empty($_SESSION['csrf'])){
    $_SESSION['csrf']=bin2hex(random_bytes(32));
}
$csrf=$_SESSION['csrf'];
?>

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>Passwort vergessen</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-10 col-md-8 col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h2 class="mb-3 text-center">Passwort zurücksetzen</h2>
                            <?php if (!empty($_GET['ok'])):?>
                                <div class="alert alert-success">
                                    Wenn diese E-Mail existiert, wurde ein temporäres Passwort gesendet.
                                </div>
                            <?php elseif(!empty($_GET['error'])):?>
                                <div class="alert alert-danger">
                                    <?php
                                    switch($_GET['error']){
                                        case 'csrf': echo "Ungültiges Formular (CSRF).";
                                        break;
                                        case 'server': echo "Unerwarteter Serverfehler.";
                                        break;
                                        default: echo "Fehler beim Zurücksetzen.";
                                    }
                                    ?>
                                </div>
                            <?php endif;?>
                            <form method="post" id="forgotPasswordForm" action="../backend/forgot_send.php" class="text-start">
                                <div class="mb-3">
                                    <label class="form-label" for="email">E-Mail (Benutzername)</label>
                                    <input class="form-control" type="email" id="email" name="email" required>
                                </div>
                                <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
                                <div class="d-grid">
                                    <button class="btn btn-primary btn-lg" type="submit">Temporäres Passwort senden</button>
                                </div>
                            </form>
                            <div class="text-center mt-3">
                                <a href="viewlogin.php">Zurück zum Login</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>