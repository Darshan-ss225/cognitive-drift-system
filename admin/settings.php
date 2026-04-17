<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'drift_threshold_low' => trim($_POST['drift_threshold_low'] ?? '0.30'),
        'drift_threshold_high' => trim($_POST['drift_threshold_high'] ?? '0.60'),
        'app_theme' => trim($_POST['app_theme'] ?? 'cyber-dark')
    ];

    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value)
        VALUES (:setting_key, :setting_value)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");

    foreach ($settings as $key => $value) {
        $stmt->execute([
            'setting_key' => $key,
            'setting_value' => $value
        ]);
    }

    set_flash('success', 'Settings updated successfully.');
    redirect('admin/settings.php');
}

$settingsRows = $pdo->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll();
$settings = [];
foreach ($settingsRows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="content">
        <div class="topbar">
            <h1>Settings</h1>
        </div>

        <?php require_once __DIR__ . '/../includes/alerts_helper.php'; ?>

        <div class="panel form-panel">
            <form method="POST">
                <div class="form-group">
                    <label>Low Drift Threshold</label>
                    <input type="number" step="0.01" name="drift_threshold_low" value="<?= e($settings['drift_threshold_low'] ?? '0.30') ?>">
                </div>

                <div class="form-group">
                    <label>High Drift Threshold</label>
                    <input type="number" step="0.01" name="drift_threshold_high" value="<?= e($settings['drift_threshold_high'] ?? '0.60') ?>">
                </div>

                <div class="form-group">
                    <label>Theme</label>
                    <select name="app_theme">
                        <option value="cyber-dark" <?= (($settings['app_theme'] ?? '') === 'cyber-dark') ? 'selected' : '' ?>>Cyber Dark</option>
                        <option value="clean-light" <?= (($settings['app_theme'] ?? '') === 'clean-light') ? 'selected' : '' ?>>Clean Light</option>
                    </select>
                </div>

                <button type="submit" class="btn">Save Settings</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>