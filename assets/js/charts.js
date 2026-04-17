document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') {
        return;
    }

    const driftCanvas = document.getElementById('driftChart');
    if (driftCanvas && typeof window.driftChartData !== 'undefined') {
        const ctx = driftCanvas.getContext('2d');
        const data = window.driftChartData;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Drift Score',
                        data: data.driftScores,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.08)',
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'Reaction Average',
                        data: data.reactionScores,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.08)',
                        tension: 0.35,
                        fill: false
                    },
                    {
                        label: 'Confidence Score',
                        data: data.confidenceScores,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.08)',
                        tension: 0.35,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    const subjectCanvas = document.getElementById('subjectTrendChart');
    if (subjectCanvas && typeof window.subjectTrendChartData !== 'undefined') {
        const ctx = subjectCanvas.getContext('2d');
        const data = window.subjectTrendChartData;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Drift',
                        data: data.drift,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.08)',
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'Quiz',
                        data: data.quiz,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.08)',
                        tension: 0.35,
                        fill: false
                    },
                    {
                        label: 'Confidence',
                        data: data.confidence,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.08)',
                        tension: 0.35,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    const dashboardCanvas = document.getElementById('dashboardTrendChart');
    if (dashboardCanvas && typeof window.dashboardTrendChartData !== 'undefined') {
        const ctx = dashboardCanvas.getContext('2d');
        const data = window.dashboardTrendChartData;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Average Drift',
                        data: data.drift,
                        borderColor: '#7c3aed',
                        backgroundColor: 'rgba(124, 58, 237, 0.08)',
                        tension: 0.35,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});