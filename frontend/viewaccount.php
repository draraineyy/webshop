<?php
session_start(); // Sitzung starten, damit wir auf Session-Variablen zugreifen können

// Prüfen, ob User eingeloggt ist
// Wenn keine user_id in der Session vorhanden ist → Redirect zurück zur viewlogin
if (!isset($_SESSION['user_id'])) {
    header("Location: viewlogin.php"); // Redirect zur Login-Seite
    exit; // Script beenden, damit kein weiterer Code ausgeführt wird
}

// Begrüßung und Basis-Infos aus der Session ausgeben
echo "<h2>Welcome to your account!</h2>"; // Überschrift
echo "<p>User-ID: " . htmlspecialchars($_SESSION['user_id']) . "</p>"; // User-ID aus Session
echo "<p>Email: " . htmlspecialchars($_SESSION['username'] ?? '') . "</p>"; // Email aus Session
echo "<p>Login time: " . date("d.m.Y H:i:s", $_SESSION['time'] ?? time()) . "</p>"; // Zeitpunkt des Logins

// Datenbank einbinden, um Punkte zu laden
require_once("../db.php"); // DB-Verbindung herstellen

// Punkte summieren für den eingeloggten User
$stmt = $pdo->prepare("SELECT COALESCE(SUM(points),0) AS total FROM points WHERE customer_id=?");
$stmt->execute([$_SESSION['user_id']]);
$totalPoints = (int)$stmt->fetchColumn();

// Punkte ausgeben
echo "<p>Total points: " . $totalPoints . "</p>";

// Erfolgsmeldungen anzeigen, wenn Redirect mit GET-Parametern erfolgt ist
if (isset($_GET['changed'])) {
    echo "<p style='color:green'>Password successfully changed.</p>"; // Meldung nach Passwortänderung
}
if (isset($_GET['registered'])) {
    echo "<p style='color:green'>Registration successful.</p>"; // Meldung nach Registrierung
}

// Weiterleitung zu logout 
echo '<p><a href="logout.php">Logout</a></p>'; // Link zum Logout-Skript
?>