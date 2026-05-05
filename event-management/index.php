<?php
require_once 'config/database.php';
require_once 'config/auth.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$role = currentUser()['role'];
header('Location: ' . BASE_URL . '/' . $role . '/dashboard.php');
exit;
