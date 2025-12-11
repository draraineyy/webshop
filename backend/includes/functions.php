<?php
// Session initialisieren, falls bisher noch keine initialisiert wurde
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
require_once __DIR__ . '/../../db.php';

// Simulieren von send email -> in einer echten Anwendung würde hier ein richtiger Mail-Server verwendet werden.
// Hier simulieren wir es, indem wir es protokollieren
function sendEmail($email, $subject, $body){
        error_log("Email an: $email\nBetreff: $subject\nInhalt: $body\n");
        return true;
}