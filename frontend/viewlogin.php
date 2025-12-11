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
  <form id="loginForm" method="POST" action="../backend/login.php" onsubmit="handleLogin(event)">
    
    <!-- Eingabe für E-Mail -->
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>
    
    <!-- Eingabe für Passwort -->
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>

    <input type="hidden" id="password_hash" name="password_hash">   <!-- hidden-feld:passwort hash. Darin kommt der Hash aus login.js-->

    
    <!-- Eingabe für den 2FA-Code (6-stellig aus Google Authenticator) -->
    <label for="code">2FA Code:</label>
    <input type="text" id="code" name="code" maxlength="6" required>
    
    <!-- Button zum Abschicken -->
    <button type="submit">Login</button>
  </form>
 

<script>
async function sha512(text) {
    const buf = await crypto.subtle.digest("SHA-512", new TextEncoder().encode(text));
    return Array.from(new Uint8Array(buf))
        .map(b => b.toString(16).padStart(2,"0"))
        .join("");
}

function validateForm() {
    const email = document.getElementById("email").value;
    const pw = document.getElementById("password").value;

    if (email.length < 5 || !email.includes("@")) {
        alert("Please enter a valid email!");
        return false;
    }

    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{9,}$/;
    if (!regex.test(pw)) {
        alert("Password must be at least 9 characters long and contain uppercase, lowercase, and a number!");
        return false;
    }
    return true;
}

document.getElementById("loginForm").addEventListener("submit", async function(e){
    e.preventDefault();

    if (!validateForm()) return; // Validierung vorher

    const pw = document.getElementById("password").value;
    const hash = await sha512(pw);

    document.getElementById("password_hash").value = hash;

    // Optional: Klartext-Passwort löschen, damit nur Hash gesendet wird
    document.getElementById("password").value = "";

    // Formular jetzt abschicken
    e.target.submit();
});




</script>

</body>


</html>