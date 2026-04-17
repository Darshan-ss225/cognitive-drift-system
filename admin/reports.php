<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Reports';

$subjects = $pdo->query("
    SELECT id, full_name, subject_code
    FROM subjects
    ORDER BY full_name ASC
")->fetchAll();

$subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$status = trim($_GET['status'] ?? '');
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');

$sql = "
    SELECT
        s.id AS session_id,
        s.subject_id,
        s.session_date,
        s.reaction_avg,
        s.confidence_score,
        s.quiz_score,
        s.drift_score,
        s.drift_status,
        s.risk_level,
        s.analyzed_at,
        s.text_sample,
        sub.full_name,
        sub.subject_code
    FROM sessions s
    JOIN subjects sub ON s.subject_id = sub.id
    WHERE 1=1
";

$params = [];

if ($subjectId > 0) {
    $sql .= " AND s.subject_id = :subject_id";
    $params['subject_id'] = $subjectId;
}

if ($status !== '') {
    $sql .= " AND s.drift_status = :status";
    $params['status'] = $status;
}

if ($fromDate !== '') {
    $sql .= " AND DATE(s.session_date) >= :from_date";
    $params['from_date'] = $fromDate;
}

if ($toDate !== '') {
    $sql .= " AND DATE(s.session_date) <= :to_date";
    $params['to_date'] = $toDate;
}

$sql .= " ORDER BY s.session_date DESC, s.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$totalRows = count($rows);
$avgDrift = 0;
$highDriftCount = 0;
$moderateDriftCount = 0;
$lowDriftCount = 0;
$latestAnalyzed = null;

if ($rows) {
    $sumDrift = 0;
    foreach ($rows as $row) {
        $sumDrift += (float)$row['drift_score'];

        if (($row['drift_status'] ?? '') === 'High Drift') $highDriftCount++;
        if (($row['drift_status'] ?? '') === 'Moderate Drift') $moderateDriftCount++;
        if (($row['drift_status'] ?? '') === 'Low Drift') $lowDriftCount++;

        if (!$latestAnalyzed && !empty($row['analyzed_at'])) {
            $latestAnalyzed = $row['analyzed_at'];
        }
    }
    $avgDrift = $sumDrift / $totalRows;
}

require_once __DIR__ . '/../includes/header.php';

function report_badge($status)
{
    if ($status === 'High Drift') return 'badge-danger';
    if ($status === 'Moderate Drift') return 'badge-warning';
    if ($status === 'Low Drift') return 'badge-success';
    return 'badge-info';
}
?>

<div class="layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="content dashboard-pro">
        <div class="topbar topbar-pro">
            <div>
                <div class="eyebrow">Insights Export Center</div>
                <h1>Reports</h1>
                <p class="page-subtitle">Filter, review, and export monitored cognitive session analytics.</p>
            </div>
        </div>

        <div class="cards dashboard-cards pro-cards">
            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot cyan"></span>
                    <h3>Total Records</h3>
                </div>
                <p><?= e($totalRows) ?></p>
            </div>

            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot blue"></span>
                    <h3>Average Drift</h3>
                </div>
                <p><?= number_format((float)$avgDrift, 2) ?></p>
            </div>

            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot red"></span>
                    <h3>High Drift Cases</h3>
                </div>
                <p><?= e($highDriftCount) ?></p>
            </div>

            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot green"></span>
                    <h3>Latest Analysis</h3>
                </div>
                <p class="small-stat-text"><?= e($latestAnalyzed ?: 'Not analyzed') ?></p>
            </div>
        </div>

        <div class="panel chart-panel-pro">
            <div class="panel-header">
                <div>
                    <h2>Filter Reports</h2>
                    <p class="panel-subtitle">Narrow down by subject, drift status, and date range</p>
                </div>
            </div>

            <form method="GET" class="filter-form">
                <div class="reports-filter-grid">
                    <div class="form-group">
                        <label>Subject</label>
                        <select name="subject_id">
                            <option value="">All Subjects</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= e($subject['id']) ?>" <?= $subjectId === (int)$subject['id'] ? 'selected' : '' ?>>
                                    <?= e($subject['full_name']) ?> (<?= e($subject['subject_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All</option>
                            <option value="Low Drift" <?= $status === 'Low Drift' ? 'selected' : '' ?>>Low Drift</option>
                            <option value="Moderate Drift" <?= $status === 'Moderate Drift' ? 'selected' : '' ?>>Moderate Drift</option>
                            <option value="High Drift" <?= $status === 'High Drift' ? 'selected' : '' ?>>High Drift</option>
                            <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="from_date" value="<?= e($fromDate) ?>">
                    </div>

                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="to_date" value="<?= e($toDate) ?>">
                    </div>
                </div>

                <div class="reports-actions">
                    <button type="submit" class="btn">Apply Filters</button>
                    <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-secondary">Reset</a>

                    <a
                        class="btn"
                        href="<?= BASE_URL ?>/admin/export_excel.php?subject_id=<?= urlencode((string)$subjectId) ?>&status=<?= urlencode($status) ?>&from_date=<?= urlencode($fromDate) ?>&to_date=<?= urlencode($toDate) ?>"
                    >
                        Download Excel Report
                    </a>
                </div>
            </form>
        </div>

        <div class="panel chart-panel-pro">
            <div class="panel-header">
                <div>
                    <h2>Report Data</h2>
                    <p class="panel-subtitle">Complete filtered view of cognitive session analytics</p>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Session ID</th>
                            <th>Subject</th>
                            <th>Code</th>
                            <th>Date</th>
                            <th>Reaction</th>
                            <th>Confidence</th>
                            <th>Quiz</th>
                            <th>Drift</th>
                            <th>Status</th>
                            <th>Risk</th>
                            <th>Analyzed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rows): ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= e($row['session_id']) ?></td>
                                    <td><?= e($row['full_name']) ?></td>
                                    <td><?= e($row['subject_code']) ?></td>
                                    <td><?= e($row['session_date']) ?></td>
                                    <td><?= number_format((float)$row['reaction_avg'], 2) ?></td>
                                    <td><?= number_format((float)$row['confidence_score'], 2) ?></td>
                                    <td><?= number_format((float)$row['quiz_score'], 2) ?></td>
                                    <td><?= number_format((float)$row['drift_score'], 2) ?></td>
                                    <td>
                                        <span class="badge <?= report_badge($row['drift_status']) ?>">
                                            <?= e($row['drift_status']) ?>
                                        </span>
                                    </td>
                                    <td><?= e($row['risk_level'] ?: 'N/A') ?></td>
                                    <td><?= e($row['analyzed_at'] ?: 'Not analyzed') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11">No data found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>