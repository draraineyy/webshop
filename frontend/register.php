<?php
session_start();

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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6">

            <div class="card shadow-sm">
                <div class="card-body p-4">

                    <h2 class="mb-3 text-center">Registrierung</h2>

                    <?php if (!empty($_GET['error'])): ?>
                        <div class="alert alert-danger">
                            <?php
                            switch ($_GET['error']){
                                case 'exists': echo "Diese E-Mail ist bereits registriert."; break;
                                case 'invalid': echo "Bitte gültige Daten eingeben."; break;
                                case 'server': echo "Unerwarteter Fehler."; break;
                                default: echo "Fehler bei der Registrierung.";
                            }
                            ?>
                        </div>
                    <?php elseif (!empty($_GET['ok'])): ?>
                        <div class="alert alert-success">
                            Registrierung erfolgreich! Bitte prüfe dein E-Mail Postfach.
                        </div>
                    <?php endif; ?>

                    <form method="post" id="registerform" action="../backend/register_save.php" novalidate>

                        <div class="mb-3">
                            <label class="form-label" for="name">Name</label>
                            <input class="form-control" type="text" id="name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="email">E-Mail (Benutzername)</label>
                            <input class="form-control" type="email" id="email" name="email" required>
                            <div id="emailFeedback" class="invalid-feedback" aria-live="polite"></div>
                        </div>

                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

                        <div class="d-grid">
                            <button class="btn btn-primary btn-lg" type="submit">
                                Registrieren
                            </button>
                        </div>

                    </form>

                    <div class="text-center mt-3">
                        <a href="viewlogin.php">Zum Login</a>
                    </div>

                </div>
            </div>

        </div>
    </div>
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

        if(email.length<5||!email.includes('@')){
            emailAvailable=false;
            setInvalid('Bitte eine gültige E-Mail angeben.');
            updateSubmitState();
            return;
        }

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
                setInvalid(data.message);
            } else if(data.exists){
                emailAvailable=false;
                setInvalid('Diese E-Mail ist bereits registriert.');
            } else{
                emailAvailable=true;
                setValid();
            }
        } catch{
            emailAvailable=false;
            setInvalid('Netzwerkfehler.');
        } finally{
            updateSubmitState();
        }
    }

    emailInput.addEventListener('input', ()=>{
        clearTimeout(debounceTimer);
        debounceTimer=setTimeout(checkEmailAvailability, 400);
    });

    form.addEventListener('submit', (e)=>{
        if(!emailAvailable){
            e.preventDefault();
            setInvalid('Bitte gültige E-Mail eingeben.');
        }
    });

    updateSubmitState();
})();
</script>

</body>
</html>
