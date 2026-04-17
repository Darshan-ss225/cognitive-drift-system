<?php
require_once __DIR__ . '/config/config.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

header('Location: ' . BASE_URL . '/login.php');
exit;