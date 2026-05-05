<?php
require_once 'config/database.php';
require_once 'config/auth.php';
session_destroy();
header('Location: ' . BASE_URL . '/login.php');
exit;
