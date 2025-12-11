<?php
session_start(); // Sitzung starten, damit wir sie beenden können

// Alle Session-Variablen löschen
session_unset(); // entfernt alle gespeicherten Variablen

// Session komplett zerstören
session_destroy(); // beendet die Session endgültig

// Redirect zurück zur Startseite (index.html) mit Parameter ?logout=1
header("Location: index.html?logout=1"); 
exit; // Script beenden, damit nichts mehr ausgeführt wird
?>