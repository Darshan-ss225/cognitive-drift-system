<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Live Monitoring';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="content live-pro-page">
        <div class="topbar topbar-pro">
            <div>
                <div class="eyebrow">Telemetry Center</div>
                <h1>Live Monitoring</h1>
                <p class="page-subtitle">Real-time monitoring of session telemetry, drift values, and risk indicators.</p>
            </div>

            <div class="topbar-actions">
                <button type="button" class="btn" id="refreshTelemetryBtn">Refresh Now</button>
            </div>
        </div>

        <div class="cards dashboard-cards pro-cards">
            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot cyan"></span>
                    <h3>Feed Status</h3>
                </div>
                <p id="feedStatusText">Live</p>
            </div>

            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot blue"></span>
                    <h3>Last Refresh</h3>
                </div>
                <p id="lastRefreshText">--</p>
            </div>

            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot red"></span>
                    <h3>Latest Drift</h3>
                </div>
                <p id="latestDriftText">--</p>
            </div>

            <div class="card stat-card-pro">
                <div class="stat-head">
                    <span class="stat-dot green"></span>
                    <h3>Latest Risk</h3>
                </div>
                <p id="latestRiskText">--</p>
            </div>
        </div>

        <div class="panel live-feed-panel">
            <div class="panel-header">
                <div>
                    <h2>Telemetry Feed</h2>
                    <p class="panel-subtitle">Auto-refreshing stream of recent cognitive session telemetry</p>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Session Date</th>
                            <th>Reaction</th>
                            <th>Confidence</th>
                            <th>Quiz</th>
                            <th>Drift</th>
                            <th>Status</th>
                            <th>Risk</th>
                        </tr>
                    </thead>
                    <tbody id="telemetryTableBody">
                        <tr>
                            <td colspan="8">Loading telemetry...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>