<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('attendee');

$db = getDB();
$user = currentUser();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = (int)($_POST['event_id'] ?? 0);
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    if ($eventId) {
        $eventStmt = $db->prepare("SELECT id, price, capacity FROM events WHERE id = ? AND status = 'published'");
        $eventStmt->execute([$eventId]);
        $event = $eventStmt->fetch();
        if ($event) {
            $soldStmt = $db->prepare("SELECT COALESCE(SUM(quantity),0) FROM tickets WHERE event_id = ? AND status != 'cancelled'");
            $soldStmt->execute([$eventId]);
            $sold = (int)$soldStmt->fetchColumn();
            if ($sold + $qty <= (int)$event['capacity']) {
                $ticketNumber = generateTicketNumber($eventId);
                $unitPrice = (float)$event['price'];
                $total = $unitPrice * $qty;
                $insert = $db->prepare("
                    INSERT INTO tickets (event_id, user_id, ticket_number, quantity, unit_price, total_price, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'confirmed')
                ");
                $insert->execute([$eventId, $user['id'], $ticketNumber, $qty, $unitPrice, $total]);
                $msg = 'Ticket booked successfully.';
            } else {
                $msg = 'Not enough seats available.';
            }
        }
    }
}

$events = $db->query("
    SELECT e.*, c.name AS category, c.color AS cat_color,
           (SELECT COALESCE(SUM(t.quantity),0) FROM tickets t WHERE t.event_id = e.id AND t.status != 'cancelled') AS sold
    FROM events e
    LEFT JOIN categories c ON c.id = e.category_id
    WHERE e.status = 'published' AND e.event_date >= CURDATE()
    ORDER BY e.event_date ASC
")->fetchAll();

$pageTitle = 'Browse Events';
?>
<?php include '../includes/head.php'; ?>
<div class="flex min-h-screen">
<?php include '../includes/sidebar.php'; ?>
<div class="flex-1 ml-64 p-8">
    <h1 class="text-2xl font-bold text-white font-mono mb-2">Browse Events</h1>
    <p class="text-slate-400 text-sm mb-8">Discover and book upcoming events</p>

    <?php if ($msg): ?><div class="flash-success"><?= e($msg) ?></div><?php endif; ?>

    <div class="grid grid-cols-2 gap-5">
        <?php foreach ($events as $event): ?>
        <?php $left = max(0, (int)$event['capacity'] - (int)$event['sold']); ?>
        <div class="glass-card p-5">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h2 class="text-white font-semibold"><?= e($event['title']) ?></h2>
                    <p class="text-slate-500 text-xs mt-1"><?= date('M j, Y', strtotime($event['event_date'])) ?> · <?= e($event['location']) ?></p>
                </div>
                <span class="category-pill" style="background:<?= e($event['cat_color'] ?? '#6366f1') ?>22;color:<?= e($event['cat_color'] ?? '#6366f1') ?>"><?= e($event['category'] ?? 'General') ?></span>
            </div>
            <p class="text-slate-300 text-sm mb-3 line-clamp-2"><?= e(substr((string)$event['description'], 0, 140)) ?>...</p>
            <div class="flex items-center justify-between mb-4">
                <p class="text-white font-mono font-semibold"><?= formatPrice((float)$event['price']) ?></p>
                <p class="text-slate-500 text-xs"><?= $left ?> seats left</p>
            </div>
            <form method="POST" class="flex items-center gap-2">
                <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">
                <input type="number" name="quantity" min="1" max="<?= max(1, $left) ?>" value="1" class="input-field" style="max-width:90px">
                <button class="btn-primary" type="submit" <?= $left <= 0 ? 'disabled' : '' ?>><?= $left <= 0 ? 'Sold Out' : 'Book Now' ?></button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</div>
</body>
</html>
