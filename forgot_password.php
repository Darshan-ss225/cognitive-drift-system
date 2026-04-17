<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_type = trim($_POST['account_type'] ?? 'admin');
    $identifier = trim($_POST['identifier'] ?? '');

    if ($identifier === '') {
        set_flash('error', 'Please enter email or username.');
        redirect('forgot_password.php');
    }

    $token = generate_token(32);
    $otp = generate_otp(6);

    if ($account_type === 'admin') {
        $stmt = $pdo->prepare("
            SELECT * FROM admins
            WHERE email = :identifier
            LIMIT 1
        ");
        $stmt->execute(['identifier' => $identifier]);
        $user = $stmt->fetch();

        if ($user) {
            $update = $pdo->prepare("
                UPDATE admins
                SET reset_token = :reset_token,
                    reset_expires_at = DATE_ADD(NOW(), INTERVAL 15 MINUTE),
                    otp_code = :otp_code,
                    otp_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE)
                WHERE id = :id
            ");
            $update->execute([
                'reset_token' => $token,
                'otp_code' => $otp,
                'id' => $user['id']
            ]);

            $_SESSION['password_reset_type'] = 'admin';
            $_SESSION['password_reset_identifier'] = $identifier;
            set_flash('success', 'Reset request created. Use OTP shown on verify page for demo.');
            redirect('verify_otp.php');
        }
    } else {
        $stmt = $pdo->prepare("
            SELECT *
            FROM subjects
            WHERE username = :identifier OR email = :identifier
            LIMIT 1
        ");
        $stmt->execute(['identifier' => $identifier]);
        $user = $stmt->fetch();

        if ($user) {
            $update = $pdo->prepare("
                UPDATE subjects
                SET reset_token = :reset_token,
                    reset_expires_at = DATE_ADD(NOW(), INTERVAL 15 MINUTE),
                    otp_code = :otp_code,
                    otp_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE)
                WHERE id = :id
            ");
            $update->execute([
                'reset_token' => $token,
                'otp_code' => $otp,
                'id' => $user['id']
            ]);

            $_SESSION['password_reset_type'] = 'subject';
            $_SESSION['password_reset_identifier'] = $identifier;
            set_flash('success', 'Reset request created. Use OTP shown on verify page for demo.');
            redirect('verify_otp.php');
        }
    }

    set_flash('error', 'Account not found.');
    redirect('forgot_password.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Cognitive Drift System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-box">
        <h2>Forgot Password</h2>

        <?php require_once __DIR__ . '/includes/alerts_helper.php'; ?>

        <form method="POST">
            <div class="form-group">
                <label for="account_type">Account Type</label>
                <select name="account_type" id="account_type" required>
                    <option value="admin">Admin</option>
                    <option value="subject">Subject</option>
                </select>
            </div>

            <div class="form-group">
                <label for="identifier">Email / Username</label>
                <input type="text" name="identifier" id="identifier" required>
            </div>

            <button type="submit" class="btn" style="width:100%;">Send Reset OTP</button>
        </form>

        <div style="margin-top: 16px; text-align:center;">
            <a href="login.php" style="color:#93c5fd;">Back to Login</a>
        </div>
    </div>
</body>
</html>