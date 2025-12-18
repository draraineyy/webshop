<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Login mit 2FA</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Eigene CSS-Datei -->
  <link rel="stylesheet" href="../css/style.css">

  <style>
    html, body {
      height: 100%;
      margin: 0;
      background-color: #f8f9fa;
    }

    body {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-card {
      max-width: 400px;
      width: 100%;
      margin: 20px;
    }

    input.form-control {
      margin-bottom: 10px;
    }

    /* Optional: kleine Anpassung, damit es auch auf sehr kleinen Bildschirmen passt */
    @media (max-height: 500px) {
      body {
        align-items: flex-start;
        padding-top: 20px;
        padding-bottom: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="login-card card shadow p-4">
    <h2 class="h4 mb-3 text-center">Login</h2>
    <p class="text-muted mb-4 text-center">Mit E-Mail, Passwort und ggf. 2FA-Code anmelden.</p>

    <form id="loginForm" method="POST" action="../backend/login.php" onsubmit="handleLogin(event)">
      <!-- E-Mail -->
      <div class="mb-3">
        <label for="email" class="form-label">Benutzername (E-Mail)</label>
        <input type="email" id="email" name="email" class="form-control" required>
      </div>

      <!-- Passwort -->
      <div class="mb-3">
        <label for="password" class="form-label">Passwort</label>
        <input type="password" class="form-control" id="password" name="password" required>
        <div class="form-text">Mindestens 9 Zeichen, Groß-/Kleinbuchstaben und Zahl.</div>
      </div>

      <!-- 2FA-Code -->
      <div class="mb-3">
        <label for="code" class="form-label">2FA Code (falls eingerichtet)</label>
        <input type="text" class="form-control" id="code" name="code" maxlength="6" inputmode="numeric" pattern="^[0-9]{6}$">
        <div class="form-text">Öffne deine Authenticator-App für den aktuellen Code. Leer lassen, wenn noch kein 2FA eingerichtet.</div>
      </div>

      <!-- Versteckte Felder für Clientdaten -->
      <input type="hidden" id="resolution" name="resolution">
      <input type="hidden" id="client_os" name="client_os">

      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary btn-lg">Anmelden</button>
      </div>
    </form>

    <div class="d-grid gap-2 mt-3 text-center">
        <a href="register.php">Neu registrieren</a>
    </div>
  </div>

  <script>
    function detectClientOS(){
      const ua = navigator.userAgent || "";
      if(/Windows/i.test(ua)) return "Windows";
      if(/Macintosh|Mac OS X/i.test(ua)) return "macOS";
      if(/Android/i.test(ua)) return "Android";
      if(/iPhone|iPad|iPod/i.test(ua)) return "iOS";
      if(/Linux/i.test(ua)) return "Linux";
      return "Unknown";
    }

    function validateForm() {
      const email = document.getElementById("email").value.trim();
      const pw = document.getElementById("password").value;
      const code = document.getElementById("code").value.trim();

      if (email.length < 5 || !email.includes("@")) {
        alert("Bitte eine gültige E-Mail eingeben!");
        return false;
      }

      const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{9,}$/;
      if (!regex.test(pw)) {
        alert("Passwort muss mindestens 9 Zeichen haben, Groß-/Kleinbuchstaben und Zahl.");
        return false;
      }

      if(code !== "" && !/^[0-9]{6}$/.test(code)){
        alert("Der 2FA-Code muss genau 6 Ziffern enthalten.");
        return false;
      }
      return true;
    }

    async function handleLogin(e){
      e.preventDefault();
      if(!validateForm()) return false;

      document.getElementById("resolution").value = `${window.screen.width}x${window.screen.height}`;
      document.getElementById("client_os").value = detectClientOS();

      e.target.submit();
    }
  </script>
</body>
</html>
