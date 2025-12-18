<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Login with 2FA</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Eigene CSS-Datei -->
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <div class="container">
    <div class="card shadow login-card">
      <div class="card-body p-4">
        <h2 class="h4 mb-3">Login</h2>
        <p class="text-muted mb-4">Bitte mit E-Mail, Passwort und ggf. 2FA-Code anmelden.</p>
  
        <!-- Login-Formular -->
        <!-- action = wohin die Daten geschickt werden (backend/login.php) -->
        <!-- onsubmit = ruft validateForm() auf, bevor das Formular abgeschickt wird -->
        <form id="loginForm" method="POST" action="../backend/login.php" onsubmit="handleLogin(event)">
          <div class="mb-3">
    
            <!-- Eingabe für E-Mail -->
            <label for="email">Benutzername:</label>
            <input type="email" id="email" name="email" required>
    
            <!-- Eingabe für Passwort -->
            <label for="password">Passwort:</label>
            <input type="password" class="form-control" id="password" name="password" required>
            <div class="form-text">Mindestens 9 Zeichen, Groß-/Kleinbuchstaben und Zahl.</div>
          </div>

          <div class="mb-3">
            <!-- Eingabe für den 2FA-Code (6-stellig aus Google Authenticator) -->
            <label for="code" class="form-label">2FA Code (falls bereits eingerichtet)</label>
            <input type="text" class="form-control" id="code" name="code" maxlength="6" inputmode="numeric" pattern="^[0-9]{6}$">
            <div class="form-text">Öffne deine Authenticator-App und nutze den aktuellen Code.</div>
            <div class="form-text">Wenn du noch kein 2FA eingerichtet hast, lasse das Feld leer - du wirst gleich weitergeleitet.</div>
          </div>

          <!-- Versteckte Felder -->
          <!-- <input type="hidden" id="password_hash" name="password_hash">   <  !-- hidden-feld:passwort hash. Darin kommt der Hash aus login.js-->
          <input type="hidden" id="resolution" name="resolution">
          <input type="hidden" id="client_os" name="client_os">
    
          <div class="d-grid gap-2">
            <!-- Button zum Abschicken -->
            <button type="submit" class="btn btn-primary">Anmelden</button>
            <!-- <button type="submit" class="btn btn-primary" action="register.php">Neu registrieren</a> -->
          </div>
        </form>
        <div class="d-grid gap-2 mt-3">
            <a href="register.php">Neu registrieren</a>
        </div>
      </div>
    </div>
  </div>

 

  <script>
    //Clientdaten
    function detectClientOS(){
      const ua=navigator.userAgent ||"";
      if(/Windows/i.test(ua)) return "Windows";
      if(/Macintosh|Mac OS X/i.test(ua))return "macOS";
      if(/Android/i.test(ua))return "Android";
      if(/iPhone|iPad|iPod/i.test(ua))return "iOS";
      if(/Linux/i.test(ua))return "Linux";
      return "Unknown";
    }

    function validateForm() {
      const email = document.getElementById("email").value.trim();
      const pw = document.getElementById("password").value;
      const code = document.getElementById("code").value.trim();

      if (email.length < 5 || !email.includes("@")) {
        alert("Please enter a valid email!");
        return false;
      }

      const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{9,}$/;
      if (!regex.test(pw)) {
        alert("Passwort muss mindestens 9 Zeichen haben, Groß-/Kleinbuchstabe und Zahl.");
        return false;
      }

      // 2FA nur prüfen, wenn etwas eingegeben wurde
      if(code!==""&&!/^[0-9]{6}$/.test(code)){
        alert("Der 2FA-Code muss exakt 6 Ziffern haben.");
        return false;
      }
      return true;
    }

    async function handleLogin(e){
      e.preventDefault();
      if(!validateForm()) return false;

      //const pw=document.getElementById("password").value;
      //document.getElementById("password_hash").value=await sha512(pw);

      //Metadaten
      document.getElementById("resolution").value=`${window.screen.width}x${window.screen.height}`;
      document.getElementById("client_os").value=detectClientOS();

      //Klartext-Passwort nicht mitsenden
      //document.getElementById("password").disabled=true;
      e.target.submit();
    }
  </script>
</body>
</html>