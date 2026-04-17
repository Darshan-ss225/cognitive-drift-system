<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Session Detail';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    set_flash('error', 'Invalid session selected.');
    redirect('admin/sessions.php');
}

$stmt = $pdo->prepare("
    SELECT s.*, sub.full_name, sub.subject_code, sub.baseline_score, sub.status AS subject_status, sub.email AS subject_email, sub.phone AS subject_phone
    FROM sessions s
    JOIN subjects sub ON s.subject_id = sub.id
    WHERE s.id = :id
    LIMIT 1
");
$stmt->execute(['id' => $id]);
$session = $stmt->fetch();

if (!$session) {
    set_flash('error', 'Session not found.');
    redirect('admin/sessions.php');
}

$alertsStmt = $pdo->prepare("
    SELECT *
    FROM alerts
    WHERE session_id = :session_id
    ORDER BY created_at DESC, id DESC
");
$alertsStmt->execute(['session_id' => $id]);
$alerts = $alertsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';

function detail_badge($status) {
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
            <h1>Session Detail</h1>
            <div class="topbar-actions">
                <button type="button" class="btn btn-secondary analyze-session-btn" data-session-id="<?= e($session['id']) ?>">Analyze Session</button>
                <a href="<?= BASE_URL ?>/admin/subject_profile.php?id=<?= e($session['subject_id']) ?>" class="btn btn-secondary">Subject Profile</a>
                <a href="<?= BASE_URL ?>/admin/sessions.php" class="btn">Back</a>
            </div>
        </div>

        <div class="profile-grid">
            <div class="panel">
                <h2>Session Information</h2>
                <p><strong>Session ID:</strong> <?= e($session['id']) ?></p>
                <p><strong>Subject:</strong> <?= e($session['full_name']) ?> (<?= e($session['subject_code']) ?>)</p>
                <p><strong>Session Date:</strong> <?= e($session['session_date']) ?></p>
                <p><strong>Reaction Average:</strong> <?= number_format((float)$session['reaction_avg'], 2) ?></p>
                <p><strong>Confidence Score:</strong> <?= number_format((float)$session['confidence_score'], 2) ?></p>
                <p><strong>Quiz Score:</strong> <?= number_format((float)$session['quiz_score'], 2) ?></p>
                <p><strong>Drift Score:</strong> <?= number_format((float)$session['drift_score'], 2) ?></p>
                <p><strong>Drift Status:</strong> <span class="badge <?= detail_badge($session['drift_status']) ?>"><?= e($session['drift_status'] ?: 'Pending') ?></span></p>
                <p><strong>Risk Level:</strong> <?= e($session['risk_level'] ?: 'N/A') ?></p>
                <p><strong>Analyzed At:</strong> <?= e($session['analyzed_at'] ?: 'Not analyzed yet') ?></p>
                <p><strong>Baseline Score:</strong> <?= $session['baseline_score'] !== null ? number_format((float)$session['baseline_score'], 2) : 'N/A' ?></p>
            </div>

            <div class="panel">
                <h2>Subject Snapshot</h2>
                <p><strong>Subject Status:</strong> <?= e(ucfirst($session['subject_status'] ?: 'active')) ?></p>
                <p><strong>Email:</strong> <?= e($session['subject_email'] ?: 'N/A') ?></p>
                <p><strong>Phone:</strong> <?= e($session['subject_phone'] ?: 'N/A') ?></p>
            </div>
        </div>

        <div class="panel">
            <h2>AI Summary</h2>
            <p><?= nl2br(e($session['ai_summary'] ?: 'No AI summary available yet.')) ?></p>
        </div>

        <div class="panel">
            <h2>Session Notes</h2>
            <p><?= nl2br(e($session['notes'] ?: 'No notes available.')) ?></p>
        </div>

        <div class="panel">
            <h2>Text Sample</h2>
            <div class="text-sample-box">
                <?= nl2br(e($session['text_sample'] ?: 'No text sample submitted.')) ?>
            </div>
        </div>

        <div class="panel">
            <h2>Triggered Alerts</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Alert Type</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($alerts): ?>
                            <?php foreach ($alerts as $alert): ?>
                                <tr>
                                    <td><?= e($alert['alert_type']) ?></td>
                                    <td><?= e($alert['message']) ?></td>
                                    <td><?= e($alert['status'] ?: 'Active') ?></td>
                                    <td><?= e($alert['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4">No alerts linked to this session.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($session['ai_raw_response'])): ?>
            <div class="panel">
                <h2>Raw AI Response</h2>
                <div class="text-sample-box"><?= nl2br(e($session['ai_raw_response'])) ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>