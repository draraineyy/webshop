<!-- Damit Login funktioniert, muss:
- Hash mit der Datenbank vergleicht (kunden.passwort).
- Den 2FA-Code mit dem Secret prüft (Google Authenticator).
- Bei Erfolg: Session starten, Punkte +5 vergeben, Logs schreiben.
- Bei Fehler: Meldung „Login fehlgeschlagen“ oder „Ungültiger 2FA-Code“. -!>


<?php
session_start();
include("../data/db.php"); // Database connection
require_once("../vendor/autoload.php"); // Google Authenticator Library

use PHPGangsta_GoogleAuthenticator;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];
    $hash = $_POST["hashedPassword"];
    $code = $_POST["code"];

    // 1. Vergeleiche password hash mit datenbank (users.password)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? AND password=?");
    $stmt->execute([$email, $hash]);
    $user = $stmt->fetch();

    if ($user) {
        // 2. Verifiziere 2FA code mit secret
        $ga = new PHPGangsta_GoogleAuthenticator();
        $secret = $user["secret"]; // Secret must be stored in 'users' table
        $checkResult = $ga->verifyCode($secret, $code, 2); // 2 = tolerance in 30s steps

        if ($checkResult) {
            // 3. Starte session
            $_SESSION["user_id"] = $user["id"];

            // 4. Füge +5 punkte hinzu
            $stmt = $pdo->prepare("UPDATE users SET points = points + 5 WHERE id=?");
            $stmt->execute([$user["id"]]);

            // 5. Logs schreiben (OS, resolution, time)
            $os = php_uname("s"); // Operating system
            $resolution = $_POST["resolution"] ?? "unknown"; // Screen resolution (optional)
            $stmt = $pdo->prepare("INSERT INTO logs (user_id, os, resolution) VALUES (?, ?, ?)");
            $stmt->execute([$user["id"], $os, $resolution]);

            // zurückleiten zur homepage
            header("Location: ../frontend/index.html");
            exit;
        } else {
            // Error: ungülltiger 2FA code
            echo "Invalid 2FA code!";
        }
    } else {
        // Error: login fehlgeschlagen
        echo "Login failed!";
    }
}