<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'Cognitive Drift System');
define('APP_VERSION', '1.0.0');

define('BASE_URL', '/cognitive-drift-system');
define('PYTHON_AI_URL', 'http://127.0.0.1:5000');

define('DB_HOST', 'localhost');
define('DB_NAME', 'cognitive_drift');
define('DB_USER', 'root');
define('DB_PASS', '');

date_default_timezone_set('Asia/Kolkata');

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}