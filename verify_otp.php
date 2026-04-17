<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$type = $_SESSION['password_reset_type'] ?? '';
$identifier = $_SESSION['password_reset_identifier'] ?? '';
$demoOtp = null;

if ($type && $identifier) {
    if ($type === 'admin') {
        $stmt = $pdo->prepare("SELECT otp_code FROM admins WHERE email = :identifier LIMIT 1");
    } else {
        $stmt = $pdo->prepare("SELECT otp_code FROM subjects WHERE username = :identifier OR email = :identifier LIMIT 1");
    }
    $stmt->execute(['identifier' => $identifier]);
    $row = $stmt->fetch();
    $demoOtp = $row['otp_code'] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');

    if ($type === '' || $identifier === '') {
        set_flash('error', 'Reset session expired. Start again.');
        redirect('forgot_password.php');
    }

    if ($type === 'admin') {
        $stmt = $pdo->prepare("
            SELECT *
            FROM admins
            WHERE email = :identifier
              AND otp_code = :otp
              AND otp_expires_at >= NOW()
            LIMIT 1
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT *
            FROM subjects
            WHERE (username = :identifier OR email = :identifier)
              AND otp_code = :otp
              AND otp_expires_at >= NOW()
            LIMIT 1
        ");
    }

    $stmt->execute([
        'identifier' => $identifier,
        'otp' => $otp
    ]);

    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['otp_verified'] = true;
        set_flash('success', 'OTP verified successfully.');
        redirect('reset_password.php');
    } else {
        set_flash('error', 'Invalid or expired OTP.');
        redirect('verify_otp.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP | Cognitive Drift System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-box">
        <h2>Verify OTP</h2>

        <?php require_once __DIR__ . '/includes/alerts_helper.php'; ?>

        <?php if ($demoOtp): ?>
            <div class="alert alert-success">
                Demo OTP: <strong><?= e($demoOtp) ?></strong>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="otp">Enter OTP</label>
                <input type="text" name="otp" id="otp" maxlength="6" required>
            </div>

            <button type="submit" class="btn" style="width:100%;">Verify OTP</button>
        </form>

        <div style="margin-top: 16px; text-align:center;">
            <a href="forgot_password.php" style="color:#93c5fd;">Back</a>
        </div>
    </div>
</body>
</html>