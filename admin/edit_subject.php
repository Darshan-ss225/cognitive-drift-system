<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Edit Subject';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    set_flash('error', 'Invalid subject.');
    redirect('admin/subjects.php');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM subjects
    WHERE id = :id
    LIMIT 1
");
$stmt->execute(['id' => $id]);
$subject = $stmt->fetch();

if (!$subject) {
    set_flash('error', 'Subject not found.');
    redirect('admin/subjects.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $subject_code = trim($_POST['subject_code'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $password = trim($_POST['password'] ?? '');

    if ($full_name === '' || $subject_code === '' || $username === '') {
        set_flash('error', 'Full name, subject code, and username are required.');
        redirect('admin/edit_subject.php?id=' . $id);
    }

    $check = $pdo->prepare("
        SELECT id
        FROM subjects
        WHERE (subject_code = :subject_code OR username = :username)
          AND id != :id
        LIMIT 1
    ");
    $check->execute([
        'subject_code' => $subject_code,
        'username' => $username,
        'id' => $id
    ]);

    if ($check->fetch()) {
        set_flash('error', 'Another subject already uses this subject code or username.');
        redirect('admin/edit_subject.php?id=' . $id);
    }

    if ($password !== '') {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $update = $pdo->prepare("
            UPDATE subjects
            SET
                full_name = :full_name,
                subject_code = :subject_code,
                email = :email,
                phone = :phone,
                username = :username,
                status = :status,
                password_hash = :password_hash
            WHERE id = :id
        ");

        $update->execute([
            'full_name' => $full_name,
            'subject_code' => $subject_code,
            'email' => $email,
            'phone' => $phone,
            'username' => $username,
            'status' => $status,
            'password_hash' => $password_hash,
            'id' => $id
        ]);
    } else {
        $update = $pdo->prepare("
            UPDATE subjects
            SET
                full_name = :full_name,
                subject_code = :subject_code,
                email = :email,
                phone = :phone,
                username = :username,
                status = :status
            WHERE id = :id
        ");

        $update->execute([
            'full_name' => $full_name,
            'subject_code' => $subject_code,
            'email' => $email,
            'phone' => $phone,
            'username' => $username,
            'status' => $status,
            'id' => $id
        ]);
    }

    log_system_action($pdo, (int)$_SESSION['admin_id'], "Updated subject ID {$id}");

    set_flash('success', 'Subject updated successfully.');
    redirect('admin/subjects.php');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="content">
        <div class="topbar">
            <h1>Edit Subject</h1>
            <div class="topbar-actions">
                <a href="<?= BASE_URL ?>/admin/subjects.php" class="btn btn-secondary">Back</a>
            </div>
        </div>

        <?php require_once __DIR__ . '/../includes/alerts_helper.php'; ?>

        <div class="panel form-panel">
            <form method="POST">

                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" value="<?= e($subject['full_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Subject Code *</label>
                    <input type="text" name="subject_code" value="<?= e($subject['subject_code']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= e($subject['email']) ?>">
                </div>

                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= e($subject['phone']) ?>">
                </div>

                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" value="<?= e($subject['username']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="active" <?= ($subject['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($subject['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>New Password (optional)</label>
                    <input type="password" name="password" placeholder="Leave blank to keep old password">
                </div>

                <button type="submit" class="btn">Update Subject</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>