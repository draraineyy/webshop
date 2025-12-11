<?php
// Session initialisieren
session_start();

require_once("../db.php");
require_once("includes/functions.php");

// CSRF prüfen
if(empty($_POST['csrf'])||empty($_SESSION['csrf'])||!hash_equals($_SESSION['csrf'], $_POST['csrf'])){
    header("Location: ../frontend/register.php?error=invalid");
    exit;
}

// Eingaben einsammeln & prüfen
$name=trim($_POST['name'] ?? '');
$email=trim($_POST['email'] ?? '');

if(strlen($name)<2||strlen($email)<5 ||strpos($email, '@')===false){
    header("Location: ../frontend/register.php?error=invalid");
    exit;
}

// E-Mail Duplikat prüfen
try{
    $stmt=$pdo->prepare("SELECT id FROM customer WHERE email=?");
    $stmt->execute([$email]);
    if($stmt->fetch()){
        header("Location: ../frontend/register.php?error=exists");
        exit;
    }
} catch (Throwable $e){
    header("Location: ../frontend/register.php?error=server");
    exit;
}

// Zufallspasswort generieren (10-12 Zeichen, Buchstaben+Zahlen)
function generateRandomPassword(int $length = 12): string{
    $alphabet='ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789'; // ohne leicht verwechselbare Zeichen
    $bytes=random_bytes($length);
    $out='';
    for($i=0;$i<$length;$i++){
        $out .=$alphabet[ord($bytes[$i])% strlen($alphabet)];
    }
    return $out;
}

$plainPassword=generateRandomPassword(12);

// SHA-512 Hash erzeugen
$passwordHash = hash('sha512', $plainPassword);

// User anlegen (must_change_password=1, twofacode erst später setzen)
try{
    $pdo->beginTransaction();

    $stmt=$pdo->prepare("INSERT INTO customer (name, email, password_hash, must_change_password, twofacode, created_at)VALUES(?, ?, ?, 1, '', NOW())");
    $stmt->execute([$name, $email, $passwordHash]);
    $customerId=(int)$pdo->lastInsertId();

    // +250 Punkte für Registrierung
    $stmt = $pdo->prepare("INSERT INTO points (customer_id, activity, points, date) VALUES (?, 'Registration', 250, NOW())");
    $stmt->execute([$customerId]);

    $pdo->commit();
// } catch(Throwable $e) {
//    if($pdo->inTransaction()) $pdo->rollBack();
//    header("Location: ../frontend/register.php?error=server");
//    exit;
//}

    // E-Mail versenden
    $subject='Dein PosterShop Startpasswort';
    $body="
        <p>Hallo {$name},</p>
        <p>Vielen Dank für deine Registrierung beim <strong>PosterShop</strong>.</p>
        <p>Dein einmaliges Startpasswort lautet: <code>{$plainPassword}</code></p>
        <p>Bitte melde dich an und ändere es beim ersten Login.</p>
        ";

    if(sendEmail($email, $subject, $body)){
        $_SESSION['registration_email'] = [
            'subject' => $subject,
            'body' => $body,
            'email' => $email,
            'password' => $plainPassword
        ];
        header("Location: ../frontend/register.php?ok=1");
        exit;   
    }
    else{
        $error='Die Registrierung war erfolgreich, aber es gab einen Fehler beim Senden der Bestätigungs E-Mail.';
    }
} catch(Throwable $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    header("Location: ../frontend/register.php?error=server");
    exit;
}