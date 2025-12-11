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


// crypto.subtle.digest erzeugt den SHA‑512‑Hash und setzt ihn ins Hidden‑Feld.

async function sha512(text) {
  const buf = await crypto.subtle.digest("SHA-512", new TextEncoder().encode(text));
  return Array.from(new Uint8Array(buf))
              .map(b => b.toString(16).padStart(2,"0"))
              .join("");
}

async function handleLogin(e) {
  e.preventDefault();

  const pw = document.getElementById("password").value;
  const hash = await sha512(pw);   // jetzt wird das echte Passwort gehasht
  document.getElementById("password_hash").value = hash;

  console.log("Hash gesetzt:", document.getElementById("password_hash").value);

  document.getElementById("password").disabled = true;
  e.target.submit();
}