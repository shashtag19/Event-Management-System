<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('organizer');

$db = getDB();
$user = currentUser();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $eventId = (int)($_POST['event_id'] ?? 0);
    if ($eventId) {
        if ($action === 'delete') {
            $db->prepare("DELETE FROM events WHERE id = ? AND organizer_id = ?")->execute([$eventId, $user['id']]);
            $msg = 'Event deleted.';
        } elseif ($action === 'status') {
            $status = $_POST['status'] ?? 'draft';
            if (in_array($status, ['draft', 'published', 'cancelled', 'completed'], true)) {
                $db->prepare("UPDATE events SET status = ? WHERE id = ? AND organizer_id = ?")->execute([$status, $eventId, $user['id']]);
                $msg = 'Event status updated.';
            }
        }
    }
}

$events = $db->prepare("
    SELECT e.*, c.name AS category,
           (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status != 'cancelled') AS sold
    FROM events e
    LEFT JOIN categories c ON c.id = e.category_id
    WHERE e.organizer_id = ?
    ORDER BY e.created_at DESC
");
$events->execute([$user['id']]);
$events = $events->fetchAll();

$pageTitle = 'My Events';
?>
<?php include '../includes/head.php'; ?>
<div class="flex min-h-screen">
<?php include '../includes/sidebar.php'; ?>
<div class="flex-1 ml-64 p-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white font-mono">My Events</h1>
            <p class="text-slate-400 text-sm mt-1">Manage your event lifecycle</p>
        </div>
        <a href="<?= BASE_URL ?>/organizer/create-event.php" class="btn-primary">Create Event</a>
    </div>

    <?php if ($msg): ?><div class="flash-success"><?= e($msg) ?></div><?php endif; ?>

    <div class="glass-card overflow-hidden">
        <table class="w-full">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.08)">
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Event</th>
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Date</th>
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Capacity</th>
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Price</th>
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Status</th>
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                <tr class="table-row">
                    <td class="px-5 py-4">
                        <p class="text-white text-sm font-medium"><?= e($event['title']) ?></p>
                        <p class="text-slate-500 text-xs"><?= e($event['category'] ?? 'Uncategorized') ?></p>
                    </td>
                    <td class="px-5 py-4 text-slate-300 text-sm"><?= date('M j, Y', strtotime($event['event_date'])) ?></td>
                    <td class="px-5 py-4 text-slate-300 text-sm"><?= (int)$event['sold'] ?> / <?= (int)$event['capacity'] ?></td>
                    <td class="px-5 py-4 text-slate-300 text-sm"><?= formatPrice((float)$event['price']) ?></td>
                    <td class="px-5 py-4">
                        <form method="POST">
                            <input type="hidden" name="action" value="status">
                            <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">
                            <select name="status" class="input-field" style="padding:4px 8px;font-size:0.75rem" onchange="this.form.submit()">
                                <?php foreach (['draft','published','cancelled','completed'] as $status): ?>
                                <option value="<?= $status ?>" <?= $event['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td class="px-5 py-4">
                        <form method="POST" onsubmit="return confirm('Delete this event?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">
                            <button class="btn-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$events): ?>
                <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">No events created yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</body>
</html>
