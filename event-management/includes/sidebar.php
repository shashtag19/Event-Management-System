<?php
$user = currentUser();
$role = $user['role'];

$navItems = [
    'admin' => [
        ['icon' => 'grid', 'label' => 'Dashboard',      'href' => BASE_URL . '/admin/dashboard.php'],
        ['icon' => 'users', 'label' => 'Users',          'href' => BASE_URL . '/admin/users.php'],
        ['icon' => 'calendar', 'label' => 'All Events',  'href' => BASE_URL . '/admin/events.php'],
        ['icon' => 'tag', 'label' => 'Categories',       'href' => BASE_URL . '/admin/categories.php'],
        ['icon' => 'chart', 'label' => 'Reports',        'href' => BASE_URL . '/admin/reports.php'],
    ],
    'organizer' => [
        ['icon' => 'grid', 'label' => 'Dashboard',       'href' => BASE_URL . '/organizer/dashboard.php'],
        ['icon' => 'calendar', 'label' => 'My Events',   'href' => BASE_URL . '/organizer/events.php'],
        ['icon' => 'plus', 'label' => 'Create Event',    'href' => BASE_URL . '/organizer/create-event.php'],
        ['icon' => 'users', 'label' => 'Attendees',      'href' => BASE_URL . '/organizer/attendees.php'],
    ],
    'attendee' => [
        ['icon' => 'grid', 'label' => 'Dashboard',       'href' => BASE_URL . '/attendee/dashboard.php'],
        ['icon' => 'search', 'label' => 'Browse Events', 'href' => BASE_URL . '/attendee/browse.php'],
        ['icon' => 'ticket', 'label' => 'My Tickets',    'href' => BASE_URL . '/attendee/my-tickets.php'],
    ],
];

$icons = [
    'grid'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>',
    'users'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
    'calendar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
    'tag'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>',
    'chart'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
    'plus'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4"/>',
    'search'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>',
    'ticket'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>',
    'logout'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>',
];

$currentPath = $_SERVER['PHP_SELF'];
$roleColors = ['admin' => '#f59e0b', 'organizer' => '#6366f1', 'attendee' => '#10b981'];
$roleColor = $roleColors[$role] ?? '#94a3b8';
$avatarInitial = strtoupper(substr($user['name'], 0, 1));
?>
<div class="sidebar fixed top-0 left-0 h-screen w-64 z-40 flex flex-col py-6">
    <!-- Logo -->
    <div class="px-6 mb-8">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <span class="text-white font-bold text-lg tracking-tight font-mono">EventPro</span>
        </div>
    </div>

    <!-- User Card -->
    <div class="px-4 mb-6">
        <div class="glass rounded-xl p-3 flex items-center gap-3">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                 style="background:linear-gradient(135deg,<?= $roleColor ?>,<?= $roleColor ?>88)">
                <?= $avatarInitial ?>
            </div>
            <div class="min-w-0">
                <p class="text-white text-sm font-semibold truncate"><?= e($user['name']) ?></p>
                <span class="text-xs font-medium capitalize" style="color:<?= $roleColor ?>"><?= e($role) ?></span>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 overflow-y-auto scrollbar-hide">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest px-3 mb-3">Menu</p>
        <?php foreach ($navItems[$role] ?? [] as $item): ?>
            <?php
            $isActive = str_contains($currentPath, basename($item['href']));
            $cls = 'nav-item' . ($isActive ? ' active' : '');
            ?>
            <a href="<?= $item['href'] ?>" class="<?= $cls ?>">
                <svg class="w-4.5 h-4.5 flex-shrink-0" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <?= $icons[$item['icon']] ?? '' ?>
                </svg>
                <?= e($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Logout -->
    <div class="px-3 pt-4 border-t border-white/10 mt-auto">
        <a href="<?= BASE_URL ?>/logout.php" class="nav-item text-red-400 hover:text-red-300 hover:bg-red-500/10">
            <svg class="flex-shrink-0" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <?= $icons['logout'] ?>
            </svg>
            Logout
        </a>
    </div>
</div>
