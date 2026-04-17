<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Subjects';

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        s.*,
        (
            SELECT COUNT(*)
            FROM sessions sess
            WHERE sess.subject_id = s.id
        ) AS total_sessions
    FROM subjects s
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (
        s.full_name LIKE :search
        OR s.subject_code LIKE :search
        OR s.email LIKE :search
        OR s.username LIKE :search
    )";
    $params['search'] = "%{$search}%";
}

$sql .= " ORDER BY s.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$subjects = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';

function subject_status_badge($status) {
    if ($status === 'active') return 'badge-success';
    if ($status === 'inactive') return 'badge-warning';
    return 'badge-info';
}

function baseline_status_badge($status) {
    if ($status === 'Completed') return 'badge-success';
    if ($status === 'Pending') return 'badge-warning';
    return 'badge-info';
}
?>

<div class="layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="content">
        <div class="topbar">
            <h1>Subjects</h1>
            <div class="topbar-actions">
                <a href="<?= BASE_URL ?>/admin/add_subject.php" class="btn">Add Subject</a>
            </div>
        </div>

        <?php require_once __DIR__ . '/../includes/alerts_helper.php'; ?>

        <div class="panel">
            <form method="GET" class="search-form">
                <input
                    type="text"
                    name="search"
                    placeholder="Search by name, subject code, email, username..."
                    value="<?= e($search) ?>"
                >
                <button type="submit" class="btn">Search</button>
                <a href="<?= BASE_URL ?>/admin/subjects.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <div class="panel">
            <h2>Subject List</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject Code</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Baseline</th>
                            <th>Sessions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($subjects): ?>
                            <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td><?= e($subject['id']) ?></td>
                                    <td><?= e($subject['subject_code']) ?></td>
                                    <td><?= e($subject['full_name']) ?></td>
                                    <td><?= e($subject['username'] ?: 'N/A') ?></td>
                                    <td><?= e($subject['email'] ?: 'N/A') ?></td>
                                    <td><?= e($subject['phone'] ?: 'N/A') ?></td>
                                    <td>
                                        <span class="badge <?= subject_status_badge($subject['status']) ?>">
                                            <?= e(ucfirst($subject['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= baseline_status_badge($subject['baseline_status'] ?: 'Pending') ?>">
                                            <?= e($subject['baseline_status'] ?: 'Pending') ?>
                                        </span>
                                    </td>
                                    <td><?= e($subject['total_sessions']) ?></td>
                                    <td>
                                        <div class="action-cell">
                                            <a class="btn btn-sm" href="<?= BASE_URL ?>/admin/subject_profile.php?id=<?= e($subject['id']) ?>">View</a>
                                            <a class="btn btn-sm btn-secondary" href="<?= BASE_URL ?>/admin/edit_subject.php?id=<?= e($subject['id']) ?>">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10">No subjects found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>