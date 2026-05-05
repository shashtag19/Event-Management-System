<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('attendee');

$db = getDB();
$user = currentUser();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    if ($ticketId) {
        $db->prepare("UPDATE tickets SET status = 'cancelled' WHERE id = ? AND user_id = ?")->execute([$ticketId, $user['id']]);
        $msg = 'Ticket cancelled.';
    }
}

$tickets = $db->prepare("
    SELECT t.*, e.title, e.event_date, e.event_time, e.location, e.venue
    FROM tickets t
    JOIN events e ON e.id = t.event_id
    WHERE t.user_id = ?
    ORDER BY t.booked_at DESC
");
$tickets->execute([$user['id']]);
$tickets = $tickets->fetchAll();

$pageTitle = 'My Tickets';
?>
<?php include '../includes/head.php'; ?>
<div class="flex min-h-screen">
<?php include '../includes/sidebar.php'; ?>
<div class="flex-1 ml-64 p-8">
    <h1 class="text-2xl font-bold text-white font-mono mb-2">My Tickets</h1>
    <p class="text-slate-400 text-sm mb-8">All your event bookings in one place</p>

    <?php if ($msg): ?><div class="flash-success"><?= e($msg) ?></div><?php endif; ?>

    <div class="glass-card overflow-hidden">
        <table class="w-full">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.08)">
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Event</th>
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Ticket #</th>
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Qty</th>
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Amount</th>
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Status</th>
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                <tr class="table-row">
                    <td class="px-5 py-4">
                        <p class="text-white text-sm font-medium"><?= e($ticket['title']) ?></p>
                        <p class="text-slate-500 text-xs"><?= date('M j, Y', strtotime($ticket['event_date'])) ?> · <?= e($ticket['location']) ?></p>
                    </td>
                    <td class="px-5 py-4 text-slate-300 text-sm font-mono"><?= e($ticket['ticket_number']) ?></td>
                    <td class="px-5 py-4 text-slate-300 text-sm"><?= (int)$ticket['quantity'] ?></td>
                    <td class="px-5 py-4 text-slate-300 text-sm">$<?= number_format((float)$ticket['total_price'], 2) ?></td>
                    <td class="px-5 py-4">
                        <?php $badges = ['confirmed'=>'badge-green','pending'=>'badge-yellow','cancelled'=>'badge-red','attended'=>'badge-blue']; ?>
                        <span class="badge <?= $badges[$ticket['status']] ?? 'badge-gray' ?>"><?= e($ticket['status']) ?></span>
                    </td>
                    <td class="px-5 py-4">
                        <?php if ($ticket['status'] !== 'cancelled'): ?>
                        <form method="POST" onsubmit="return confirm('Cancel this ticket?');">
                            <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
                            <button class="btn-danger" type="submit">Cancel</button>
                        </form>
                        <?php else: ?>
                        <span class="text-slate-500 text-xs">Cancelled</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$tickets): ?>
                <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">No tickets booked yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</body>
</html>
