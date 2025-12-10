<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Login mit 2FA</title>
  <!-- Einbindung der jsSHA-Bibliothek, um Passwörter clientseitig mit SHA512 zu hashen -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jsSHA/2.4.2/sha512.min.js"></script>
  <!-- Externe JS-Datei -->
  <script src="js/login.js" defer></script>

</head>
<body>
  <h2>Login</h2>
  
  <!-- Login-Formular -->
  <!-- action = wohin die Daten geschickt werden (backend/login.php) -->
  <!-- onsubmit = ruft validateForm() auf, bevor das Formular abgeschickt wird -->
  <form id="loginForm" method="POST" action="../backend/login.php" onsubmit="return validateForm()">
    
    <!-- Eingabe für E-Mail -->
    <label for="email">E-Mail:</label>
    <input type="email" id="email" name="email" required>
    
    <!-- Eingabe für Passwort -->
    <label for="password">Passwort:</label>
    <input type="password" id="password" name="password" required>
    
    <!-- Verstecktes Feld, in das wir den SHA512-Hash schreiben -->
    <input type="hidden" id="hashedPassword" name="hashedPassword">

    <!-- Eingabe für den 2FA-Code (6-stellig aus Google Authenticator) -->
    <label for="code">2FA-Code:</label>
    <input type="text" id="code" name="code" maxlength="6" required>
    
    <!-- Button zum Abschicken -->
    <button type="submit">Login</button>
  </form>

</body>
</html>