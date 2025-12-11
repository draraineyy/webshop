
<?php
ob_start();                // Outputs buffern, weil sonst redirect nicht ausgeführt werden kann - 
                            // sorgt dafür, dass alles, was später ausgegeben wird (z. B. echo oder versehentliches Leerzeichen), erst in einen Puffer geschrieben wird


ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once("../db.php"); // DB-Verbindung

$email = $_POST["email"];
$password = $_POST["password"]; // Klartext kommt an
$code = $_POST["code"];

// Passwort-Hash erzeugen
$hash = hash("sha512", $password);

// User aus DB holen
$stmt = $pdo->prepare("SELECT * FROM customer WHERE email=? AND password_hash=?");
$stmt->execute([$email, $hash]);
$user = $stmt->fetch();

if ($user) {
    // 2FA prüfen
    require_once("PHPGangsta/GoogleAuthenticator.php");
    $ga = new PHPGangsta_GoogleAuthenticator();
    $checkResult = $ga->verifyCode($user["twofacode"], $code, 2);

    if ($checkResult) {
        $_SESSION["user_id"] = $user["id"];


        // Punkte +5 für Login
        $stmt = $pdo->prepare("INSERT INTO points (customer_id, activity, points, date) VALUES (?, 'Login', 5, NOW())");
        $stmt->execute([$user["id"]]);

        // Logs schreiben
        $os = php_uname("s");
        $resolution = $_POST["resolution"] ?? "unknown";
        $stmt = $pdo->prepare("INSERT INTO logs (customer_id, login_date, operating_system, aufloesung) VALUES (?, NOW(), ?, ?)");
        $stmt->execute([$user["id"], $os, $resolution]);

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
