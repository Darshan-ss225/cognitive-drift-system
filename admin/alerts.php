<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Alerts';

$statusFilter = trim($_GET['status'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');

$sql = "
    SELECT a.*, sub.full_name, sub.subject_code, s.drift_score, s.session_date
    FROM alerts a
    JOIN subjects sub ON a.subject_id = sub.id
    LEFT JOIN sessions s ON a.session_id = s.id
    WHERE 1=1
";
$params = [];

if ($statusFilter !== '') {
    $sql .= " AND a.status = :status";
    $params['status'] = $statusFilter;
}

if ($typeFilter !== '') {
    $sql .= " AND a.alert_type = :type";
    $params['type'] = $typeFilter;
}

$sql .= " ORDER BY a.created_at DESC, a.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alerts = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';

function alert_badge($type) {
    if ($type === 'High Drift') return 'badge-danger';
    if ($type === 'Moderate Drift') return 'badge-warning';
    return 'badge-info';
}
?>

<div class="layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="content">
        <div class="topbar">
            <h1>Alerts</h1>
        </div>

        <div class="panel">
            <form method="GET" class="filter-form">
                <div class="filter-row">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All</option>
                            <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Resolved" <?= $statusFilter === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Alert Type</label>
                        <select name="type">
                            <option value="">All</option>
                            <option value="Moderate Drift" <?= $typeFilter === 'Moderate Drift' ? 'selected' : '' ?>>Moderate Drift</option>
                            <option value="High Drift" <?= $typeFilter === 'High Drift' ? 'selected' : '' ?>>High Drift</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn">Filter</button>
                <a href="<?= BASE_URL ?>/admin/alerts.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <div class="panel">
            <h2>Alert List</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Session Date</th>
                            <th>Alert Type</th>
                            <th>Drift Score</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($alerts): ?>
                            <?php foreach ($alerts as $alert): ?>
                                <tr>
                                    <td><?= e($alert['full_name']) ?> (<?= e($alert['subject_code']) ?>)</td>
                                    <td><?= e($alert['session_date'] ?: 'N/A') ?></td>
                                    <td><span class="badge <?= alert_badge($alert['alert_type']) ?>"><?= e($alert['alert_type']) ?></span></td>
                                    <td><?= $alert['drift_score'] !== null ? number_format((float)$alert['drift_score'], 2) : 'N/A' ?></td>
                                    <td><?= e($alert['message']) ?></td>
                                    <td><span class="badge <?= ($alert['status'] === 'Resolved' ? 'badge-success' : 'badge-danger') ?>"><?= e($alert['status'] ?: 'Active') ?></span></td>
                                    <td><?= e($alert['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7">No alerts found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>