<?php
require_once __DIR__ . '/../utils/security.php';
require_once __DIR__ . '/../utils/bootstrap.php';
secure_session_start();
check_user_active();
$controller = new \App\Controllers\UserController();
$controller->dashboard();

