<?php
session_start();
require_once("../../db.php");

// Nur eingeloggte User berücksichtigen
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(["count" => 0, "loggedIn" => false]);
    exit;
}

// Eigenen Heartbeat aktualisieren
$stmt = $pdo->prepare("REPLACE INTO online_status (customer_id, last_seen) VALUES (?, NOW())");
$stmt->execute([$_SESSION['customer_id']]);

// Alte Einträge löschen (Timeout 60 Sekunden)
$pdo->query("DELETE FROM online_status WHERE last_seen < (NOW() - INTERVAL 60 SECOND)");

// Anzahl der aktuell Online‑User holen
$stmt = $pdo->query("SELECT COUNT(*) FROM online_status");
$count = (int)$stmt->fetchColumn();

echo json_encode(["count" => $count, "loggedIn" => true]);