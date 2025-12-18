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
        $mail->Body=sprintf('
            <h2>Willkommen, %s!</h2>
            <p>Vielen Dank für deine Registrierung beim <strong>PosterShop</strong></p>
            <p>Dein Startpasswort lautet: <strong>%s</strong></p>
            <p>Bitte melde dich an und ändere es beim ersten Login.</p>',
            htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($plainPassword, ENT_QUOTES, 'UTF-8')
        );
        $mail->AltBody="Willkommen, {name}!\nDein Startpasswort: {$plainPassword}?n Bitte ändere es beim ersten Login.";

        $mail->send();
    } catch(Exception $e){
        error_log("register_save: Mailer Error: " .$mail->ErrorInfo);
        header("Location: ../frontend/register.php?ok=1&mail=fail");
        exit;
    }

    // E-Mail versenden
    /*if(sendEmail($email, $subject, $body)){
        $_SESSION['registration_email'] = [
            'subject' => $subject,
            'body' => $body,
            'email' => $email,
            'password' => $plainPassword
        ];
        header("Location: ../frontend/register.php?ok=1");   
    }
    else{
        header("Location: ../frontend/register.php?ok=1&mail=fail");
    }*/

// Weiter zu first-login
header("Location: ../frontend/first_login.php");
exit;