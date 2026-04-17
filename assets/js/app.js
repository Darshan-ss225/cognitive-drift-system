document.addEventListener('DOMContentLoaded', function () {
    const analyzeButtons = document.querySelectorAll('.analyze-session-btn');

    analyzeButtons.forEach(btn => {
        btn.addEventListener('click', async function () {
            const sessionId = this.getAttribute('data-session-id');

            if (!sessionId) {
                alert('Invalid session ID');
                return;
            }

            const originalText = this.innerText;
            this.innerText = 'Analyzing...';
            this.disabled = true;

            try {
                const response = await fetch('/cognitive-drift-system/api/analyze_session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        session_id: sessionId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('AI Analysis Completed');
                    window.location.href = window.location.href;
                    return;
                } else {
                    alert(data.message || 'Analysis failed');
                    console.error(data);
                }
            } catch (err) {
                console.error(err);
                alert('Server error while analyzing');
            }

            this.innerText = originalText;
            this.disabled = false;
        });
    });

    const telemetryTable = document.getElementById('telemetryTableBody');

    if (telemetryTable) {
        async function loadTelemetry() {
            try {
                const response = await fetch('/cognitive-drift-system/api/live_telemetry.php');
                const data = await response.json();

                if (!data.success) return;

                let html = '';

                if (!data.telemetry || data.telemetry.length === 0) {
                    html = '<tr><td colspan="8">No live data</td></tr>';
                } else {
                    data.telemetry.forEach(row => {
                        let badge = 'badge-info';
                        if (row.drift_status === 'High Drift') badge = 'badge-danger';
                        else if (row.drift_status === 'Moderate Drift') badge = 'badge-warning';
                        else if (row.drift_status === 'Low Drift') badge = 'badge-success';

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

                const lastRefresh = document.getElementById('lastRefreshText');
                const latestDrift = document.getElementById('latestDriftText');
                const latestRisk = document.getElementById('latestRiskText');

                if (lastRefresh) lastRefresh.innerText = new Date().toLocaleTimeString();

                if (data.telemetry && data.telemetry.length > 0) {
                    if (latestDrift) latestDrift.innerText = parseFloat(data.telemetry[0].drift_score || 0).toFixed(2);
                    if (latestRisk) latestRisk.innerText = data.telemetry[0].risk_level || 'N/A';
                }
            } catch (err) {
                console.error(err);
            }
        }

        loadTelemetry();
        setInterval(loadTelemetry, 5000);

        const refreshBtn = document.getElementById('refreshTelemetryBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', loadTelemetry);
        }
    }
});