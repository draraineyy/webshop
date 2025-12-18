<?php

session_start();
if(!isset($_SESSION['customer_id'])){
    header("Location: viewlogin.php");
    exit;
}
// CSRF-Token einmalig erzeugen (nur wenn nicht vorhanden)
if(empty($_SESSION['csrf'])){
    $_SESSION['csrf']=bin2hex(random_bytes(32));
}
$csrf=$_SESSION['csrf'];
?>
<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>Erstes Login - Passwort ändern</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

    </head>
    <body class="container py-4">
        <h2 class="h4 mb-3">Neues Passwort festlegen</h2>

        <?php
        if (!empty($_GET['error'])):?>
            <div class="alert alert-danger">
                <?= $_GET['error']==='csrf'?'Ungültiges Formular (CSRF). Bitte erneut absenden.':
                ($_GET['error']==='invalid'?'Passwörter stimmen nicht oder erfüllen die Regeln nicht.':
                ($_GET['error']==='server'?'Serverfehler. Bitte später erneut versuchen.':'Fehler.'))?>
            </div>
        <?php endif; ?>

        <form method="post" id="firstloginform" action="../backend/first_login_save.php" class="form-horizontal" role="form" onsubmit="checkPW()">
            <!--Startpasswort (Wurde zugeschickt) -->
            <div class="mb-3">
                <label for="current_pw" class="form-label">Startpasswort</label>
                <input type="password" id="current_pw" name="current_pw" class="form-control" required>
                <div class="form-text">Geben Sie das Passwort ein, das Sie bei der Registrierung erhalten haben.</div>
            </div>

            <!-- Neues Passwort -->
            <div class="mb-3">
                <label for="pw1" class="form-label">Neues Passwort</label>
                <input type="password" id="pw1" name="pw1" class="form-control" required>
                <div class="form-text">Mindestens 9 Zeiche, Groß-/Kleinbuchstaben und Zahl.</div>
            </div>
            
            <!-- Wiederholung -->
            <div class="mb-3">
                <label for="pw2" class="form-label">Neues Passwort (Wiederholung)</label>
                <input type="password" id="pw2" name="pw2" class="form-control" required><br>
            </div>

            <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

            <button type="submit" class="btn btn-primary">Speichern</button>
    </form>

    <script>
        function checkPW(){
            const p1=documet.getElementById('pw1').value;
            const p2=document.getElementById('pw2').value;
            const regex=/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{9,}$/;
            if(!regex.test(p1)) {
                alert('Passwort muss mindestens 9 Zeichen, Groß-/Kleinbuchstaben und Zahl enthalten.');
                return false;
            }
            if(p1!==p2){
                alert('Passwörter stimmen nicht überein.');
                return false;
            }
            return true;
        }
    </script>
    </body>
</html>