<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('organizer');

$db = getDB();
$user = currentUser();

$attendees = $db->prepare("
    SELECT t.ticket_number, t.quantity, t.total_price, t.status, t.booked_at,
           u.name AS attendee_name, u.email AS attendee_email,
           e.title AS event_title, e.event_date
    FROM tickets t
    JOIN users u ON u.id = t.user_id
    JOIN events e ON e.id = t.event_id
    WHERE e.organizer_id = ?
    ORDER BY t.booked_at DESC
");
$attendees->execute([$user['id']]);
$attendees = $attendees->fetchAll();

$pageTitle = 'Attendees';
?>
<?php include '../includes/head.php'; ?>
<div class="flex min-h-screen">
<?php include '../includes/sidebar.php'; ?>
<div class="flex-1 ml-64 p-8">
    <h1 class="text-2xl font-bold text-white font-mono mb-2">Attendees</h1>
    <p class="text-slate-400 text-sm mb-8">All bookings for your events</p>

    <div class="glass-card overflow-hidden">
        <table class="w-full">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.08)">
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Attendee</th>
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Event</th>
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Ticket</th>
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Qty</th>
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Amount</th>
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendees as $item): ?>
                <tr class="table-row">
                    <td class="px-5 py-4">
                        <p class="text-white text-sm"><?= e($item['attendee_name']) ?></p>
                        <p class="text-slate-500 text-xs"><?= e($item['attendee_email']) ?></p>
                    </td>
                    <td class="px-5 py-4 text-slate-300 text-sm">
                        <?= e($item['event_title']) ?>
                        <p class="text-slate-500 text-xs"><?= date('M j, Y', strtotime($item['event_date'])) ?></p>
                    </td>
                    <td class="px-5 py-4 text-slate-300 text-sm font-mono"><?= e($item['ticket_number']) ?></td>
                    <td class="px-5 py-4 text-slate-300 text-sm"><?= (int)$item['quantity'] ?></td>
                    <td class="px-5 py-4 text-slate-300 text-sm">$<?= number_format((float)$item['total_price'], 2) ?></td>
                    <td class="px-5 py-4">
                        <?php $badges = ['confirmed'=>'badge-green','pending'=>'badge-yellow','cancelled'=>'badge-red','attended'=>'badge-blue']; ?>
                        <span class="badge <?= $badges[$item['status']] ?? 'badge-gray' ?>"><?= e($item['status']) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$attendees): ?>
                <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">No attendees found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</body>
</html>
