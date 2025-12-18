<?php
session_start();
require_once("../db.php"); // DB-Verbindung laden

// Wenn ein User eingeloggt war → aus online_status entfernen
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("DELETE FROM online_status WHERE customer_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// Alle Session-Variablen löschen
session_unset();

// Session komplett zerstören
session_destroy();

// Redirect zurück zur Startseite
header("Location: ../index.php");
exit;