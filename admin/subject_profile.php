<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Subject Profile';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    set_flash('error', 'Invalid subject selected.');
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

$summaryStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_sessions,
        COALESCE(AVG(drift_score), 0) AS avg_drift,
        COALESCE(MAX(drift_score), 0) AS max_drift,
        COALESCE(AVG(reaction_avg), 0) AS avg_reaction,
        COALESCE(AVG(confidence_score), 0) AS avg_confidence,
        COALESCE(AVG(quiz_score), 0) AS avg_quiz
    FROM sessions
    WHERE subject_id = :subject_id
");
$summaryStmt->execute(['subject_id' => $id]);
$summary = $summaryStmt->fetch() ?: [
    'total_sessions' => 0,
    'avg_drift' => 0,
    'max_drift' => 0,
    'avg_reaction' => 0,
    'avg_confidence' => 0,
    'avg_quiz' => 0
];

$baselineStmt = $pdo->prepare("
    SELECT *
    FROM baseline_assessments
    WHERE subject_id = :subject_id
    ORDER BY id DESC
    LIMIT 1
");
$baselineStmt->execute(['subject_id' => $id]);
$baseline = $baselineStmt->fetch();

$sessionStmt = $pdo->prepare("
    SELECT *
    FROM sessions
    WHERE subject_id = :subject_id
    ORDER BY session_date DESC, id DESC
");
$sessionStmt->execute(['subject_id' => $id]);
$sessions = $sessionStmt->fetchAll();

$alertStmt = $pdo->prepare("
    SELECT *
    FROM alerts
    WHERE subject_id = :subject_id
    ORDER BY created_at DESC, id DESC
    LIMIT 5
");
$alertStmt->execute(['subject_id' => $id]);
$alerts = $alertStmt->fetchAll();

$latestSession = $sessions[0] ?? null;

$chartLabels = [];
$chartDrift = [];
$chartQuiz = [];
$chartConfidence = [];

$chartSessions = array_reverse($sessions);
foreach ($chartSessions as $row) {
    $chartLabels[] = date('Y-m-d', strtotime($row['session_date']));
    $chartDrift[] = (float)($row['drift_score'] ?? 0);
    $chartQuiz[] = (float)($row['quiz_score'] ?? 0);
    $chartConfidence[] = (float)($row['confidence_score'] ?? 0);
}

require_once __DIR__ . '/../includes/header.php';

function profile_badge(string $status): string
{
    if ($status === 'High Drift') return 'badge-danger';
    if ($status === 'Moderate Drift') return 'badge-warning';
    if ($status === 'Low Drift') return 'badge-success';
    return 'badge-info';
}

function subject_status_badge(?string $status): string
{
    if ($status === 'active') return 'badge-success';
    if ($status === 'inactive') return 'badge-warning';
    return 'badge-info';
}
?>

<div class="layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="content dashboard-pro">
        <div class="topbar topbar-pro">
            <div>
                <div class="eyebrow">Subject Intelligence</div>
                <h1>Subject Profile</h1>
                <p class="page-subtitle">Complete behavior history, baseline comparison, drift pattern, and recent alerts.</p>
            </div>

            <div class="topbar-actions">
                <a href="<?= BASE_URL ?>/admin/edit_subject.php?id=<?= e($subject['id']) ?>" class="btn btn-secondary">Edit Subject</a>
                <a href="<?= BASE_URL ?>/admin/subjects.php" class="btn">Back</a>
            </div>
        </div>

        <?php require_once __DIR__ . '/../includes/alerts_helper.php'; ?>

        <div class="cards dashboard-cards pro-cards">
            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot cyan"></span>
                    <h3>Total Sessions</h3>
                </div>
                <p><?= e($summary['total_sessions']) ?></p>
            </div>

            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot blue"></span>
                    <h3>Average Drift</h3>
                </div>
                <p><?= number_format((float)$summary['avg_drift'], 2) ?></p>
            </div>

            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot red"></span>
                    <h3>Peak Drift</h3>
                </div>
                <p><?= number_format((float)$summary['max_drift'], 2) ?></p>
            </div>

            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot green"></span>
                    <h3>Average Quiz</h3>
                </div>
                <p><?= number_format((float)$summary['avg_quiz'], 2) ?></p>
            </div>
        </div>

        <div class="profile-grid">
            <div class="panel chart-panel-pro">
                <div class="panel-header">
                    <div>
                        <h2>Basic Details</h2>
                        <p class="panel-subtitle">Core subject information and current baseline state</p>
                    </div>
                </div>

                <div class="profile-info-list">
                    <p><strong>Subject Code:</strong> <?= e($subject['subject_code'] ?? 'N/A') ?></p>
                    <p><strong>Full Name:</strong> <?= e($subject['full_name'] ?? 'N/A') ?></p>
                    <p><strong>Username:</strong> <?= e($subject['username'] ?: 'N/A') ?></p>
                    <p><strong>Email:</strong> <?= e($subject['email'] ?: 'N/A') ?></p>
                    <p><strong>Phone:</strong> <?= e($subject['phone'] ?: 'N/A') ?></p>
                    <p>
                        <strong>Status:</strong>
                        <span class="badge <?= subject_status_badge($subject['status'] ?? null) ?>">
                            <?= e(ucfirst($subject['status'] ?? 'unknown')) ?>
                        </span>
                    </p>
                    <p><strong>Baseline Score:</strong> <?= $subject['baseline_score'] !== null ? number_format((float)$subject['baseline_score'], 2) : 'N/A' ?></p>
                    <p><strong>Baseline Status:</strong> <?= e($subject['baseline_status'] ?: 'Pending') ?></p>
                    <p><strong>Created At:</strong> <?= e($subject['created_at'] ?? 'N/A') ?></p>
                </div>
            </div>

            <div class="panel chart-panel-pro">
                <div class="panel-header">
                    <div>
                        <h2>Baseline vs Latest Session</h2>
                        <p class="panel-subtitle">Compare normal reference values against the latest observed state</p>
                    </div>
                </div>

                <div class="profile-info-list">
                    <?php if ($baseline): ?>
                        <p><strong>Baseline Reaction:</strong> <?= number_format((float)$baseline['baseline_reaction_avg'], 2) ?></p>
                        <p><strong>Baseline Confidence:</strong> <?= number_format((float)$baseline['baseline_confidence_score'], 2) ?></p>
                        <p><strong>Baseline Quiz:</strong> <?= number_format((float)$baseline['baseline_quiz_score'], 2) ?></p>
                        <p><strong>Baseline Notes:</strong> <?= e($baseline['notes'] ?: 'N/A') ?></p>
                        <hr class="profile-divider">
                    <?php else: ?>
                        <p><strong>Baseline:</strong> Not created yet.</p>
                        <hr class="profile-divider">
                    <?php endif; ?>

                    <?php if ($latestSession): ?>
                        <p><strong>Latest Session Date:</strong> <?= e($latestSession['session_date']) ?></p>
                        <p><strong>Latest Reaction:</strong> <?= number_format((float)$latestSession['reaction_avg'], 2) ?></p>
                        <p><strong>Latest Confidence:</strong> <?= number_format((float)$latestSession['confidence_score'], 2) ?></p>
                        <p><strong>Latest Quiz:</strong> <?= number_format((float)$latestSession['quiz_score'], 2) ?></p>
                        <p>
                            <strong>Latest Drift Status:</strong>
                            <span class="badge <?= profile_badge($latestSession['drift_status'] ?? '') ?>">
                                <?= e($latestSession['drift_status'] ?: 'Pending') ?>
                            </span>
                        </p>
                    <?php else: ?>
                        <p><strong>Latest Session:</strong> No sessions found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="panel chart-panel-pro trend-panel-pro">
            <div class="panel-header">
                <div>
                    <h2>Subject Trend</h2>
                    <p class="panel-subtitle">Drift, quiz, and confidence progression over time</p>
                </div>

                <div class="trend-meta">
                    <span class="trend-chip">Sessions: <?= count($sessions) ?></span>
                    <span class="trend-chip">Baseline: <?= $subject['baseline_score'] !== null ? number_format((float)$subject['baseline_score'], 2) : 'N/A' ?></span>
                </div>
            </div>

            <?php if (!empty($sessions)): ?>
                <div class="chart-wrap chart-wrap-pro trend-chart-wrap">
                    <canvas id="subjectTrendChart"></canvas>
                </div>
            <?php else: ?>
                <div class="empty-trend-state">
                    <div class="empty-trend-icon">📈</div>
                    <h3>No Subject Trend Yet</h3>
                    <p>This graph will appear after the subject completes sessions and AI analysis is performed.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="dashboard-grid dashboard-grid-pro">
            <div class="panel recent-panel-pro">
                <div class="panel-header">
                    <div>
                        <h2>Recent Alerts</h2>
                        <p class="panel-subtitle">Latest alert signals linked to this subject</p>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($alerts): ?>
                                <?php foreach ($alerts as $alert): ?>
                                    <tr>
                                        <td><?= e($alert['alert_type'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge <?= (($alert['status'] ?? '') === 'Resolved') ? 'badge-success' : 'badge-danger' ?>">
                                                <?= e($alert['status'] ?: 'Active') ?>
                                            </span>
                                        </td>
                                        <td><?= e($alert['created_at'] ?? 'N/A') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3">No alerts found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel chart-panel-pro">
                <div class="panel-header">
                    <div>
                        <h2>Performance Snapshot</h2>
                        <p class="panel-subtitle">Average observed subject metrics across completed sessions</p>
                    </div>
                </div>

                <div class="profile-info-list">
                    <p><strong>Average Reaction:</strong> <?= number_format((float)$summary['avg_reaction'], 2) ?></p>
                    <p><strong>Average Confidence:</strong> <?= number_format((float)$summary['avg_confidence'], 2) ?></p>
                    <p><strong>Average Quiz:</strong> <?= number_format((float)$summary['avg_quiz'], 2) ?></p>
                    <p><strong>Average Drift:</strong> <?= number_format((float)$summary['avg_drift'], 2) ?></p>
                    <p><strong>Peak Drift:</strong> <?= number_format((float)$summary['max_drift'], 2) ?></p>
                </div>
            </div>
        </div>

        <div class="panel chart-panel-pro">
            <div class="panel-header">
                <div>
                    <h2>Session History</h2>
                    <p class="panel-subtitle">Complete historical session timeline for this subject</p>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
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
                                    <td><?= e($session['session_date'] ?? 'N/A') ?></td>
                                    <td><?= number_format((float)($session['reaction_avg'] ?? 0), 2) ?></td>
                                    <td><?= number_format((float)($session['confidence_score'] ?? 0), 2) ?></td>
                                    <td><?= number_format((float)($session['quiz_score'] ?? 0), 2) ?></td>
                                    <td><?= number_format((float)($session['drift_score'] ?? 0), 2) ?></td>
                                    <td>
                                        <span class="badge <?= profile_badge($session['drift_status'] ?? '') ?>">
                                            <?= e($session['drift_status'] ?: 'Pending') ?>
                                        </span>
                                    </td>
                                    <td><?= e($session['risk_level'] ?: 'N/A') ?></td>
                                    <td>
                                        <a class="btn btn-sm" href="<?= BASE_URL ?>/admin/session_detail.php?id=<?= e($session['id']) ?>">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">No sessions available for this subject.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($sessions)): ?>
<script>
const subjectTrendCtx = document.getElementById('subjectTrendChart');

new Chart(subjectTrendCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
            {
                label: 'Drift Score',
                data: <?= json_encode($chartDrift) ?>,
                borderColor: '#00eaff',
                backgroundColor: 'rgba(0, 234, 255, 0.10)',
                fill: true,
                tension: 0.45,
                borderWidth: 3,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#00eaff',
                pointBorderColor: '#06101f',
                pointBorderWidth: 2
            },
            {
                label: 'Quiz Score',
                data: <?= json_encode($chartQuiz) ?>,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.04)',
                fill: false,
                tension: 0.35,
                borderWidth: 2.5,
                pointRadius: 3,
                pointHoverRadius: 5,
                pointBackgroundColor: '#f59e0b',
                pointBorderColor: '#06101f',
                pointBorderWidth: 2
            },
            {
                label: 'Confidence Score',
                data: <?= json_encode($chartConfidence) ?>,
                borderColor: '#22c55e',
                backgroundColor: 'rgba(34, 197, 94, 0.04)',
                fill: false,
                tension: 0.35,
                borderWidth: 2.5,
                pointRadius: 3,
                pointHoverRadius: 5,
                pointBackgroundColor: '#22c55e',
                pointBorderColor: '#06101f',
                pointBorderWidth: 2
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        animation: {
            duration: 1000
        },
        plugins: {
            legend: {
                labels: {
                    color: '#e2e8f0',
                    boxWidth: 32,
                    boxHeight: 10,
                    padding: 16
                }
            },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.96)',
                titleColor: '#ffffff',
                bodyColor: '#dbe7f3',
                borderColor: 'rgba(0,234,255,0.18)',
                borderWidth: 1,
                padding: 12
            }
        },
        scales: {
            x: {
                ticks: {
                    color: '#b6c2d2',
                    maxRotation: 0,
                    autoSkip: true
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
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>