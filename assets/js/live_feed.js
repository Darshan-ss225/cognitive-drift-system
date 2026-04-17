document.addEventListener('DOMContentLoaded', function () {
    const telemetryTable = document.getElementById('telemetryTableBody');
    const refreshBtn = document.getElementById('refreshTelemetryBtn');
    const lastRefresh = document.getElementById('lastRefreshText');
    const latestDrift = document.getElementById('latestDriftText');
    const latestRisk = document.getElementById('latestRiskText');
    const feedStatus = document.getElementById('feedStatusText');

    if (!telemetryTable) return;

    async function loadTelemetry() {
        try {
            if (feedStatus) {
                feedStatus.innerText = 'Loading...';
                feedStatus.classList.remove('live-on', 'live-off');
            }

            const response = await fetch('/cognitive-drift-system/api/live_telemetry.php');
            const data = await response.json();

            if (!data.success) {
                telemetryTable.innerHTML = '<tr><td colspan="8">Unable to load telemetry.</td></tr>';
                if (feedStatus) {
                    feedStatus.innerText = 'Error';
                    feedStatus.classList.add('live-off');
                }
                return;
            }

            let html = '';

            if (!data.telemetry || data.telemetry.length === 0) {
                html = '<tr><td colspan="8">No live data found.</td></tr>';
            } else {
                data.telemetry.forEach(row => {
                    let badge = 'badge-info';

                    if (row.drift_status === 'High Drift') {
                        badge = 'badge-danger';
                    } else if (row.drift_status === 'Moderate Drift') {
                        badge = 'badge-warning';
                    } else if (row.drift_status === 'Low Drift') {
                        badge = 'badge-success';
                    }

                    html += `
                        <tr>
                            <td>${row.full_name} (${row.subject_code})</td>
                            <td>${row.session_date ?? ''}</td>
                            <td>${parseFloat(row.reaction_avg || 0).toFixed(2)}</td>
                            <td>${parseFloat(row.confidence_score || 0).toFixed(2)}</td>
                            <td>${parseFloat(row.quiz_score || 0).toFixed(2)}</td>
                            <td>${parseFloat(row.drift_score || 0).toFixed(2)}</td>
                            <td><span class="badge ${badge}">${row.drift_status || 'N/A'}</span></td>
                            <td>${row.risk_level || 'N/A'}</td>
                        </tr>
                    `;
                });
            }

            telemetryTable.innerHTML = html;

            if (lastRefresh) {
                lastRefresh.innerText = new Date().toLocaleTimeString();
            }

            if (data.telemetry && data.telemetry.length > 0) {
                if (latestDrift) {
                    latestDrift.innerText = parseFloat(data.telemetry[0].drift_score || 0).toFixed(2);
                }
                if (latestRisk) {
                    latestRisk.innerText = data.telemetry[0].risk_level || 'N/A';
                }
            }

            if (feedStatus) {
                feedStatus.innerText = 'Live';
                feedStatus.classList.add('live-on');
                feedStatus.classList.remove('live-off');
            }
        } catch (error) {
            console.error(error);
            telemetryTable.innerHTML = '<tr><td colspan="8">Server error while loading telemetry.</td></tr>';

            if (feedStatus) {
                feedStatus.innerText = 'Error';
                feedStatus.classList.add('live-off');
                feedStatus.classList.remove('live-on');
            }
        }
    }

    loadTelemetry();
    setInterval(loadTelemetry, 5000);

    if (refreshBtn) {
        refreshBtn.addEventListener('click', loadTelemetry);
    }
});