<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login with 2FA</title>
  
</head>
<body>
  <h2>Login</h2>
  
  <!-- Login-Formular -->
  <!-- action = wohin die Daten geschickt werden (backend/login.php) -->
  <!-- onsubmit = ruft validateForm() auf, bevor das Formular abgeschickt wird -->
  <form id="loginForm" method="POST" action="../backend/login.php" onsubmit="return validateForm()">
    
    <!-- Eingabe für E-Mail -->
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>
    
    <!-- Eingabe für Passwort -->
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>
    
    <!-- Verstecktes Feld, in das wir den SHA512-Hash schreiben -->
    <input type="hidden" id="hashedPassword" name="hashedPassword">

    <!-- Eingabe für den 2FA-Code (6-stellig aus Google Authenticator) -->
    <label for="code">2FA Code:</label>
    <input type="text" id="code" name="code" maxlength="6" required>
    
    <!-- Button zum Abschicken -->
    <button type="submit">Login</button>
  </form>
 

<script>
function validateForm() {
    console.log("Form validation läuft!");

    let email = document.getElementById("email").value;
    let password = document.getElementById("password").value;

    // Validierung: E-Mail prüfen
    if (email.length < 5 || !email.includes("@")) {
        alert("Please enter a valid email!");
        return false;
    }

    // Validierung: Passwort prüfen
    let regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{9,}$/;
    if (!regex.test(password)) {
        alert("Password must be at least 9 characters long and contain uppercase, lowercase, and a number!");
        return false;
    }

    // Kein Hashing hier mehr!
    return true; // Formular darf abgeschickt werden
}

</script>

</body>


</html>