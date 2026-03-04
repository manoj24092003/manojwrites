<?php
date_default_timezone_set('Asia/Kolkata');
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_name('manual_login');
session_start();

$message = "";
$reset_success = false;

/* =========================
   DB + MAIL CONFIG
   ========================= */
require "../config/connection.php";
require "../config/mail.php";

/* PHPMailer */
require "./phpmailer/PHPMailer.php";
require "./phpmailer/SMTP.php";
require "./phpmailer/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$valid_token = false;
$token_param = $_GET['token'] ?? $_POST['token'] ?? null;

if ($token_param && $_SERVER['REQUEST_METHOD'] === 'GET')  {
    $check = $con->prepare("
        SELECT id
        FROM admin_login
        WHERE reset_token = ?
          AND reset_expires > NOW()
        LIMIT 1
    ");
    $check->bind_param("s", $token_param);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 1) {
        $valid_token = true;
    }
}
/* =========================
   FORGOT PASSWORD REQUEST
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && empty($_POST['token'])) {

    $email = trim($_POST['email']);

    if ($email !== '') {

        $stmt = $con->prepare("
            SELECT id, last_reset
            FROM admin_login
            WHERE Admin_Email = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $last_reset);
            $stmt->fetch();

            if (!$last_reset || strtotime($last_reset) < strtotime('-15 minutes')) {

                // SAFE token generation
                $token = bin2hex(openssl_random_pseudo_bytes(32));

                $upd = $con->prepare("
                    UPDATE admin_login
                    SET reset_token = ?,
                    reset_expires = DATE_ADD(NOW(), INTERVAL 15 MINUTE),
                    last_reset = NOW()
                    WHERE id = ?
                ");
                $upd->bind_param("si", $token, $id);
                $upd->execute();

                // SAFE mail sending (will never crash page)
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = MAIL_USER;
                    $mail->Password   = MAIL_PASS;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->SMTPOptions = [
                    'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                       ]
                    ];
                    $mail->setFrom(MAIL_USER, 'Admin Panel');
                    $mail->addAddress($email);
                    $mail->isHTML(true);

                    $link = "https://manojwrites.xyz/login/recover.php?token=$token";

                    $mail->Subject = 'Admin Password Reset';
                    $mail->Body = "
                        <p>You requested a password reset.</p>
                        <p><a href='$link'>Reset Password</a></p>
                        <p>This link expires in 15 minutes.</p>
                    ";

                    $mail->send();
                } catch (\Throwable $e) {
                    // intentionally silent
                 
                }
            }
        }
    }

    $message = "If the email exists, a reset link has been sent.";
    

}

/* =========================
   RESET PASSWORD
   ========================= */
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {

    $token = trim($_POST['token']);
    $p1 = $_POST['password'] ?? '';
    $p2 = $_POST['confirm'] ?? '';

    if ($p1 === $p2 && strlen($p1) >= 8) {

        $stmt = $con->prepare("
            SELECT id
            FROM admin_login
            WHERE reset_token = ?
            AND reset_expires > NOW()
            LIMIT 1
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id);
            $stmt->fetch();

            $hash = password_hash($p1, PASSWORD_DEFAULT);

            $upd = $con->prepare("
                UPDATE admin_login
                SET Admin_Password = ?,
                    reset_token = NULL,
                    reset_expires = NULL
                WHERE id = ?
            ");
            $upd->bind_param("si", $hash, $id);
            $upd->execute();

           // header("Location:index.php");
           $reset_success = true;
          $message = "Yoo! Password reset successful.";
            
        }
    }

    //$message = "Invalid or expired reset link.";
    if (!$reset_success) {
        $message = "Invalid or expired reset link.";
    }


    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recover Password</title>
    <meta name="theme-color" content="#000000">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="./login.css">
    <link rel="stylesheet" href="../style.css">
</head>

<body>
<header class="header" style="z-index:9999;">
    <div class="logo" onclick="location.href='/index.php'"><i class="bi bi-tv-fill tv-logo"></i></div>
</header>

<main>
<div class="hero">
<div class="formbox">

<?php if ($reset_success): ?>
    <div style="display:flex;text-align: center; font-weight:400;">
    <p><a href="index.php">Go to Login</a></p>
    </div>
        
<?php elseif ($valid_token): ?>
<form method="post">
    <span><h2>Reset Password</h2></span>

    <input type="hidden" name="token" value="<?= htmlspecialchars($token_param) ?>">

    <div class="form-group">
        <i class="bi bi-lock-fill"></i>
        <input type="password" name="password" placeholder="New Password" required>
    </div>

    <div class="form-group">
        <i class="bi bi-lock-fill"></i>
        <input type="password" name="confirm" placeholder="Confirm Password" required>
    </div>

    <div class="form-group signin">
        <i class="bi bi-check-circle-fill"></i>
        <input type="submit" class="btn btn-primary" value="Update Password">
    </div>
</form>

<?php else: ?>

<form method="post">
    <span><h2>FORGOT PASSWORD</h2></span>

    <div class="form-group">
        <i class="bi bi-envelope"></i>
        <input type="email" name="email" id="adminEmail" placeholder="Admin Email">
    </div>

    <div class="form-group signin">
        <i class="bi bi-send"></i>
        <input type="submit" class="btn btn-primary" value="Send Reset Link">
    </div>

    <span><a href="index.php">Back to Login</a></span>
</form>

<?php endif; ?>

</div>
</div>
</main>
    <div id="site-toast" role="status" aria-live="polite" aria-atomic="true"></div>
    
<script>
function toast(message) {
    const toast = document.getElementById('site-toast');
    toast.innerText = message;
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}
</script>
    
<script>
document.addEventListener('DOMContentLoaded', function () {
    const emailInput = document.getElementById('adminEmail');

    if (!emailInput) return; // not on forgot page

    const form = emailInput.closest('form');

    form.addEventListener('submit', function (e) {
        if (emailInput.value.trim() === '') {
            e.preventDefault(); // stop form submit
            toast('Admin email is required');
            //emailInput.focus();
        }
    });
});
</script>
   
<?php if (!empty($message)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof toast === 'function') {
        toast("<?= htmlspecialchars($message, ENT_QUOTES) ?>");
    }
});
</script>
<?php endif; ?>
</body>
</html>