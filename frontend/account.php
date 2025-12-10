<?php
session_start();

// Prüfen, ob User eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    // Wenn nicht eingeloggt → zurück zur Login-Seite
    header("Location: login.php");
    exit;
}

// Beispiel-Ausgabe für eingeloggten User
echo "<h2>Willkommen im Benutzerkonto!</h2>";
echo "<p>User-ID: " . htmlspecialchars($_SESSION['user_id']) . "</p>";
echo "<p>Email: " . htmlspecialchars($_SESSION['username'] ?? '') . "</p>";
echo "<p>Login-Zeit: " . date("d.m.Y H:i:s", $_SESSION['time'] ?? time()) . "</p>";

// Optional: Punkte oder andere Daten laden
// Hier könntest du mit der DB arbeiten, z.B. Punkte anzeigen
// require_once("../db.php");
// $stmt = $pdo->prepare("SELECT SUM(points) FROM points WHERE customer_id=?");
// $stmt->execute([$_SESSION['user_id']]);
// $totalPoints = $stmt->fetchColumn();
// echo "<p>Gesamtpunkte: " . $totalPoints . "</p>";
?>