
<?php
// backend/loyaltyController.php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once("../db.php");

// Prüfen, ob eingeloggt
$customerId = $_SESSION['customer_id'] ?? null;
if (!$customerId) {
    http_response_code(401);
    echo json_encode(['error' => 'not_authenticated', 'points' => 0]);
    exit;
}

try {
    // Annahme: Punkte werden in Tabelle `points` verwaltet (positive/negative Buchungen)
    // Kontostand = SUM(points) für den Kunden
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(points), 0) AS balance FROM points WHERE customer_id = ?");
    $stmt->execute([$customerId]);
    $points = (int)($stmt->fetchColumn() ?? 0);

    echo json_encode(['points' => $points]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db_error', 'message' => $e->getMessage()]);
}
