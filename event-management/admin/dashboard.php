<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('admin');

$db   = getDB();
$user = currentUser();

// Stats
$totalUsers    = $db->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
$totalEvents   = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
$publishedEvts = $db->query("SELECT COUNT(*) FROM events WHERE status = 'published'")->fetchColumn();
$totalTickets  = $db->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$totalRevenue  = $db->query("SELECT COALESCE(SUM(total_price),0) FROM tickets WHERE status != 'cancelled'")->fetchColumn();
$pendingUsers  = $db->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();

// Recent events (5)
$recentEvents = $db->query("
    SELECT e.id, e.title, e.event_date, e.status, e.featured, e.price, e.cover_gradient,
           u.name AS organizer, c.name AS category, c.color AS cat_color,
           (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status != 'cancelled') AS ticket_count
    FROM events e
    JOIN users u ON e.organizer_id = u.id
    LEFT JOIN categories c ON e.category_id = c.id
    ORDER BY e.created_at DESC LIMIT 6
")->fetchAll();

// Recent users (6)
$recentUsers = $db->query("
    SELECT id, name, email, role, status, avatar_color, created_at
    FROM users ORDER BY created_at DESC LIMIT 6
")->fetchAll();

// Category stats
$catStats = $db->query("
    SELECT c.name, c.color, COUNT(e.id) AS event_count
    FROM categories c
    LEFT JOIN events e ON e.category_id = c.id
    GROUP BY c.id ORDER BY event_count DESC
")->fetchAll();

// Monthly revenue (last 6 months)
$monthlyRevenue = $db->query("
    SELECT DATE_FORMAT(booked_at,'%b') AS month, COALESCE(SUM(total_price),0) AS revenue
    FROM tickets
    WHERE booked_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND status != 'cancelled'
    GROUP BY YEAR(booked_at), MONTH(booked_at)
    ORDER BY booked_at ASC
")->fetchAll();

$maxRevenue = max(array_column($monthlyRevenue, 'revenue') ?: [1]);
$pageTitle  = 'Admin Dashboard';
?>
<?php include '../includes/head.php'; ?>

<div class="flex min-h-screen">
<?php include '../includes/sidebar.php'; ?>

<!-- Main -->
<div class="flex-1 ml-64 p-8 overflow-auto">

    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white font-mono">Admin Dashboard</h1>
            <p class="text-slate-400 text-sm mt-1">Welcome back, <?= e($user['name']) ?> · <?= date('l, F j, Y') ?></p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= BASE_URL ?>/admin/events.php" class="btn-ghost">
                <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Events
            </a>
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn-primary">
                <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Manage Users
            </a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-4 gap-5 mb-8">
        <?php $stats = [
            ['Total Users',    $totalUsers,    '$', '#6366f1', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'Users on platform'],
            ['Total Events',   $totalEvents,   '', '#10b981', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', $publishedEvts . ' published'],
            ['Tickets Sold',   $totalTickets,  '', '#f59e0b', 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z', 'Across all events'],
            ['Total Revenue',  '$'.number_format($totalRevenue,2), '', '#ec4899', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'Net earnings'],
        ];
        foreach ($stats as [$label, $val, $prefix, $color, $path, $sub]): ?>
        <div class="stat-card" style="--accent-color:<?=$color?>26">
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:<?=$color?>22">
                    <svg style="width:20px;height:20px;color:<?=$color?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="<?=$path?>"/>
                    </svg>
                </div>
                <?php if($pendingUsers > 0 && $label === 'Total Users'): ?>
                <span class="badge badge-yellow"><?=$pendingUsers?> pending</span>
                <?php endif; ?>
            </div>
            <p class="text-3xl font-bold text-white font-mono"><?= $val ?></p>
            <p class="text-slate-400 text-sm mt-1"><?= $label ?></p>
            <p class="text-xs mt-1" style="color:<?=$color?>99"><?= $sub ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-3 gap-6 mb-8">
        <!-- Revenue Chart -->
        <div class="col-span-2 glass-card p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-white font-semibold text-base">Revenue — Last 6 Months</h2>
                <span class="badge badge-green">↑ Live</span>
            </div>
            <?php if ($monthlyRevenue): ?>
            <div class="flex items-end gap-3 h-44">
                <?php foreach ($monthlyRevenue as $m): ?>
                <?php $pct = $maxRevenue > 0 ? ($m['revenue'] / $maxRevenue) * 100 : 0; ?>
                <div class="flex-1 flex flex-col items-center gap-2">
                    <span class="text-slate-400 text-xs"><?= '$' . number_format($m['revenue'] / 1000, 1) ?>k</span>
                    <div class="w-full rounded-t-lg transition-all" style="height:<?= max(4, $pct * 1.4) ?>px;background:linear-gradient(180deg,#6366f1,#8b5cf6);min-height:4px"></div>
                    <span class="text-slate-500 text-xs"><?= $m['month'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="flex items-center justify-center h-44 text-slate-500 text-sm">No revenue data yet</div>
            <?php endif; ?>
        </div>

        <!-- Category Breakdown -->
        <div class="glass-card p-6">
            <h2 class="text-white font-semibold text-base mb-5">Events by Category</h2>
            <?php $maxCat = max(array_column($catStats, 'event_count') ?: [1]); ?>
            <div class="space-y-4">
                <?php foreach ($catStats as $cs): ?>
                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <span class="text-slate-300 text-xs font-medium"><?= e($cs['name']) ?></span>
                        <span class="text-slate-400 text-xs"><?= $cs['event_count'] ?></span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width:<?= $maxCat > 0 ? ($cs['event_count']/$maxCat*100) : 0 ?>%;background:<?= $cs['color'] ?>"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <!-- Recent Events -->
        <div class="glass-card p-6">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-white font-semibold text-base">Recent Events</h2>
                <a href="<?= BASE_URL ?>/admin/events.php" class="text-indigo-400 text-xs hover:text-indigo-300">View all →</a>
            </div>
            <div class="space-y-3">
                <?php foreach ($recentEvents as $ev): ?>
                <div class="flex items-center gap-3 table-row py-3 px-1">
                    <div class="w-10 h-10 rounded-xl flex-shrink-0 bg-gradient-to-br <?= e($ev['cover_gradient']) ?> flex items-center justify-center">
                        <svg style="width:16px;height:16px" class="text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-white text-sm font-medium truncate"><?= e($ev['title']) ?></p>
                        <p class="text-slate-500 text-xs"><?= date('M j, Y', strtotime($ev['event_date'])) ?> · <?= $ev['ticket_count'] ?> tickets</p>
                    </div>
                    <?php
                    $sBadge = ['published'=>'badge-green','draft'=>'badge-gray','cancelled'=>'badge-red','completed'=>'badge-blue'];
                    ?>
                    <span class="badge <?= $sBadge[$ev['status']] ?? 'badge-gray' ?>"><?= $ev['status'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="glass-card p-6">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-white font-semibold text-base">Recent Users</h2>
                <a href="<?= BASE_URL ?>/admin/users.php" class="text-indigo-400 text-xs hover:text-indigo-300">View all →</a>
            </div>
            <div class="space-y-3">
                <?php foreach ($recentUsers as $ru): ?>
                <div class="flex items-center gap-3 table-row py-2.5 px-1">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                         style="background:<?= e($ru['avatar_color']) ?>33;border:1px solid <?= e($ru['avatar_color']) ?>55;color:<?= e($ru['avatar_color']) ?>">
                        <?= strtoupper(substr($ru['name'],0,1)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-white text-sm font-medium truncate"><?= e($ru['name']) ?></p>
                        <p class="text-slate-500 text-xs truncate"><?= e($ru['email']) ?></p>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <?php $rColors = ['admin'=>'badge-yellow','organizer'=>'badge-purple','attendee'=>'badge-blue']; ?>
                        <span class="badge <?= $rColors[$ru['role']] ?? 'badge-gray' ?>"><?= $ru['role'] ?></span>
                        <?php if($ru['status'] !== 'active'): ?>
                        <span class="badge badge-red"><?= $ru['status'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>
</div>
</body>
</html>
