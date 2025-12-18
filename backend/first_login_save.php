<?php
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once("../db.php");

if(!isset($_SESSION['customer_id'])){
    header("Location: ../frontend/viewlogin.php");
    exit;
}

//CSRF
if(empty($_POST['csrf']) || empty($_SESSION['csrf'])|| !hash_equals($_SESSION ['csrf'], $_POST['csrf'])){
    header("Location: ../frontend/first_login.php?error=csrf");
    exit;
}

$current=$_POST['current_pw']??'';
$pw1=$_POST['pw1']??'';
$pw2=$_POST['pw2']??'';
$regex='/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{9,}$/';

//Basisvalidierung neues Passwort
if($pw1!==$pw2||!preg_match($regex, $pw1)){
    header("Location: ../frontend/first_login.php?error=invalid");
    exit;
}

try{
    // Aktuellen Hash laden
    $stmt=$pdo->prepare("SELECT password_hash, must_change_password FROM customer WHERE id=? LIMIT 1");
    $stmt->execute([$_SESSION['customer_id']]);
    $row=$stmt->fetch(PDO::FETCH_ASSOC);

    if(!$row){
        header("Location: ../frontend/first_login.php?error=server");
        exit;
    }

    $storedHash=$row['password_hash'];

    //Startpasswort prüfen
    if(!password_verify($current, $storedHash)){
        header("Location: ../frontend/first_login.php?error=oldpw");
        exit;
    }

    //Neues Passwort hashen
    $algo=defined('PASSWORD_ARGON2ID')?PASSWORD_ARGON2ID:PASSWORD_DEFAULT;
    $newHash=password_hash($pw1, $algo);

    //Speichern und must_password_change abschalten
    $stmt=$pdo->prepare("UPDATE customer SET password_hash=?, must_change_password=0 WHERE id=?");
    $stmt->execute([$newHash, $_SESSION['customer_id']]);

    //CSRF-Token erneuern
    unset($_SESSION['csrf']);
    session_regenerate_id(true);

} catch(Throwable $e){
    header("Location: ../frontend/first_login.php?error=server");
    exit;
}

header("Location: ../frontend/twofa_setup.php");