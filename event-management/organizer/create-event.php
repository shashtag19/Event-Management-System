<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('organizer');

$db = getDB();
$user = currentUser();
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $eventDate = $_POST['event_date'] ?? '';
    $eventTime = $_POST['event_time'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $capacity = max(1, (int)($_POST['capacity'] ?? 100));
    $price = max(0, (float)($_POST['price'] ?? 0));
    $status = in_array($_POST['status'] ?? '', ['draft', 'published'], true) ? $_POST['status'] : 'draft';

    if (!$title || !$eventDate || !$eventTime) {
        $err = 'Title, date, and time are required.';
    } else {
        $stmt = $db->prepare("
            INSERT INTO events (title, description, category_id, organizer_id, event_date, event_time, location, venue, capacity, price, status, cover_gradient)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'from-indigo-500 to-purple-600')
        ");
        $stmt->execute([$title, $description, $categoryId ?: null, $user['id'], $eventDate, $eventTime, $location, $venue, $capacity, $price, $status]);
        $msg = 'Event created successfully.';
    }
}

$categories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$pageTitle = 'Create Event';
?>
<?php include '../includes/head.php'; ?>
<div class="flex min-h-screen">
<?php include '../includes/sidebar.php'; ?>
<div class="flex-1 ml-64 p-8">
    <h1 class="text-2xl font-bold text-white font-mono mb-2">Create Event</h1>
    <p class="text-slate-400 text-sm mb-8">Publish a new event for attendees</p>

    <?php if ($msg): ?><div class="flash-success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="flash-error"><?= e($err) ?></div><?php endif; ?>

    <div class="glass-card p-6 max-w-3xl">
        <form method="POST" class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="text-slate-300 text-xs mb-1 block">Title</label>
                <input class="input-field" name="title" required>
            </div>
            <div class="col-span-2">
                <label class="text-slate-300 text-xs mb-1 block">Description</label>
                <textarea class="input-field" name="description" rows="4"></textarea>
            </div>
            <div>
                <label class="text-slate-300 text-xs mb-1 block">Category</label>
                <select class="input-field" name="category_id">
                    <option value="">Select category</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?= (int)$category['id'] ?>"><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-slate-300 text-xs mb-1 block">Status</label>
                <select class="input-field" name="status">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>
            <div>
                <label class="text-slate-300 text-xs mb-1 block">Date</label>
                <input class="input-field" type="date" name="event_date" required>
            </div>
            <div>
                <label class="text-slate-300 text-xs mb-1 block">Time</label>
                <input class="input-field" type="time" name="event_time" required>
            </div>
            <div>
                <label class="text-slate-300 text-xs mb-1 block">Location</label>
                <input class="input-field" name="location">
            </div>
            <div>
                <label class="text-slate-300 text-xs mb-1 block">Venue</label>
                <input class="input-field" name="venue">
            </div>
            <div>
                <label class="text-slate-300 text-xs mb-1 block">Capacity</label>
                <input class="input-field" type="number" name="capacity" value="100" min="1">
            </div>
            <div>
                <label class="text-slate-300 text-xs mb-1 block">Price (USD)</label>
                <input class="input-field" type="number" name="price" step="0.01" min="0" value="0">
            </div>
            <div class="col-span-2">
                <button type="submit" class="btn-primary">Create Event</button>
            </div>
        </form>
    </div>
</div>
</div>
</body>
</html>
