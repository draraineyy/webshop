
<?php
ob_start();                // Outputs buffern, weil sonst redirect nicht ausgeführt werden kann - 
                            // sorgt dafür, dass alles, was später ausgegeben wird (z. B. echo oder versehentliches Leerzeichen), erst in einen Puffer geschrieben wird


ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once("../db.php"); // DB-Verbindung



$email = $_POST["email"];
$passwordHash = $_POST["password_hash"]; // Hash kommt an
$code = $_POST["code"];

var_dump($email, $passwordHash);
$stmt = $pdo->prepare("SELECT * FROM customer WHERE email=? AND password_hash=?");
$stmt->execute([$email, $passwordHash]);
$user = $stmt->fetch();

if (!$user) {
    die("User nicht gefunden. Prüfe Email + Hash!");
}

// User aus DB holen
$stmt = $pdo->prepare("SELECT * FROM customer WHERE email=? AND password_hash=?");
$stmt->execute([$email, $passwordHash]);
$user = $stmt->fetch();

if ($user) {
    // 2FA prüfen
<<<<<<< HEAD
    require_once "PHPGangsta/GoogleAuthenticator.php";
=======
    require_once("PHPGangsta/GoogleAuthenticator.php");
>>>>>>> f017d360b1bc19a7326f3c87d7c3d898ef93dc7c
    $ga = new PHPGangsta_GoogleAuthenticator();
    $checkResult = $ga->verifyCode($user["twofacode"], $code, 2);

    if ($checkResult) {
        $_SESSION["user_id"] = $user["id"];     // Primärschlüssel aus Tabelle
        $_SESSION["username"] = $user["email"]; // Email oder Name, damit User im Frontend begrüßt wird
        $_SESSION["time"] = time();             // Zeitpunkt des Logins für "Letzte Login" Anzeige



        // Punkte +5 für Login
        $stmt = $pdo->prepare("INSERT INTO points (customer_id, activity, points, date) VALUES (?, 'Login', 5, NOW())");
        $stmt->execute([$user["id"]]);

        // Logs schreiben
        $os = php_uname("s");
        $resolution = $_POST["resolution"] ?? "unknown";
        $stmt = $pdo->prepare("INSERT INTO logs (customer_id, login_date, operating_system, aufloesung) VALUES (?, NOW(), ?, ?)");
        $stmt->execute([$user["id"], $os, $resolution]);


         // Erweiterung: First-Login prüfen
        if (!empty($user['must_change_password']) && (int)$user['must_change_password'] === 1) {  // if bedingung: 1. Feld ist nicht leer && 2. Wert ist exakt 1
            header("Location: ../frontend/first_login.php");
            exit;
        }


        // Redirect ins Benutzerkonto
                header("Location: ../frontend/account.php");
                exit;
            } else {
                header("Location: ../frontend/viewlogin.php?error=2fa");
                exit;
            }
        } else {
            header("Location: ../frontend/viewlogin.php?error=login");
            exit;
        }
