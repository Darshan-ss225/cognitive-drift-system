<?php
$current = basename($_SERVER['PHP_SELF']);

function active($file) {
    global $current;
    return $current === $file ? 'active' : '';
}
?>

<div class="sidebar pro-sidebar">
    <div class="logo-wrap pro-logo-wrap">
        <div class="logo-icon pulse-icon">🧠</div>
        <div class="logo-text">
            <div class="logo-title">Cognitive Drift</div>
            <div class="logo-subtitle">AI Monitoring Panel</div>
        </div>
    </div>

    <nav class="sidebar-nav pro-sidebar-nav">
        <a class="<?= active('dashboard.php') ?>" href="<?= BASE_URL ?>/admin/dashboard.php">
            <span class="nav-icon">📊</span>
            <span>Dashboard</span>
        </a>

        <a class="<?= active('live_monitoring.php') ?>" href="<?= BASE_URL ?>/admin/live_monitoring.php">
            <span class="nav-icon">📡</span>
            <span>Live Monitoring</span>
        </a>

        <a class="<?= active('subjects.php') ?>" href="<?= BASE_URL ?>/admin/subjects.php">
            <span class="nav-icon">👤</span>
            <span>Subjects</span>
        </a>

        <a class="<?= active('add_subject.php') ?>" href="<?= BASE_URL ?>/admin/add_subject.php">
            <span class="nav-icon">➕</span>
            <span>Add Subject</span>
        </a>

        <a class="<?= active('edit_subject.php') ?>" href="<?= BASE_URL ?>/admin/subjects.php">
            <span class="nav-icon">✏️</span>
            <span>Edit Subject</span>
        </a>

        <a class="<?= active('subject_profile.php') ?>" href="<?= BASE_URL ?>/admin/subjects.php">
            <span class="nav-icon">🪪</span>
            <span>Subject Profile</span>
        </a>

        <a class="<?= active('baseline_assessment.php') ?>" href="<?= BASE_URL ?>/admin/baseline_assessment.php">
            <span class="nav-icon">🧪</span>
            <span>Baseline Assessment</span>
        </a>

        <a class="<?= active('sessions.php') ?>" href="<?= BASE_URL ?>/admin/sessions.php">
            <span class="nav-icon">🗂️</span>
            <span>Sessions</span>
        </a>

        <a class="<?= active('session_detail.php') ?>" href="<?= BASE_URL ?>/admin/sessions.php">
            <span class="nav-icon">📄</span>
            <span>Session Detail</span>
        </a>

        <a class="<?= active('drift_analysis.php') ?>" href="<?= BASE_URL ?>/admin/drift_analysis.php">
            <span class="nav-icon">📈</span>
            <span>Drift Analysis</span>
        </a>

        <a class="<?= active('alerts.php') ?>" href="<?= BASE_URL ?>/admin/alerts.php">
            <span class="nav-icon">🚨</span>
            <span>Alerts</span>
        </a>

        <a class="<?= active('reports.php') ?>" href="<?= BASE_URL ?>/admin/reports.php">
            <span class="nav-icon">📑</span>
            <span>Reports</span>
        </a>

        <a class="<?= active('system_logs.php') ?>" href="<?= BASE_URL ?>/admin/system_logs.php">
            <span class="nav-icon">📜</span>
            <span>System Logs</span>
        </a>

        <a class="<?= active('settings.php') ?>" href="<?= BASE_URL ?>/admin/settings.php">
            <span class="nav-icon">⚙️</span>
            <span>Settings</span>
        </a>

        <a class="<?= active('profile.php') ?>" href="<?= BASE_URL ?>/admin/profile.php">
            <span class="nav-icon">🙍</span>
            <span>Profile</span>
        </a>

        <a class="logout-link" href="<?= BASE_URL ?>/logout.php">
            <span class="nav-icon">🚪</span>
            <span>Logout</span>
        </a>
    </nav>
</div>