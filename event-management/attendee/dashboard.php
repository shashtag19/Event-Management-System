<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('attendee');

$db = getDB();
$user = currentUser();

$ticketCount = $db->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status != 'cancelled'");
$ticketCount->execute([$user['id']]);
$ticketCount = $ticketCount->fetchColumn();

$spent = $db->prepare("SELECT COALESCE(SUM(total_price),0) FROM tickets WHERE user_id = ? AND status != 'cancelled'");
$spent->execute([$user['id']]);
$spent = $spent->fetchColumn();

$upcoming = $db->prepare("
    SELECT e.*, c.name AS category, c.color AS cat_color
    FROM events e
    LEFT JOIN categories c ON c.id = e.category_id
    WHERE e.status = 'published' AND e.event_date >= CURDATE()
    ORDER BY e.event_date ASC
    LIMIT 6
");
$upcoming->execute();
$upcoming = $upcoming->fetchAll();

$myTickets = $db->prepare("
    SELECT t.*, e.title, e.event_date, e.location
    FROM tickets t
    JOIN events e ON e.id = t.event_id
    WHERE t.user_id = ?
    ORDER BY t.booked_at DESC
    LIMIT 6
");
$myTickets->execute([$user['id']]);
$myTickets = $myTickets->fetchAll();

$pageTitle = 'Attendee Dashboard';
?>
<?php include '../includes/head.php'; ?>
<div class="flex min-h-screen">
<?php include '../includes/sidebar.php'; ?>
<div class="flex-1 ml-64 p-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-white font-mono">Welcome, <?= e($user['name']) ?></h1>
        <p class="text-slate-400 text-sm mt-1">Track bookings and discover upcoming events</p>
    </div>

    <div class="grid grid-cols-2 gap-5 mb-8">
        <div class="stat-card"><p class="text-slate-400 text-sm">Tickets</p><p class="text-3xl text-white font-mono font-bold mt-1"><?= (int)$ticketCount ?></p></div>
        <div class="stat-card"><p class="text-slate-400 text-sm">Total Spent</p><p class="text-3xl text-white font-mono font-bold mt-1">$<?= number_format((float)$spent, 2) ?></p></div>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <div class="glass-card p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-white font-semibold">Upcoming Events</h2>
                <a href="<?= BASE_URL ?>/attendee/browse.php" class="text-indigo-400 text-xs">Browse all →</a>
            </div>
            <div class="space-y-3">
                <?php foreach ($upcoming as $event): ?>
                <div class="table-row py-3 px-1">
                    <p class="text-white text-sm font-medium"><?= e($event['title']) ?></p>
                    <p class="text-slate-500 text-xs"><?= date('M j, Y', strtotime($event['event_date'])) ?> · <?= e($event['location']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="glass-card p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-white font-semibold">My Recent Tickets</h2>
                <a href="<?= BASE_URL ?>/attendee/my-tickets.php" class="text-indigo-400 text-xs">View all →</a>
            </div>
            <div class="space-y-3">
                <?php foreach ($myTickets as $ticket): ?>
                <div class="table-row py-3 px-1">
                    <p class="text-white text-sm font-medium"><?= e($ticket['title']) ?></p>
                    <p class="text-slate-500 text-xs"><?= e($ticket['ticket_number']) ?> · $<?= number_format((float)$ticket['total_price'], 2) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>
