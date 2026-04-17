<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('admin/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_type = trim($_POST['login_type'] ?? 'admin');
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        set_flash('error', 'Please enter login details.');
        redirect('login.php');
    }

    if ($login_type === 'admin') {
        $stmt = $pdo->prepare("
            SELECT *
            FROM admins
            WHERE email = :identifier
            LIMIT 1
        ");
        $stmt->execute(['identifier' => $identifier]);
        $admin = $stmt->fetch();

        if ($admin && !empty($admin['password_hash']) && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'] ?? 'admin';

            set_flash('success', 'Admin login successful.');
            redirect('admin/dashboard.php');
        } else {
            set_flash('error', 'Invalid admin email or password.');
            redirect('login.php');
        }
    } else {
        $stmt = $pdo->prepare("
            SELECT *
            FROM subjects
            WHERE username = :identifier OR email = :identifier
            LIMIT 1
        ");
        $stmt->execute(['identifier' => $identifier]);
        $subject = $stmt->fetch();

        if ($subject && !empty($subject['password_hash']) && password_verify($password, $subject['password_hash'])) {
            $_SESSION['subject_id'] = $subject['id'];
            $_SESSION['subject_name'] = $subject['full_name'];
            $_SESSION['subject_code'] = $subject['subject_code'];

            set_flash('success', 'Subject login successful.');
            redirect('subject_session.php');
        } else {
            set_flash('error', 'Invalid subject username/email or password.');
            redirect('login.php');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="login-page">
    <div class="login-wrapper">
        <div class="login-hero">
            <div class="login-badge">CD</div>
            <h1>Cognitive Drift System</h1>
            <p>AI-based behavioral monitoring, drift analysis, alerting, and reporting platform.</p>
            <ul class="login-points">
                <li>Session-based cognitive tracking</li>
                <li>AI drift scoring and risk detection</li>
                <li>Dashboard, reports, and live monitoring</li>
            </ul>
        </div>

        <div class="login-box">
            <h2>System Login</h2>

            <?php require_once __DIR__ . '/includes/alerts_helper.php'; ?>

            <form method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="login_type">Login Type</label>
                    <select name="login_type" id="login_type" required>
                        <option value="admin">Admin Login</option>
                        <option value="subject">Subject Login</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="identifier">Email / Username</label>
                    <input type="text" name="identifier" id="identifier" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                </div>

                <button type="submit" class="btn login-btn">Login</button>
            </form>

            <div class="login-links">
                <a href="<?= BASE_URL ?>/subject_session.php">Go to Subject Session</a>
                <a href="<?= BASE_URL ?>/forgot_password.php">Forgot Password?</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>