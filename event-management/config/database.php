<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'eventpro');
define('DB_USER', 'root');
define('DB_PASS', '');

// Auto-detect project base path so redirects work in subfolders (e.g. /event-management).
if (!defined('BASE_URL')) {
    $baseUrl = '';
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $projectRoot = realpath(__DIR__ . '/..');

    if ($documentRoot && $projectRoot) {
        $documentRoot = rtrim(str_replace('\\', '/', realpath($documentRoot) ?: $documentRoot), '/');
        $projectRoot = str_replace('\\', '/', $projectRoot);

        if (strpos($projectRoot, $documentRoot) === 0) {
            $relative = trim(substr($projectRoot, strlen($documentRoot)), '/');
            $baseUrl = $relative !== '' ? '/' . $relative : '';
        }
    }

    define('BASE_URL', $baseUrl);
}

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch (PDOException $e) {
            die('<div style="font-family:monospace;padding:20px;background:#1a1a2e;color:#ff6b6b;border:1px solid #ff6b6b;border-radius:8px;margin:20px;">
                <strong>Database Error:</strong> ' . htmlspecialchars($e->getMessage()) . '<br><br>
                Please run <a href="' . BASE_URL . '/setup/install.php" style="color:#6366f1">setup/install.php</a> first.
            </div>');
        }
    }
    return $pdo;
}
