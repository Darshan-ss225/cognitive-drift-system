<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/functions.php';

if (empty($_SESSION['admin_id'])) {
    set_flash('error', 'Please login first.');
    redirect('login.php');
}