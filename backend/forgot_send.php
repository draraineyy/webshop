<?php
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("../db.php");

//CSRF prüfen
if(empty($_POST['csrf'])||empty($_SESSION['csrf'])||!hash_equals($_SESSION['csrf'], $_POST['csrf'])){
    header("Location: ../frontend/forgot.php?error=csrf");
    exit;
}

//Eingaben
$email=mb_strtolower(trim($_POST['email']??''));

//Generische Weiterleitung vorbereiten
$redirectOk="../frontend/forgot.php?ok=1";
$redirectFail="../frontend/forgot.php?error=server";

//E-Mail grob validieren
if(strlen($email)<5||strpos($email, '@')===false){
    header("Location: " .$redirectOk);
    exit;
}

try{
    //Benutzer lookup
    $stmt=$pdo->prepare("SELECT id, name, email FROM customer WHERE LOWER(email)=?");
    $stmt->execute([$email]);
    $user=$stmt->fetch(PDO::FETCH_ASSOC);

    //Wenn Benutzer nicht existiert
    if(!$user){
        usleep(250000);
        header("Location: " .$redirectOk);
        exit;
    }

    //Temporäres Passwort generieren
    function secureShuffle(string $s):string{
        $arr=str_split($s);
        for($i=count($arr)-1; $i>0;$i--){
            $j=random_int(0, $i);
            [$arr[$i], $arr[$j]]=[$arr[$j], $arr[$i]];
        }
        return implode('', $arr);
    }

    function generateRandomPassword(int $length=12):string{
        $upper='ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower='abcdefghijkmnopqrstuvwxyz';
        $digits='23456789';
        $all=$upper .$lower .$digits;
        $pwd='';
        $pwd .=$upper[random_int(0, strlen($upper)-1)];
        $pwd .=$lower[random_int(0, strlen($lower)-1)];
        $pwd .=$digits[random_int(0, strlen($digits)-1)];
        for ($i=3; $i<$length; $i++){
            $pwd .=$all[random_int(0, strlen($all)-1)];
        }
        return secureShuffle($pwd);
    }

    $plainPassword=generateRandomPassword(12);

    //Hash erzeugen
    $algo=defined('PASSWORD_ARGON2ID')?PASSWORD_ARGON2ID:PASSWORD_DEFAULT;
    $passwordHash=password_hash($plainPassword, $algo);

    //Passwort setzen & must_change_password=1
    $stmt=$pdo->prepare("UPDATE customer SET password_hash=?, must_change_password=1 WHERE id=?");
    $stmt->execute([$passwordHash, (int)$user['id']]);

    //PHPMailer laden
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/PHPMailer/src/Exception.php';

    $smtpHost='smtp.gmail.com';
    $smtpUser='postershop.info@gmail.com';
    $smtpPass='veyo lyyy twbl rhal';
    $smtpPort=587;

    $mail=new PHPMailer\PHPMailer\PHPMailer(true);
    try{
        
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpPort;

        $mail->setFrom($smtpUser, 'Postershop');
        $mail->addAddress($user['email'], $user['name'] ?? '');

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Subject = 'Dein temporäres Passwort – Postershop';

        // E-Mail-Inhalt
        $safecustomerName=$user['name']?'' .htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'):'';
        $safePlainPwd=htmlspecialchars($plainPassword, ENT_QUOTES, 'UTF-8');
        $mail->Body = <<<HTML
        <!DOCTYPE html>
        <html lang="de">
            <head>
                <meta charset="UTF-8">
                <title>Temporäres Passwort</title>
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <style>
                    body{margin:0;padding:0;background:#f6f7fb;color:#222;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
                    .container{max-width:680px;margin:0 auto;padding:24px}
                    .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 2px 6px rgba(0,0,0,.06);overflow:hidden}
                    .header{display:flex;align-items:center;gap:12px;padding:16px 20px;border-bottom:1px solid #eef0f5}
                    .brand{display:flex;align-items:center;gap:10px;font-weight:700;font-size:18px}
                    .brand img{height:28px;width:auto;vertical-align:middle}
                    .content{padding:20px}
                    h1{font-size:20px;margin:0 0 8px}
                    p{margin:0 0 12px;line-height:1.5}
                    .meta{background:#fafafa;border:1px solid #eef0f5;border-radius:10px;padding:12px;margin:12px 0}
                    .row{display:flex;justify-content:space-between;margin-top:6px}
                    .muted{color:#6b7280}
                    .kbd{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;padding:6px 8px;display:inline-block}
                    .btn{display:inline-block;background:#0ea5e9;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;font-weight:600;margin-top:12px}
                    .footer{padding:16px 20px;border-top:1px solid #eef0f5;color:#6b7280;font-size:12px}
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="card">
                        <div class="header">
                            <div class="brand">
                                <span>PosterShop</span>
                            </div>
                        </div>
                        <div class="content">
                            <h1>Temporäres Passwort</h1>
                            <p>Hallo{$customerName},</p>
                            <p>du hast ein neues temporäres Passwort angefordert. Bitte verwende es für den nächsten Login und
                                <strong>ändere dein Passwort</strong> direkt nach der Anmeldung.
                            </p>
                            <div class="meta">
                                <div class="row">
                                    <span class="muted">Temporäres Passwort:</span>
                                    <span class="kbd">{$safePlainPwd}</span>
                                </div>
                            </div>
                            <p style="margin-top:12px;" class="muted">
                                Hinweis<br>
                                Wenn du kein neues Passwort angefordert hast, ignoriere diese E-Mail oder kontaktiere unseren Support
                            </p>
                        </div>
                        <div class="footer">
                            <p>&copy; PosterShop</p>
                        </div>
                    </div>
                </div>
            </body>
        </html>
        HTML;
        
        $mail->AltBody =
        "Hallo {$customerName}," . PHP_EOL .
        "Du hast ein neues temporäres Passwort angefordert." . PHP_EOL .
        "Dein temporäres Passwort lautet: {$plainPassword}" . PHP_EOL .
        "Bitte melde dich an und ändere es direkt beim ersten Login." . PHP_EOL .
        "Wenn du das nicht warst, ignoriere diese E-Mail oder kontaktiere unseren Support." . PHP_EOL;

        $mail->send();
    } catch (PHPMailer\PHPMailer\Exception $e) {
        error_log("forgot_send: Mailer Error: " . $mail->ErrorInfo);
        // Generisch antworten, unabhängig vom Mail-Erfolg
        header("Location: " . $redirectOk);
        exit;
    }
    
    // Immer generisch OK
    header("Location: ../frontend/viewlogin.php");
    exit;

} catch (Throwable $e) {
    error_log("forgot_send: " . $e->getMessage());
    header("Location: " . $redirectFail);
    exit;
}