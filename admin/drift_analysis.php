<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Drift Analysis';

$subjectFilter = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

$subjectOptionsStmt = $pdo->query("
    SELECT id, full_name, subject_code
    FROM subjects
    ORDER BY full_name ASC
");
$subjectOptions = $subjectOptionsStmt->fetchAll();

$sql = "
    SELECT
        s.id,
        s.subject_id,
        s.session_date,
        s.drift_score,
        s.drift_status,
        s.reaction_avg,
        s.confidence_score,
        s.quiz_score,
        s.risk_level,
        sub.full_name,
        sub.subject_code
    FROM sessions s
    JOIN subjects sub ON s.subject_id = sub.id
    WHERE 1=1
";

$params = [];

if ($subjectFilter > 0) {
    $sql .= " AND s.subject_id = :subject_id";
    $params['subject_id'] = $subjectFilter;
}

$sql .= " ORDER BY s.session_date ASC, s.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$labels = [];
$driftScores = [];
$reactionScores = [];
$confidenceScores = [];

foreach ($rows as $row) {
    $labels[] = date('Y-m-d', strtotime($row['session_date']));
    $driftScores[] = (float)$row['drift_score'];
    $reactionScores[] = (float)$row['reaction_avg'];
    $confidenceScores[] = (float)$row['confidence_score'];
}

$summarySql = "
    SELECT
        COUNT(*) AS total_sessions,
        COALESCE(AVG(drift_score), 0) AS avg_drift,
        COALESCE(MAX(drift_score), 0) AS peak_drift,
        SUM(CASE WHEN drift_status = 'High Drift' THEN 1 ELSE 0 END) AS high_drift_count
    FROM sessions
    WHERE 1=1
";

$summaryParams = [];

if ($subjectFilter > 0) {
    $summarySql .= " AND subject_id = :subject_id";
    $summaryParams['subject_id'] = $subjectFilter;
}

$summaryStmt = $pdo->prepare($summarySql);
$summaryStmt->execute($summaryParams);
$summary = $summaryStmt->fetch();

require_once __DIR__ . '/../includes/header.php';

function drift_badge($status) {
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
                <div class="eyebrow">Behavior Analytics</div>
                <h1>Drift Analysis</h1>
                <p class="page-subtitle">Visual trend tracking and historical drift performance across monitored sessions.</p>
            </div>
        </div>

        <div class="cards dashboard-cards pro-cards">
            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot cyan"></span>
                    <h3>Total Sessions</h3>
                </div>
                <p><?= e($summary['total_sessions'] ?? 0) ?></p>
            </div>

            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot blue"></span>
                    <h3>Average Drift</h3>
                </div>
                <p><?= number_format((float)($summary['avg_drift'] ?? 0), 2) ?></p>
            </div>

            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot red"></span>
                    <h3>Peak Drift</h3>
                </div>
                <p><?= number_format((float)($summary['peak_drift'] ?? 0), 2) ?></p>
            </div>

            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot green"></span>
                    <h3>High Drift Cases</h3>
                </div>
                <p><?= e($summary['high_drift_count'] ?? 0) ?></p>
            </div>
        </div>

        <div class="panel chart-panel-pro">
            <div class="panel-header">
                <div>
                    <h2>Filter Analysis</h2>
                    <p class="panel-subtitle">Focus trend analysis for one subject or review all monitored data</p>
                </div>
            </div>

            <form method="GET" class="filter-form">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="subject_id">Select Subject</label>
                        <select name="subject_id" id="subject_id">
                            <option value="">All Subjects</option>
                            <?php foreach ($subjectOptions as $option): ?>
                                <option value="<?= e($option['id']) ?>" <?= ($subjectFilter === (int)$option['id']) ? 'selected' : '' ?>>
                                    <?= e($option['full_name']) ?> (<?= e($option['subject_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn">Filter</button>
                <a href="<?= BASE_URL ?>/admin/drift_analysis.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <div class="dashboard-grid dashboard-grid-pro">
            <div class="panel chart-panel-pro">
                <div class="panel-header">
                    <div>
                        <h2>Drift Trend</h2>
                        <p class="panel-subtitle">Drift, reaction average, and confidence signals over time</p>
                    </div>
                </div>

                <div class="chart-wrap chart-wrap-pro">
                    <canvas id="driftChart"></canvas>
                </div>
            </div>

            <div class="panel recent-panel-pro">
                <div class="panel-header">
                    <div>
                        <h2>Latest Analysis</h2>
                        <p class="panel-subtitle">Most recent drift states in the selected scope</p>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Drift</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($rows): ?>
                                <?php foreach (array_reverse(array_slice($rows, -6)) as $row): ?>
                                    <tr>
                                        <td><?= e(date('Y-m-d', strtotime($row['session_date']))) ?></td>
                                        <td><?= number_format((float)$row['drift_score'], 2) ?></td>
                                        <td>
                                            <span class="badge <?= drift_badge($row['drift_status']) ?>">
                                                <?= e($row['drift_status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3">No data found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel chart-panel-pro">
            <div class="panel-header">
                <div>
                    <h2>Analysis Data Table</h2>
                    <p class="panel-subtitle">Complete numerical record of subject behavior across all sessions</p>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Drift Score</th>
                            <th>Reaction Avg</th>
                            <th>Confidence</th>
                            <th>Quiz</th>
                            <th>Status</th>
                            <th>Risk</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rows): ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= e($row['full_name']) ?> (<?= e($row['subject_code']) ?>)</td>
                                    <td><?= e($row['session_date']) ?></td>
                                    <td><?= number_format((float)$row['drift_score'], 2) ?></td>
                                    <td><?= number_format((float)$row['reaction_avg'], 2) ?></td>
                                    <td><?= number_format((float)$row['confidence_score'], 2) ?></td>
                                    <td><?= number_format((float)$row['quiz_score'], 2) ?></td>
                                    <td>
                                        <span class="badge <?= drift_badge($row['drift_status']) ?>">
                                            <?= e($row['drift_status']) ?>
                                        </span>
                                    </td>
                                    <td><?= e($row['risk_level'] ?: 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">No drift analysis data found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const driftCtx = document.getElementById('driftChart');

new Chart(driftCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [
            {
                label: 'Drift Score',
                data: <?= json_encode($driftScores) ?>,
                borderColor: '#00eaff',
                backgroundColor: 'rgba(0,234,255,0.10)',
                fill: true,
                tension: 0.42,
                borderWidth: 2.5,
                pointRadius: 3
            },
            {
                label: 'Reaction Avg',
                data: <?= json_encode($reactionScores) ?>,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245,158,11,0.04)',
                fill: false,
                tension: 0.35,
                borderWidth: 2,
                pointRadius: 2
            },
            {
                label: 'Confidence',
                data: <?= json_encode($confidenceScores) ?>,
                borderColor: '#22c55e',
                backgroundColor: 'rgba(34,197,94,0.04)',
                fill: false,
                tension: 0.35,
                borderWidth: 2,
                pointRadius: 2
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: '#e2e8f0'
                }
            }
        },
        scales: {
            x: {
                ticks: {
                    color: '#b6c2d2'
                },
                grid: {
                    color: 'rgba(255,255,255,0.05)'
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#b6c2d2'
                },
                grid: {
                    color: 'rgba(255,255,255,0.05)'
                }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>