<?php
ob_start();                // Outputs buffern, weil sonst redirect nicht ausgeführt werden kann - 
                            // sorgt dafür, dass alles, was später ausgegeben wird (z. B. echo oder versehentliches Leerzeichen), erst in einen Puffer geschrieben wird
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once("../db.php"); // DB-Verbindung

//Sicherheit: Exceptions bei PDO
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function redirect($url, $code=302){
    header("Location: $url", true, $code);
    exit;
}

//Eingaben
$email = $_POST["email"]??'';
$password=$_POST["password"]??'';
$code = $_POST["code"]??'';
$resolution=$_POST["resolution"]??'unknown';
$clientOS=$_POST["client_os"]??'Unknown';

//Minimal-Validierung
if(strlen($email)<5||strpos($email, '@')===false||strlen($password)<1){
    header("Location: ../frontend/viewlogin.php?error=login");
    exit;
}

//User nur nach E-Mail laden
$stmt = $pdo->prepare("SELECT id, email, password_hash, must_change_password, twofacode FROM customer WHERE LOWER(email)=LOWER(?)");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
//Passwort prüfen
if(!$user||!password_verify($password, $user['password_hash'])){
    header("Location: ../frontend/viewlogin.php?error=login");
    exit;
}

//2FA erzwingen, wenn Secret vorhanden
$twofaSecret=trim((string)($user["twofacode"]??''));

require_once(__DIR__ . "/PHPGangsta/GoogleAuthenticator.php");
$ga=new PHPGangsta_GoogleAuthenticator();

if($twofaSecret !== ''){
    //Secret existiert -> Code MUSS vorliegen und gültig sein
    if(!preg_match('/^\d{6}$/', $code)){
        redirect("../frontend/viewlogin.php?need2fa=1");
    }
    $isValidTOTP=$ga->verifyCode($twofaSecret, $code, 2);
    if(!$isValidTOTP){
        header("Location: ../frontend/viewlogin.php?error=2fa");
        exit;
    }
}

// Session setzen
$_SESSION["customer_id"]=$user["id"];
$_SESSION["username"]=$user["email"];
$_SESSION["time"]=time();
session_regenerate_id(true);

// User als online markieren
$stmt = $pdo->prepare("REPLACE INTO online_status (customer_id, last_seen) VALUES (?, NOW())");  // - REPLACE INTO sorgt dafür, dass entweder ein neuer Eintrag erstellt oder ein bestehender überschrieben wird (falls der User schon drinsteht). Und  NOW() setzt den aktuellen Zeitpunkt als last_seen.
$stmt->execute([$user["id"]]); 

// Punkte + Logs
$pdo->prepare("INSERT INTO points (customer_id, activity, points, date) VALUES(?, 'Login', 5, NOW())")
    ->execute([$user["id"]]);

$pdo->prepare("INSERT INTO logs (customer_id, login_date, operating_system, aufloesung) VALUES (?, NOW(), ?, ?)")
    ->execute([$user["id"], $clientOS, $resolution]);

// First-Login erzwingen
if(!empty($user['must_change_password']) && (int)$user['must_change_password']===1){
    $_SESSION["customer_id"]=$user["id"];
    $_SESSION["username"]=$user["email"];
    $_SESSION["time"]=time();
    session_regenerate_id(true);
    
    header("Location: ../frontend/first_login.php");
    exit;
}

//Noch kein TOTP eingerichtet -> zwinge ins Setup
if($twofaSecret==''){
    $_SESSION["pending_2fa_user_id"]=$user["id"];
    $_SESSION["pending_2fa_email"]=$user["email"];
    session_regenerate_id(true);
    header("Location: ../frontend/twofa_setup.php");
    exit;
}

// Weiter ins Konto
header("Location: ../frontend/viewaccount.php");
exit;
