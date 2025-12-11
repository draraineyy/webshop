<?php
// db.php - Zentrale Datenbankverbindung

$host = 'localhost';
$dbname = 'postershop';
$user = 'root';      // Standard XAMPP User
$password = '';      // Standard XAMPP Passwort (leer)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    
    // Fehler sollen als Exceptions geworfen werden
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Standard-Abrufmodus auf assoziatives Array setzen (z.B. $row['name'])
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Datenbank-Verbindungsfehler: " . $e->getMessage());
}
?>
