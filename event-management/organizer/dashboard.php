<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('organizer');

$db     = getDB();
$user   = currentUser();
$userId = $user['id'];

// Stats
$myEvents     = $db->prepare("SELECT COUNT(*) FROM events WHERE organizer_id = ?");
$myEvents->execute([$userId]);
$myEvents = $myEvents->fetchColumn();

$publishedEvts = $db->prepare("SELECT COUNT(*) FROM events WHERE organizer_id = ? AND status = 'published'");
$publishedEvts->execute([$userId]);
$publishedEvts = $publishedEvts->fetchColumn();

$totalAttendees = $db->prepare("
    SELECT COALESCE(SUM(t.quantity),0) FROM tickets t
    JOIN events e ON t.event_id = e.id
    WHERE e.organizer_id = ? AND t.status != 'cancelled'
");
$totalAttendees->execute([$userId]);
$totalAttendees = $totalAttendees->fetchColumn();

$totalRevenue = $db->prepare("
    SELECT COALESCE(SUM(t.total_price),0) FROM tickets t
    JOIN events e ON t.event_id = e.id
    WHERE e.organizer_id = ? AND t.status != 'cancelled'
");
$totalRevenue->execute([$userId]);
$totalRevenue = $totalRevenue->fetchColumn();

// My events (upcoming + current)
$myEventsList = $db->prepare("
    SELECT e.*, c.name AS category, c.color AS cat_color,
           (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status != 'cancelled') AS sold
    FROM events e
    LEFT JOIN categories c ON e.category_id = c.id
    WHERE e.organizer_id = ?
    ORDER BY e.event_date DESC LIMIT 6
");
$myEventsList->execute([$userId]);
$myEventsList = $myEventsList->fetchAll();

// Recent bookings for my events
$recentBookings = $db->prepare("
    SELECT t.*, u.name AS attendee_name, u.email AS attendee_email, u.avatar_color,
           e.title AS event_title, e.event_date
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    JOIN events e ON t.event_id = e.id
    WHERE e.organizer_id = ?
    ORDER BY t.booked_at DESC LIMIT 8
");
$recentBookings->execute([$userId]);
$recentBookings = $recentBookings->fetchAll();

// Monthly revenue chart data
$revenueData = $db->prepare("
    SELECT DATE_FORMAT(t.booked_at,'%b') AS month, COALESCE(SUM(t.total_price),0) AS revenue
    FROM tickets t JOIN events e ON t.event_id = e.id
    WHERE e.organizer_id = ? AND t.booked_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND t.status != 'cancelled'
    GROUP BY YEAR(t.booked_at), MONTH(t.booked_at)
    ORDER BY t.booked_at ASC
");
$revenueData->execute([$userId]);
$revenueData = $revenueData->fetchAll();
$maxRev = max(array_column($revenueData, 'revenue') ?: [1]);

$pageTitle = 'Organizer Dashboard';
?>
<?php include '../includes/head.php'; ?>
<div class="flex min-h-screen">
<?php include '../includes/sidebar.php'; ?>

<div class="flex-1 ml-64 p-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white font-mono">Organizer Hub</h1>
            <p class="text-slate-400 text-sm mt-1">Welcome, <?= e($user['name']) ?> · <?= date('l, F j, Y') ?></p>
        </div>
        <a href="<?= BASE_URL ?>/organizer/create-event.php" class="btn-primary">
            <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Event
        </a>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-4 gap-5 mb-8">
        <?php $stats = [
            ['My Events',       $myEvents,       '#6366f1', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', "$publishedEvts published"],
            ['Total Attendees', $totalAttendees, '#10b981', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'Across all events'],
            ['Revenue',         '$'.number_format($totalRevenue,2), '#f59e0b', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'Total earnings'],
            ['Avg Fill Rate',   ($myEvents > 0 ? round(($totalAttendees / max(1, $myEvents * 100)) * 100) : 0) . '%', '#ec4899', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'Average capacity used'],
        ];
        foreach ($stats as [$label, $val, $color, $path, $sub]): ?>
        <div class="stat-card" style="--accent-color:<?=$color?>26">
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:<?=$color?>22">
                    <svg style="width:20px;height:20px;color:<?=$color?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="<?=$path?>"/>
                    </svg>
                </div>
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
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-white font-semibold text-base">My Revenue</h2>
                <span class="text-slate-400 text-xs">Last 6 months</span>
            </div>
            <?php if ($revenueData): ?>
            <div class="flex items-end gap-3 h-40">
                <?php foreach ($revenueData as $m): ?>
                <?php $pct = $maxRev > 0 ? ($m['revenue']/$maxRev)*100 : 0; ?>
                <div class="flex-1 flex flex-col items-center gap-2">
                    <span class="text-slate-400 text-xs">$<?= number_format($m['revenue']/1000,1) ?>k</span>
                    <div class="w-full rounded-t-lg" style="height:<?= max(4, $pct*1.4) ?>px;background:linear-gradient(180deg,#10b981,#059669);min-height:4px"></div>
                    <span class="text-slate-500 text-xs"><?= $m['month'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="flex items-center justify-center h-40 text-slate-500 text-sm">No revenue data yet. Create and publish events!</div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="glass-card p-6">
            <h2 class="text-white font-semibold text-base mb-5">Quick Actions</h2>
            <div class="space-y-3">
                <a href="<?= BASE_URL ?>/organizer/create-event.php" class="flex items-center gap-3 p-3 rounded-xl transition-all hover:bg-white/5 border border-white/5 hover:border-indigo-500/30 group">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:rgba(99,102,241,0.2)">
                        <svg style="width:16px;height:16px;color:#a5b4fc" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div>
                        <p class="text-white text-sm font-medium">Create New Event</p>
                        <p class="text-slate-500 text-xs">Add a new event</p>
                    </div>
                </a>
                <a href="<?= BASE_URL ?>/organizer/events.php" class="flex items-center gap-3 p-3 rounded-xl transition-all hover:bg-white/5 border border-white/5">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:rgba(16,185,129,0.2)">
                        <svg style="width:16px;height:16px;color:#6ee7b7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <p class="text-white text-sm font-medium">Manage Events</p>
                        <p class="text-slate-500 text-xs">Edit, publish events</p>
                    </div>
                </a>
                <a href="<?= BASE_URL ?>/organizer/attendees.php" class="flex items-center gap-3 p-3 rounded-xl transition-all hover:bg-white/5 border border-white/5">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:rgba(245,158,11,0.2)">
                        <svg style="width:16px;height:16px;color:#fde68a" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-white text-sm font-medium">View Attendees</p>
                        <p class="text-slate-500 text-xs">See who's attending</p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <!-- My Events -->
        <div class="glass-card p-6">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-white font-semibold text-base">My Events</h2>
                <a href="<?= BASE_URL ?>/organizer/events.php" class="text-indigo-400 text-xs hover:text-indigo-300">View all →</a>
            </div>
            <?php if ($myEventsList): ?>
            <div class="space-y-3">
                <?php foreach ($myEventsList as $ev): ?>
                <div class="flex items-center gap-3 table-row py-3 px-1">
                    <div class="w-10 h-10 rounded-xl flex-shrink-0 bg-gradient-to-br <?= e($ev['cover_gradient']) ?> flex items-center justify-center">
                        <svg style="width:14px;height:14px" class="text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-white text-sm font-medium truncate"><?= e($ev['title']) ?></p>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-slate-500 text-xs"><?= date('M j', strtotime($ev['event_date'])) ?></span>
                            <div class="progress-bar flex-1" style="max-width:80px">
                                <div class="progress-fill" style="width:<?= min(100, ($ev['sold']/$ev['capacity'])*100) ?>%"></div>
                            </div>
                            <span class="text-slate-400 text-xs"><?= $ev['sold'] ?>/<?= $ev['capacity'] ?></span>
                        </div>
                    </div>
                    <?php $sBadge=['published'=>'badge-green','draft'=>'badge-gray','cancelled'=>'badge-red','completed'=>'badge-blue']; ?>
                    <span class="badge <?= $sBadge[$ev['status']] ?? 'badge-gray' ?>"><?= $ev['status'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-12">
                <p class="text-slate-400 text-sm">No events yet.</p>
                <a href="<?= BASE_URL ?>/organizer/create-event.php" class="btn-primary mt-4 inline-flex">Create your first event</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Bookings -->
        <div class="glass-card p-6">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-white font-semibold text-base">Recent Bookings</h2>
            </div>
            <?php if ($recentBookings): ?>
            <div class="space-y-3">
                <?php foreach ($recentBookings as $bk): ?>
                <div class="flex items-center gap-3 table-row py-2.5 px-1">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                         style="background:<?=e($bk['avatar_color'])?>33;color:<?=e($bk['avatar_color'])?>">
                        <?= strtoupper(substr($bk['attendee_name'],0,1)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-white text-sm font-medium truncate"><?= e($bk['attendee_name']) ?></p>
                        <p class="text-slate-500 text-xs truncate"><?= e($bk['event_title']) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-white text-sm font-mono font-semibold">$<?= number_format($bk['total_price'],0) ?></p>
                        <p class="text-slate-500 text-xs"><?= timeAgo($bk['booked_at']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="flex items-center justify-center h-32 text-slate-500 text-sm">No bookings yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
</body>
</html>
