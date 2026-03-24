<?php
require_once __DIR__ . '/../utils/security.php';
require_once __DIR__ . '/../utils/bootstrap.php';
secure_session_start();
$controller = new \App\Controllers\UserController();
$controller->dashboard();

