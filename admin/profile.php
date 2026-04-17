<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Profile';

$id = (int)$_SESSION['admin_id'];

$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $id]);
$admin = $stmt->fetch();

if (!$admin) {
    set_flash('error', 'Admin profile not found.');
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($full_name === '') {
        set_flash('error', 'Full name is required.');
        redirect('admin/profile.php');
    }

    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $update = $pdo->prepare("
            UPDATE admins
            SET full_name = :full_name,
                password_hash = :password_hash
            WHERE id = :id
        ");
        $update->execute([
            'full_name' => $full_name,
            'password_hash' => $hash,
            'id' => $id
        ]);
    } else {
        $update = $pdo->prepare("
            UPDATE admins
            SET full_name = :full_name
            WHERE id = :id
        ");
        $update->execute([
            'full_name' => $full_name,
            'id' => $id
        ]);
    }

    $_SESSION['admin_name'] = $full_name;

    set_flash('success', 'Profile updated successfully.');
    redirect('admin/profile.php');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="content">
        <div class="topbar">
            <h1>Admin Profile</h1>
        </div>

        <?php require_once __DIR__ . '/../includes/alerts_helper.php'; ?>

        <div class="panel form-panel">
            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?= e($admin['full_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?= e($admin['email']) ?>" disabled>
                </div>

                <div class="form-group">
                    <label>New Password (optional)</label>
                    <input type="password" name="password" placeholder="Leave blank to keep current password">
                </div>

                <button type="submit" class="btn">Save Profile</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>