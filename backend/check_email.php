<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once("../db.php");

$response=[
    'ok'=>false,
    'exists'=>false,
    'valid'=>false,
    'message'=>''
];

//CSRF prüfen
if(empty($_POST['csrf'])||empty($_SESSION['csrf'])||!hash_equals($_SESSION['csrf'], $_POST['csrf'])){
    http_response_code(400);
    $response['message']='Ungültiger Request.';
    echo json_encode($response);
    exit;
}

//Eingaben validieren
$email=trim($_POST['email']??'');
if($email === ''||!filter_var($email, FILTER_VALIDATE_EMAIL)||strlen($email)>255){
    $response['ok']=true;
    $response['valid']=false;
    $response['message']='Bitte eine gültige E-Mail angeben.';
    echo json_encode($response);
    exit;
}

//Normalisieren (case-intensitive Suche)
$normEmail=mb_strtolower($email);

try{
    //Case-intensitive Vergleich: LOWER(email)=?
    $stmt=$pdo->prepare("SELECT 1 FROM customer WHERE LOWER(email)=?");
    $stmt->execute([$normEmail]);
    $exists=(bool)$stmt->fetchColumn();

    $response['ok']=true;
    $response['valid']=true;
    $response['exists']=$exists;
    $response['message']=$exists?'Diese E-Mail ist bereits registriert.' : 'E-Mail ist verfügbar.';
    echo json_encode($response);
} catch (Throwable $e){
    http_response_code(500);
    $response['message']='Serverfehler.';
    echo json_encode($response);
}