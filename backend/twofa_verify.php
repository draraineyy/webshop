<?php
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../frontend/viewlogin.php");
    exit;
}

require_once("../db.php");

// CSRF prüfen
if(empty($_POST['csrf'])||empty($_SESSION['csrf'])||!hash_equals($_SESSION['csrf'], $_POST['csrf'])){
    header("Location: ../frontend/twofa_setup.php?error=csrf");
    exit;
}

$code=trim($_POST['code']??'');

if(!preg_match('/^[0-9]{6}$/', $code)){
    header("Location: ../frontend/twofa_setup.php?error=invalid");
    exit;
}

require_once("PHPGangsta/GoogleAuthenticator.php");
$ga=new PHPGangsta_GoogleAuthenticator();

// +/- 1 Zeit-SLice Toleranz (30 Sekunden)
$secret=$_SESSION['pending_2fa_secret']??'';
if($secret===''){
    header("Location: ../frontend/twofa_setup.php?error=server");
    exit;
}
$ok=$ga->verifyCode($secret, $code, 1);

if(!$ok){
    header("Location: ../frontend/twofa_setup.php?error=invalid");
    exit;
}

// Secret jetzt dauerhaft beim User speichern
try{
    $stmt=$pdo->prepare("UPDATE customer SET twofacode=?, created_at=created_at WHERE id=?");
    $stmt->execute([$secret, $_SESSION['user_id']]);
    unset($_SESSION['pending_2fa_secret']);

    header("Location: ../frontend/account.php");
    exit;
} catch (Throwable $e){
    error_log("2");
    header ("Location: ../frontend/twofa_setup.php?error=server");
    exit;
}