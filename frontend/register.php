<?php
//Initialisierung der Session
session_start();

// CSRF-Token erzeugen
if(empty($_SESSION['csrf'])) {
    $_SESSION['csrf']=bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];
?>

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>Registrierung</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!--Bootstrap einbinden-->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>

    <body class="container py-4">
        <h2>Registrierung</h2>

        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-danger">
                <?php
                    switch ($_GET['error']){
                        case 'exists': echo "Diese E-Mail ist bereits registriert.";
                        break;
                        case 'invalid': echo "Bitte gültige Daten eingeben.";
                        break;
                        case 'server': echo "Unerwarteter Fehler. Bitte später erneut versuchen.";
                        break;
                        default: echo "Fehler bei der Registrierung.";
                    }
                ?>
            </div>
        <?php elseif (!empty($_GET['ok'])): ?>
            <div class="alert alert-success">
                Registrierung erfolgreich! Bitte prüfe dein E-Mail Postfach (auch den Spam-Ordner).
            </div>
        <?php endif; ?>
        
        <form method="post" id="registerform" action="../backend/register_save.php" class="form-horizontal" role="form">
            <div class="col-md-6">
                <label class="form-label" for="name">Name</label>
                <input class="form-control" type="text" id="name" name="name" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">E-Mail (Benutzername)</label>
                <input class="form-control" type="email" id="email" name="email" required>
                <div id="emailFeedback" class="invalid-feedback" arialive="polite"></div>
            </div>
            
            <!--CSRF-->
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

            <div class="col-12">
                <button class="btn btn-primary" type="submit">Registrieren</button>
                <!--<button class="btn btn-primary" type="submit" action="viewlogin.php">Zum Login</a> -->
            </div>
        </form>

        <div class="d-grid gap-2 mt-3">
            <a href="viewlogin.php">Zum Login</a>
        </div>

        <script>
            (function (){
                const form=document.getElementById('registerform');
                const emailInput=document.getElementById('email');
                const submitBtn=form.querySelector('button[type="submit"]');
                const csrf=document.querySelector('input[name="csrf"]').value;
                const emailFb=document.getElementById('emailFeedback');

                let debounceTimer=null;
                let emailAvailable=false;

                function setInvalid(message){
                    emailInput.classList.remove('is-valid');
                    emailInput.classList.add('is-invalid');
                    emailFb.textContent=message ||'';
                }

                function setValid(){
                    emailInput.classList.remove('is-invalid');
                    emailInput.classList.add('is-valid');
                    emailFb.textContent='';
                }

                function updateSubmitState(){
                    submitBtn.disabled=!emailAvailable;
                }

                async function checkEmailAvailability(){
                    const email=emailInput.value.trim();

                    // schnelle Client-Validierung
                    if(email.length<5||!email.includes('@')){
                        emailAvailable=false;
                        setInvalid('Bitte eine gültige E-Mail angeben.');
                        updateSubmitState();
                        return;
                    }

                    // Anfrage absetzen
                    try{
                        const body=new URLSearchParams({email, csrf});
                        const res=await fetch('../backend/check_email.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body
                        });

                        const data=await res.json();

                        if(!data.ok){
                            emailAvailable=false;
                            setInvalid(data.message || 'Fehler bei der Prüfung.');
                        } else if(data.exists){
                            emailAvailable=false;
                            setInvalid('Diese E-Mail ist bereits registriert.');
                        } else{
                            emailAvailable=true;
                            setValid();
                        }
                    } catch (e){
                        emailAvailable=false;
                        setInvalid('Netzwerkfehler. Bitte später erneut versuchen.');
                    } finally{
                        updateSubmitState();
                    }
                }

                //Debounced Prüfung beim Tippen
                emailInput.addEventListener('input', ()=>{
                    clearTimeout(debounceTimer);
                    debounceTimer=setTimeout(checkEmailAvailability, 400);
                });

                // Finaler Submit-Guard
                form.addEventListener('submit', (e)=>{
                    if(!emailAvailable){
                        e.preventDefault();
                        setInvalid('Bitte eine gültige und verfügbare E-Mail wählen.');
                        alert('Bitte gültige und verfügbare E-Mail eingeben.')
                    }
                });

                //Initialzustand
                updateSubmitState();
            })();

            /*function validateRegister(){
                const name=document.getElementById('name').value.trim();
                const email=document.getElementById('email').value.trim();

                if(name.length<2){
                    alert('Bitte einen gültigen Namen eingeben.');
                    return false;
                }
                if(email.length<5||!email.includes('@')){
                    alert('Bitte eine gültige E-Mail angeben.');
                    return false;
                }
            }*/
        </script>
    </body>
</html>