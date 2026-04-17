<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$type = $_SESSION['password_reset_type'] ?? '';
$identifier = $_SESSION['password_reset_identifier'] ?? '';
$otpVerified = $_SESSION['otp_verified'] ?? false;

if (!$otpVerified || $type === '' || $identifier === '') {
    set_flash('error', 'Please verify OTP first.');
    redirect('forgot_password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword === '' || strlen($newPassword) < 6) {
        set_flash('error', 'Password must be at least 6 characters.');
        redirect('reset_password.php');
    }

    if ($newPassword !== $confirmPassword) {
        set_flash('error', 'Passwords do not match.');
        redirect('reset_password.php');
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);

    if ($type === 'admin') {
        $stmt = $pdo->prepare("
            UPDATE admins
            SET password_hash = :password_hash,
                reset_token = NULL,
                reset_expires_at = NULL,
                otp_code = NULL,
                otp_expires_at = NULL
            WHERE email = :identifier
        ");
        $stmt->execute([
            'password_hash' => $hash,
            'identifier' => $identifier
        ]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE subjects
            SET password_hash = :password_hash,
                reset_token = NULL,
                reset_expires_at = NULL,
                otp_code = NULL,
                otp_expires_at = NULL
            WHERE username = :identifier OR email = :identifier
        ");
        $stmt->execute([
            'password_hash' => $hash,
            'identifier' => $identifier
        ]);
    }

    unset($_SESSION['password_reset_type'], $_SESSION['password_reset_identifier'], $_SESSION['otp_verified']);

    set_flash('success', 'Password reset successful. Please login.');
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Cognitive Drift System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-box">
        <h2>Reset Password</h2>

        <?php require_once __DIR__ . '/includes/alerts_helper.php'; ?>

        <form method="POST">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" name="new_password" id="new_password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>

            <button type="submit" class="btn" style="width:100%;">Reset Password</button>
        </form>
    </div>
</body>
</html>