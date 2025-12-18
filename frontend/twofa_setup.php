<?php
session_start();
if(!isset($_SESSION['user_id']))
{
    header("Location: viewlogin.php");
    exit;
}

require_once("../db.php");


// User laden
$stmt=$pdo->prepare("SELECT email, twofacode FROM customer WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$user=$stmt->fetch();

if(!$user){
    header("Location: viewaccount.php");
    exit;
}

$email=$user['email'];
$existingSecret=$user['twofacode'];

// Wenn bereits ein Secret existiert zurück, sonst Setup anzeigen!
if (!empty($existingSecret)){
    header("Location: viewaccount.php?twofa=already");
    exit;
}

// Noch kein Secret -> QR anzeigen
require_once("../backend/PHPGangsta/GoogleAuthenticator.php");
$ga=new PHPGangsta_GoogleAuthenticator();
$secret=$ga->createSecret();
$_SESSION['pending_2fa_secret']=$secret;
$qrUrl=$ga->getQRCodeGoogleUrl($email,$secret,'PosterShop');

// Wenn noch kein Secret vorhanden -> neues erzeugen (wird zunächst NICHT gespeichert)
//$secret=$existingSecret ?: $ga->createSecret();

//QR-Code URL erzeugen (ISSUER-Name frei wählbar)
//$qrUrl=$ga->getQRCodeGoogleUrl($email, $secret, 'PosterShop');

//CSRF-Token für das Formular
if(empty($_SESSION['csrf'])){
    $_SESSION['csrf']=bin2hex(random_bytes(32));
}
$csrf=$_SESSION['csrf'];
?>

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>2FA einrichten</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

    </head>

    <body class="container py-4">
        <h2 class="h4 mb-3">Zwei-Faktor-Authentisierung (2FA) einrichten</h2>

        <p class="text-muted">1. Öffne Google Authenticator und scanne diesen QR-Code:</p>
        <!-- QR-Cpde als Bild einbinden -->
        <img
            src="<?= htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8')?>"
            alt="2FA QR"
            class="img-fluid mb-3"
        />

        <p>2. Gib einen aktuellen 6-stelligen Code aus der App ein, um die Einrichtung zu bestätigen:</p>

        <form method="post" id="twofasetupform" action="../backend/twofa_verify.php" class="form-horizontal" role="form">
            <div class="col-md-4">
                <label class="form-label" for="code">2FA Code</label>
                <input type="text" class="form-control" id="code" name="code" maxlength="6" inputmode="numeric" pattern="^[0-9]{6}$" required>
            </div>

            <!--Secret wird nur im Rahmen der Bestätigung übergeben; erst bei Erfolg wird es gespeichert-->
            <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8')?>">

            <div class="col-12">
                <button type="submit" class="btn btn-primary">2FA aktivieren</button>
            </div>
        </form>
        <a href="viewlogin.php">Zum Login</a>

        <?php if(!empty($_GET['error'])):?>
            <div class="alert alert-danger mt-3">
                <?=
                    $_GET['error']==='invalid'?'Der eingegebene Code ist ungültig.'
                    :($_GET['error']==='csrf' ? 'Ungültiges Formular(CSRF).'
                    :'Fehler bei der Aktivierung.')
                ?>
            </div>
        <?php endif; ?>
    </body>
</html>    