<?php
ob_start();                // Outputs buffern, weil sonst redirect nicht ausgeführt werden kann - 
                            // sorgt dafür, dass alles, was später ausgegeben wird (z. B. echo oder versehentliches Leerzeichen), erst in einen Puffer geschrieben wird
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once("../db.php"); // DB-Verbindung



$email = $_POST["email"]??'';
$password=$_POST["password"]??'';
//password_verify($password, $user['password_hash']);
$code = $_POST["code"]??'';
$resolution=$_POST["resolution"]??'unknown';
$clientOS=$_POST["client_os"]??'Unknown';

//Minimal-Validierung
if(strlen($email)<5||strpos($email, '@')===false||strlen($password)<1){
    header("Location: ../frontend/viewlogin.php?error=login");
    exit;
}

//User nur nach E-Mail laden
$stmt = $pdo->prepare("SELECT id, email, password_hash, must_change_password, twofacode FROM customer WHERE email=?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
//Passwort prüfen
if(!$user||!password_verify($password, $user['password_hash'])){
    header("Location: ../frontend/viewlogin.php?error=login");
    exit;
}

// First-Login erzwingen
if(!empty($user['must_change_password']) && (int)$user['must_change_password']===1){
    $_SESSION["user_id"]=$user["id"];
    $_SESSION["username"]=$user["email"];
    $_SESSION["time"]=time();
    session_regenerate_id(true);
    
    header("Location: ../frontend/first_login.php");
    exit;
}

//Zwangsweiterleitung: Hat der Nutzer KEIN 2FA-Secret?
$twofaSecret=$user["twofacode"]??'';
if(empty($twofaSecret)){
    //Noch kein TOTP eingerichtet -> zwinge ins Setup
    $_SESSION["pending_2fa_user_id"]=$user["id"];
    $_SESSION["pending_2fa_email"]=$user["email"];
    session_regenerate_id(true);
    header("Location: ../frontend/twofa_setup.php");
    exit;
}

// 2FA prüfen
require_once("PHPGangsta/GoogleAuthenticator.php");
$ga=new PHPGangsta_GoogleAuthenticator();
if(preg_match('/^\d{6}$/', $code)){
    $isValidTOTP=$ga->verifyCode($twofaSecret, $code, 2);
    if(!$isValidTOTP){
        header("Location: ../frontend/viewlogin.php?error=2fa");
        exit;
    }
} else{
    $_SESSION["pending_2fa_user_id"]=$user["id"];
    $_SESSION["pending_2fa_email"]=$user["email"];
    session_regenerate_id(true);
    header("Location: ../frontend/twofa_setup.php");
    exit;
}

// Session setzen
$_SESSION["user_id"]=$user["id"];
$_SESSION["username"]=$user["email"];
$_SESSION["time"]=time();
session_regenerate_id(true);

// Punkte + Logs
$pdo->prepare("INSERT INTO points (customer_id, activity, points, date) VALUES(?, 'Login', 5, NOW())")
    ->execute([$user["id"]]);

$pdo->prepare("INSERT INTO logs (customer_id, login_date, operating_system, aufloesung) VALUES (?, NOW(), ?, ?)")
    ->execute([$user["id"], $clientOS, $resolution]);

// Weiter ins Konto
header("Location: ../frontend/viewaccount.php");
exit;
