<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'System Logs';

$logs = $pdo->query("
    SELECT l.*, a.full_name AS admin_name
    FROM system_logs l
    LEFT JOIN admins a ON l.admin_id = a.id
    ORDER BY l.id DESC
    LIMIT 100
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="content">
        <div class="topbar">
            <h1>System Logs</h1>
        </div>

        <div class="panel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($logs): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= e($log['id']) ?></td>
                                    <td><?= e($log['admin_name'] ?: 'System') ?></td>
                                    <td><?= e($log['action']) ?></td>
                                    <td><?= e($log['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4">No logs found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>