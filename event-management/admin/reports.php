<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('admin');

$db = getDB();

$overview = $db->query("
    SELECT
        (SELECT COUNT(*) FROM users) AS users_count,
        (SELECT COUNT(*) FROM events) AS events_count,
        (SELECT COUNT(*) FROM tickets WHERE status != 'cancelled') AS tickets_count,
        (SELECT COALESCE(SUM(total_price),0) FROM tickets WHERE status != 'cancelled') AS revenue
")->fetch();

$topEvents = $db->query("
    SELECT e.title, u.name AS organizer, COUNT(t.id) AS sales, COALESCE(SUM(t.total_price),0) AS revenue
    FROM events e
    LEFT JOIN users u ON e.organizer_id = u.id
    LEFT JOIN tickets t ON t.event_id = e.id AND t.status != 'cancelled'
    GROUP BY e.id
    ORDER BY sales DESC, revenue DESC
    LIMIT 10
")->fetchAll();

$organizerPerformance = $db->query("
    SELECT u.name, COUNT(DISTINCT e.id) AS event_count, COUNT(t.id) AS sold_tickets, COALESCE(SUM(t.total_price),0) AS revenue
    FROM users u
    LEFT JOIN events e ON e.organizer_id = u.id
    LEFT JOIN tickets t ON t.event_id = e.id AND t.status != 'cancelled'
    WHERE u.role = 'organizer'
    GROUP BY u.id
    ORDER BY revenue DESC
")->fetchAll();

$pageTitle = 'Reports';
?>
<?php include '../includes/head.php'; ?>
<div class="flex min-h-screen">
<?php include '../includes/sidebar.php'; ?>

<div class="flex-1 ml-64 p-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-white font-mono">Reports</h1>
        <p class="text-slate-400 text-sm mt-1">Platform performance and revenue snapshots</p>
    </div>

    <div class="grid grid-cols-4 gap-5 mb-8">
        <div class="stat-card"><p class="text-slate-400 text-sm">Users</p><p class="text-3xl text-white font-mono font-bold mt-1"><?= (int)$overview['users_count'] ?></p></div>
        <div class="stat-card"><p class="text-slate-400 text-sm">Events</p><p class="text-3xl text-white font-mono font-bold mt-1"><?= (int)$overview['events_count'] ?></p></div>
        <div class="stat-card"><p class="text-slate-400 text-sm">Tickets</p><p class="text-3xl text-white font-mono font-bold mt-1"><?= (int)$overview['tickets_count'] ?></p></div>
        <div class="stat-card"><p class="text-slate-400 text-sm">Revenue</p><p class="text-3xl text-white font-mono font-bold mt-1">$<?= number_format((float)$overview['revenue'], 2) ?></p></div>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <div class="glass-card overflow-hidden">
            <div class="px-5 py-4 border-b border-white/10">
                <h2 class="text-white font-semibold">Top Events</h2>
            </div>
            <table class="w-full">
                <thead>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.08)">
                        <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-3">Event</th>
                        <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-3">Sales</th>
                        <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-3">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topEvents as $event): ?>
                    <tr class="table-row">
                        <td class="px-5 py-3">
                            <p class="text-white text-sm"><?= e($event['title']) ?></p>
                            <p class="text-slate-500 text-xs"><?= e($event['organizer'] ?? 'N/A') ?></p>
                        </td>
                        <td class="px-5 py-3 text-slate-300 text-sm"><?= (int)$event['sales'] ?></td>
                        <td class="px-5 py-3 text-slate-300 text-sm">$<?= number_format((float)$event['revenue'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="glass-card overflow-hidden">
            <div class="px-5 py-4 border-b border-white/10">
                <h2 class="text-white font-semibold">Organizer Performance</h2>
            </div>
            <table class="w-full">
                <thead>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.08)">
                        <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-3">Organizer</th>
                        <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-3">Events</th>
                        <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-3">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($organizerPerformance as $org): ?>
                    <tr class="table-row">
                        <td class="px-5 py-3 text-white text-sm"><?= e($org['name']) ?></td>
                        <td class="px-5 py-3 text-slate-300 text-sm"><?= (int)$org['event_count'] ?></td>
                        <td class="px-5 py-3 text-slate-300 text-sm">$<?= number_format((float)$org['revenue'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
</body>
</html>
