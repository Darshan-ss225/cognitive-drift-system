<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Dashboard';

$totalSubjects = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
$totalSessions = $pdo->query("SELECT COUNT(*) FROM sessions")->fetchColumn();
$totalAlerts = $pdo->query("SELECT COUNT(*) FROM alerts WHERE status = 'Active'")->fetchColumn();
$avgDrift = $pdo->query("SELECT COALESCE(AVG(drift_score), 0) FROM sessions")->fetchColumn();

$recent = $pdo->query("
    SELECT s.*, sub.full_name
    FROM sessions s
    JOIN subjects sub ON s.subject_id = sub.id
    ORDER BY s.id DESC
    LIMIT 5
")->fetchAll();

$trendRows = $pdo->query("
    SELECT DATE(session_date) AS session_day, COALESCE(AVG(drift_score), 0) AS avg_drift
    FROM sessions
    GROUP BY DATE(session_date)
    ORDER BY session_day ASC
    LIMIT 7
")->fetchAll();

$chartLabels = [];
$chartData = [];

foreach ($trendRows as $row) {
    $chartLabels[] = $row['session_day'];
    $chartData[] = (float)$row['avg_drift'];
}

require_once __DIR__ . '/../includes/header.php';

function badge($status) {
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
                <div class="eyebrow">AI Command Center</div>
                <h1>AI Dashboard</h1>
                <p class="page-subtitle">Live overview of cognitive monitoring, drift status, and system activity.</p>
            </div>
        </div>

        <div class="cards dashboard-cards pro-cards">
            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot cyan"></span>
                    <h3>Total Subjects</h3>
                </div>
                <p><?= e($totalSubjects) ?></p>
            </div>

            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot blue"></span>
                    <h3>Sessions</h3>
                </div>
                <p><?= e($totalSessions) ?></p>
            </div>

            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot red"></span>
                    <h3>Alerts</h3>
                </div>
                <p><?= e($totalAlerts) ?></p>
            </div>

            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot green"></span>
                    <h3>Avg Drift</h3>
                </div>
                <p><?= number_format((float)$avgDrift, 2) ?></p>
            </div>
        </div>

        <div class="dashboard-grid dashboard-grid-pro">
            <div class="panel chart-panel-pro">
                <div class="panel-header">
                    <div>
                        <h2>Drift Trend</h2>
                        <p class="panel-subtitle">Average drift pattern across recent sessions</p>
                    </div>
                </div>

                <div class="chart-wrap chart-wrap-pro">
                    <canvas id="chart"></canvas>
                </div>
            </div>

            <div class="panel recent-panel-pro">
                <div class="panel-header">
                    <div>
                        <h2>Recent Sessions</h2>
                        <p class="panel-subtitle">Latest analyzed cognitive sessions</p>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Score</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($recent): ?>
                                <?php foreach ($recent as $r): ?>
                                    <tr>
                                        <td><?= e($r['full_name']) ?></td>
                                        <td><?= number_format((float)$r['drift_score'], 2) ?></td>
                                        <td>
                                            <span class="badge <?= badge($r['drift_status']) ?>">
                                                <?= e($r['drift_status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3">No session data found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('chart');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Average Drift',
            data: <?= json_encode($chartData) ?>,
            borderColor: '#00eaff',
            backgroundColor: 'rgba(0, 234, 255, 0.10)',
            fill: true,
            tension: 0.42,
            borderWidth: 2.5,
            pointRadius: 3.5,
            pointHoverRadius: 5,
            pointBackgroundColor: '#00eaff',
            pointBorderColor: '#0b1220',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 900
        },
        plugins: {
            legend: {
                labels: {
                    color: '#e2e8f0',
                    boxWidth: 34,
                    boxHeight: 10
                }
            },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                titleColor: '#ffffff',
                bodyColor: '#cbd5e1',
                borderColor: 'rgba(0,234,255,0.18)',
                borderWidth: 1
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