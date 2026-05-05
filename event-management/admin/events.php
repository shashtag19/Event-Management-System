<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('admin');

$db  = getDB();
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $eventId = (int)($_POST['event_id'] ?? 0);

    if ($action === 'toggle_status' && $eventId) {
        $newStatus = $_POST['new_status'] ?? 'published';
        if (in_array($newStatus, ['published','draft','cancelled','completed'])) {
            $db->prepare("UPDATE events SET status = ? WHERE id = ?")->execute([$newStatus, $eventId]);
            $msg = "Event status updated to $newStatus.";
        }
    } elseif ($action === 'toggle_featured' && $eventId) {
        $db->prepare("UPDATE events SET featured = NOT featured WHERE id = ?")->execute([$eventId]);
        $msg = 'Featured status toggled.';
    } elseif ($action === 'delete' && $eventId) {
        $db->prepare("DELETE FROM events WHERE id = ?")->execute([$eventId]);
        $msg = 'Event deleted.';
    }
}

$statusFilter   = $_GET['status'] ?? '';
$categoryFilter = (int)($_GET['cat'] ?? 0);
$search         = trim($_GET['q'] ?? '');

$where  = [];
$params = [];
if ($statusFilter)   { $where[] = "e.status = ?";       $params[] = $statusFilter; }
if ($categoryFilter) { $where[] = "e.category_id = ?";  $params[] = $categoryFilter; }
if ($search)         { $where[] = "e.title LIKE ?";      $params[] = "%$search%"; }

$sql = "SELECT e.*, u.name AS organizer, c.name AS category, c.color AS cat_color,
               (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status != 'cancelled') AS sold
        FROM events e
        JOIN users u ON e.organizer_id = u.id
        LEFT JOIN categories c ON e.category_id = c.id"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . " ORDER BY e.created_at DESC";

$stmt   = $db->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$pageTitle  = 'All Events';
?>
<?php include '../includes/head.php'; ?>
<div class="flex min-h-screen">
<?php include '../includes/sidebar.php'; ?>

<div class="flex-1 ml-64 p-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white font-mono">All Events</h1>
            <p class="text-slate-400 text-sm mt-1"><?= count($events) ?> events</p>
        </div>
    </div>

    <?php if ($msg): ?><div class="flash-success"><?= e($msg) ?></div><?php endif; ?>

    <!-- Filters -->
    <div class="glass-card p-4 mb-6">
        <form method="GET" class="flex gap-3 flex-wrap items-center">
            <input type="text" name="q" value="<?= e($search) ?>" class="input-field" style="max-width:240px" placeholder="Search events…">
            <select name="status" class="input-field" style="max-width:160px" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach(['published','draft','cancelled','completed'] as $s): ?>
                <option value="<?=$s?>" <?= $statusFilter===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="cat" class="input-field" style="max-width:180px" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php foreach($categories as $cat): ?>
                <option value="<?=$cat['id']?>" <?= $categoryFilter===$cat['id']?'selected':'' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary">Filter</button>
            <?php if ($search || $statusFilter || $categoryFilter): ?>
            <a href="<?= BASE_URL ?>/admin/events.php" class="btn-ghost">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Events Table -->
    <div class="glass-card overflow-hidden">
        <table class="w-full">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.08)">
                    <?php foreach(['Event','Organizer','Category','Date','Tickets','Price','Status','Actions'] as $h): ?>
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-4 py-4"><?=$h?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $ev): ?>
                <tr class="table-row">
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex-shrink-0 bg-gradient-to-br <?= e($ev['cover_gradient']) ?> flex items-center justify-center">
                                <svg style="width:14px;height:14px" class="text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-white text-sm font-medium truncate max-w-[180px]"><?= e($ev['title']) ?></p>
                                <?php if($ev['featured']): ?><span class="badge badge-yellow" style="font-size:0.65rem">⭐ Featured</span><?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-slate-300 text-sm"><?= e($ev['organizer']) ?></td>
                    <td class="px-4 py-4">
                        <?php if($ev['category']): ?>
                        <span class="category-pill text-xs font-semibold" style="background:<?=$ev['cat_color']?>22;color:<?=$ev['cat_color']?>">
                            <?= e($ev['category']) ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-4 text-slate-300 text-sm whitespace-nowrap"><?= date('M j, Y', strtotime($ev['event_date'])) ?></td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-2">
                            <span class="text-white text-sm font-mono"><?= $ev['sold'] ?></span>
                            <span class="text-slate-500 text-xs">/ <?= $ev['capacity'] ?></span>
                        </div>
                        <div class="progress-bar mt-1.5" style="width:80px">
                            <div class="progress-fill" style="width:<?= min(100, ($ev['sold']/$ev['capacity'])*100) ?>%"></div>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-slate-300 text-sm font-mono"><?= formatPrice($ev['price']) ?></td>
                    <td class="px-4 py-4">
                        <form method="POST" class="flex items-center gap-1">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                            <select name="new_status" onchange="this.form.submit()" class="input-field" style="padding:4px 8px;font-size:0.75rem">
                                <?php foreach(['published','draft','cancelled','completed'] as $s): ?>
                                <option value="<?=$s?>" <?= $ev['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-2">
                            <form method="POST">
                                <input type="hidden" name="action" value="toggle_featured">
                                <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                <button type="submit" class="btn-ghost" style="padding:4px 10px;font-size:0.75rem"
                                        title="<?= $ev['featured'] ? 'Unfeature' : 'Feature' ?>">
                                    <?= $ev['featured'] ? '★' : '☆' ?>
                                </button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Delete this event and all its tickets?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                <button type="submit" class="btn-danger">Del</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$events): ?>
                <tr><td colspan="8" class="px-5 py-12 text-center text-slate-500">No events found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</body>
</html>
