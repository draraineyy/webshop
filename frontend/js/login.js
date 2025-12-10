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