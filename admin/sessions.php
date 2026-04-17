<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Sessions';

$subjectFilter = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$statusFilter = trim($_GET['status'] ?? '');

$subjectOptions = $pdo->query("
    SELECT id, full_name, subject_code
    FROM subjects
    ORDER BY full_name ASC
")->fetchAll();

$sql = "
    SELECT s.*, sub.full_name, sub.subject_code
    FROM sessions s
    JOIN subjects sub ON s.subject_id = sub.id
    WHERE 1=1
";
$params = [];

if ($subjectFilter > 0) {
    $sql .= " AND s.subject_id = :subject_id";
    $params['subject_id'] = $subjectFilter;
}

if ($statusFilter !== '') {
    $sql .= " AND s.drift_status = :drift_status";
    $params['drift_status'] = $statusFilter;
}

$sql .= " ORDER BY s.session_date DESC, s.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sessions = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';

function session_badge($status) {
    if ($status === 'High Drift') return 'badge-danger';
    if ($status === 'Moderate Drift') return 'badge-warning';
    if ($status === 'Low Drift') return 'badge-success';
    return 'badge-info';
}
?>

<div class="layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="content">
        <div class="topbar">
            <h1>Sessions</h1>
        </div>

        <div class="panel">
            <form method="GET" class="filter-form">
                <div class="filter-row">
                    <div class="form-group">
                        <label>Subject</label>
                        <select name="subject_id">
                            <option value="">All Subjects</option>
                            <?php foreach ($subjectOptions as $option): ?>
                                <option value="<?= e($option['id']) ?>" <?= ($subjectFilter === (int)$option['id']) ? 'selected' : '' ?>>
                                    <?= e($option['full_name']) ?> (<?= e($option['subject_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All</option>
                            <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Low Drift" <?= $statusFilter === 'Low Drift' ? 'selected' : '' ?>>Low Drift</option>
                            <option value="Moderate Drift" <?= $statusFilter === 'Moderate Drift' ? 'selected' : '' ?>>Moderate Drift</option>
                            <option value="High Drift" <?= $statusFilter === 'High Drift' ? 'selected' : '' ?>>High Drift</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn">Apply Filter</button>
                <a href="<?= BASE_URL ?>/admin/sessions.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <div class="panel">
            <h2>Session List</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Reaction</th>
                            <th>Confidence</th>
                            <th>Quiz</th>
                            <th>Drift</th>
                            <th>Status</th>
                            <th>Risk</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($sessions): ?>
                            <?php foreach ($sessions as $session): ?>
                                <tr>
                                    <td><?= e($session['id']) ?></td>
                                    <td><?= e($session['full_name']) ?> (<?= e($session['subject_code']) ?>)</td>
                                    <td><?= e($session['session_date']) ?></td>
                                    <td><?= number_format((float)$session['reaction_avg'], 2) ?></td>
                                    <td><?= number_format((float)$session['confidence_score'], 2) ?></td>
                                    <td><?= number_format((float)$session['quiz_score'], 2) ?></td>
                                    <td><?= number_format((float)$session['drift_score'], 2) ?></td>
                                    <td><span class="badge <?= session_badge($session['drift_status']) ?>"><?= e($session['drift_status']) ?></span></td>
                                    <td><?= e($session['risk_level'] ?: 'N/A') ?></td>
                                    <td>
                                        <div class="action-cell">
                                            <a class="btn btn-sm" href="<?= BASE_URL ?>/admin/session_detail.php?id=<?= e($session['id']) ?>">View</a>
                                            <button type="button" class="btn btn-sm btn-secondary analyze-session-btn" data-session-id="<?= e($session['id']) ?>">Analyze</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10">No sessions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>