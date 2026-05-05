<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUser(): array {
    return [
        'id'    => $_SESSION['user_id'] ?? null,
        'name'  => $_SESSION['name'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role'  => $_SESSION['role'] ?? '',
    ];
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireRole(string ...$roles): void {
    requireLogin();
    $user = currentUser();
    if (!in_array($user['role'], $roles, true)) {
        $map = [
            'admin'     => BASE_URL . '/admin/dashboard.php',
            'organizer' => BASE_URL . '/organizer/dashboard.php',
            'attendee'  => BASE_URL . '/attendee/dashboard.php',
        ];
        header('Location: ' . ($map[$user['role']] ?? BASE_URL . '/login.php'));
        exit;
    }
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function formatPrice(float $price): string {
    return $price == 0 ? 'Free' : '$' . number_format($price, 2);
}

function generateTicketNumber(int $eventId): string {
    return 'TKT-' . strtoupper(substr(md5($eventId . microtime() . rand()), 0, 8));
}

function timeAgo(string $datetime): string {
    $time = time() - strtotime($datetime);
    if ($time < 60)    return 'just now';
    if ($time < 3600)  return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    return floor($time/86400) . 'd ago';
}
