<?php
// Session initialisieren
session_start();

require_once("../db.php");
require_once("includes/functions.php");

// CSRF prüfen
if(empty($_POST['csrf'])||empty($_SESSION['csrf'])||!hash_equals($_SESSION['csrf'], $_POST['csrf'])){
    header("Location: ../frontend/register.php?error=invalid");
    exit;
}

// Eingaben einsammeln & prüfen
$name=trim($_POST['name'] ?? '');
$email=mb_strtolower(trim($_POST['email'] ?? ''));

if(strlen($name)<2||strlen($email)<5 ||strpos($email, '@')===false){
    header("Location: ../frontend/register.php?error=invalid");
    exit;
}

// E-Mail Duplikat prüfen
try{
    $stmt=$pdo->prepare("SELECT id FROM customer WHERE LOWER(email)=?");
    $stmt->execute([$email]);
    if($stmt->fetch()){
        header("Location: ../frontend/register.php?error=exists");
        exit;
    }
} catch (Throwable $e){
    header("Location: ../frontend/register.php?error=server");
    exit;
}

// Zufallspasswort generieren (10-12 Zeichen, Buchstaben+Zahlen)
function generateRandomPassword(int $length = 12): string{
    $upper='ABCDEFGHJKLMNPQRSTUVWXYZ'; // ohne I/O
    $lower='abcdefghijkmnopqrstuvwxyz'; // ohne l
    $digits='23456789'; // ohne 0/1
    $all=$upper .$lower .$digits;
    // Garantiert je 1 Zeichen aus jeder Klasse:
    $pwd='';
    $pwd.=$upper[random_int(0, strlen($upper)-1)];
    $pwd.=$lower[random_int(0, strlen($lower)-1)];
    $pwd.=$digits[random_int(0, strlen($digits)-1)];
    // Rest auffüllen mit kompletter Menge
    for($i=3; $i<$length; $i++){
        $pwd.=$all[random_int(0, strlen($all)-1)];
    }
    // Sicher durchmischen
    $pwd=secureShuffle($pwd);

    return $pwd;
}

function secureShuffle(string $s):string{
    $arr=str_split($s);
    for ($i=count($arr)-1; $i>0;$i--){
        $j=random_int(0, $i);
        [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
    }
    return implode ('', $arr);
}

$plainPassword=generateRandomPassword(12);

//Hash erzeugen
$algo=defined('PASSWORD_ARGON2ID')?PASSWORD_ARGON2ID:PASSWORD_DEFAULT;
$passwordHash=password_hash($plainPassword, $algo);

// User anlegen (must_change_password=1, twofacode erst später setzen)
try{
    $pdo->beginTransaction();

    $stmt=$pdo->prepare("INSERT INTO customer (name, email, password_hash, must_change_password, twofacode, created_at)VALUES(?, ?, ?, 1, '', NOW())");
    $stmt->execute([$name, $email, $passwordHash]);
    $customerId=(int)$pdo->lastInsertId();

    // +250 Punkte für Registrierung
    $stmt = $pdo->prepare("INSERT INTO points (customer_id, activity, points, date) VALUES (?, 'Registration', 250, NOW())");
    $stmt->execute([$customerId]);

    $pdo->commit();

    $_SESSION['customer_id']=$customerId;
    $_SESSION['username']=$email;
    $_SESSION['time']=time();
    $_SESSION['pending_pw_change']=true;
    session_regenerate_id(true);
} catch(Throwable $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    header("Location: ../frontend/register.php?error=server");
    exit;
}

    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    $smtpHost='smtp.gmail.com';
    $smtpUser='postershop.info@gmail.com';
    $smtpPass='veyo lyyy twbl rhal';
    $smtpPort=587;

    $mail=new PHPMailer(true);
    try{
        $mail->isSMTP();
        $mail->Host=$smtpHost;
        $mail->SMTPAuth=true;
        $mail->Username=$smtpUser;
        $mail->Password=$smtpPass;
        $mail->SMTPSecure=PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port=$smtpPort;

        //Absender & Empfänger
        $mail->setFrom($smtpUser, 'Postershop');
        $mail->addAddress($email, $name);

        //Inhalt
        $mail->isHTML(true);
        $mail->CharSet='UTF-8';
        $mail->Encoding='base64';
        $mail->Subject='Deine Registrierung beim PosterShop';
        $safeName=htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safePass=htmlspecialchars($plainPassword, ENT_QUOTES, 'UTF-8');
        $mail->Body=<<<HTML
        <!DOCTYPE html>
        <html lang="de">
            <head>
                <meta charset="UTF-8">
                <title>Registrierung bestätigt</title>
                <style>
                    body{
                        margin:0;
                        padding:0;
                        background:#f6f7fb;
                        color:#222;
                        font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
                    }
                    
                    .container{
                        max-width:640px;
                        margin:0 auto;
                        padding:24px;
                    }

                    .card{
                        background:#fff;
                        border:1px solid #e5e7eb;
                        border-radius:12px;
                        box-shadow:0 2px 6px rgba(0,0,0,.06);
                        overflow:hidden;
                    }

                    .header{
                        display:flex;
                        align-items:center;
                        gap:12px;
                        padding:16px 20px;
                        border-bottom:1px solid #eef0f5;
                    }

                    .content{
                        padding:20px;
                    }

                    h1{
                        font-size:20px;
                        margin:0 0 8px;
                    }

                    p{
                        margin:0 0 12px;
                        line-height:1.5;
                    }

                    .meta{
                        background:#fafafa;
                        border:1px solid #eef0f5;
                        border-radius:10px;
                        padding:12px;
                        margin:12px 0;
                    }

                    .btn{
                        display:inline-block;
                        background:#0ea5e9;
                        color:#fff;
                        text-decoration:none;
                        padding:10px 14px;
                        border-radius:8px;
                        font-weight:600;
                        margin-top:12px;
                    }

                    .footer{
                        padding:16px 20px;
                        border-top:1px solid #eef0f5;
                        color:#6b7280;
                        font-size:12px;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="card">
                        <div class="content">
                            <h1>Registrierung bestätigt</h1>
                            <p>Hallo {$safeName},</p>
                            <p>Vielen Dank für deine Registrierung beim <strong>PosterShop</strong>.</p>
                            <div class="meta">
                                <p><strong>Dein einmaliges Startpasswort:</strong>
                                <code>{$safePass}</code></p>
                                <p>Bitte melde dich an und ändere es beim ersten Login.</p>
                            </div>
                        </div>
                        <div class="footer">
                            <p>Diese E-Mail wurde automatisch erstellt. Bei Fragen antworte einfach auf diese Nachricht.</p>
                            <p>&copy; PosterShop</p>
                        </div>
                    </div>
                </div>
            </body>
        </html>
        HTML;
        $mail->AltBody="Willkommen, {name}!\nDein Startpasswort: {$plainPassword}?n Bitte ändere es beim ersten Login.";

        $mail->send();
    } catch(Exception $e){
        error_log("register_save: Mailer Error: " .$mail->ErrorInfo);
        header("Location: ../frontend/register.php?ok=1&mail=fail");
        exit;
    }

// Weiter zu first-login
header("Location: ../frontend/first_login.php");
exit;