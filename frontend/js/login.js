
function validateForm() {
    // Werte aus den Eingabefeldern holen
    let email = document.getElementById("email").value;
    let password = document.getElementById("password").value;

    // Validierung: E-Mail muss mindestens 5 Zeichen haben und ein @ enthalten
    if (email.length < 5 || !email.includes("@")) {
        alert("Bitte gültige E-Mail eingeben!");
        return false; // Formular wird nicht abgeschickt
    }

    // Validierung: Passwort muss mindestens 9 Zeichen haben,
    // mindestens einen Großbuchstaben, einen Kleinbuchstaben und eine Zahl
    let regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{9,}$/;
    if (!regex.test(password)) {
        alert("Passwort muss mind. 9 Zeichen, Groß-, Kleinbuchstaben und Zahl enthalten!");
        return false;
    }

    // SHA512-Hash erzeugen
    let shaObj = new jsSHA("SHA-512", "TEXT");
    shaObj.update(password);
    let hash = shaObj.getHash("HEX");

    // Hash ins versteckte Feld schreiben
    document.getElementById("hashedPassword").value = hash;

    // Passwort-Feld leeren, damit Klartext nicht übertragen wird
    document.getElementById("password").value = "";

    // true = Formular darf abgeschickt werden
    return true;
}
