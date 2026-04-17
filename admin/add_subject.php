<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Add Subject';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name']);
    $subject_code = trim($_POST['subject_code']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $status = $_POST['status'] ?? 'active';

    if ($full_name === '' || $subject_code === '' || $username === '' || $password === '') {
        set_flash('error', 'Please fill all required fields.');
        redirect('admin/add_subject.php');
    }

    // check duplicate
    $check = $pdo->prepare("
        SELECT id FROM subjects 
        WHERE subject_code = :code OR username = :username
        LIMIT 1
    ");
    $check->execute([
        'code' => $subject_code,
        'username' => $username
    ]);

    if ($check->fetch()) {
        set_flash('error', 'Subject already exists.');
        redirect('admin/add_subject.php');
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO subjects (
            full_name,
            subject_code,
            email,
            phone,
            username,
            password_hash,
            status,
            baseline_status,
            created_at
        )
        VALUES (
            :full_name,
            :subject_code,
            :email,
            :phone,
            :username,
            :password_hash,
            :status,
            'Pending',
            NOW()
        )
    ");

    $stmt->execute([
        'full_name' => $full_name,
        'subject_code' => $subject_code,
        'email' => $email,
        'phone' => $phone,
        'username' => $username,
        'password_hash' => $password_hash,
        'status' => $status
    ]);

    set_flash('success', 'Subject added successfully.');
    redirect('admin/subjects.php');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="content">

<div class="topbar">
    <h1>Add Subject</h1>
</div>

<?php require_once __DIR__ . '/../includes/alerts_helper.php'; ?>

<div class="panel form-panel">

<form method="POST">

<div class="form-group">
<label>Full Name *</label>
<input type="text" name="full_name" required>
</div>

<div class="form-group">
<label>Subject Code *</label>
<input type="text" name="subject_code" required>
</div>

<div class="form-group">
<label>Email</label>
<input type="email" name="email">
</div>

<div class="form-group">
<label>Phone</label>
<input type="text" name="phone">
</div>

<div class="form-group">
<label>Username *</label>
<input type="text" name="username" required>
</div>

<div class="form-group">
<label>Password *</label>
<input type="password" name="password" required>
</div>

<div class="form-group">
<label>Status</label>
<select name="status">
<option value="active">Active</option>
<option value="inactive">Inactive</option>
</select>
</div>

<button type="submit" class="btn">Save Subject</button>

</form>

</div>

</div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>