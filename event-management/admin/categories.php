<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('admin');

$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name  = trim($_POST['name'] ?? '');
        $slug  = trim($_POST['slug'] ?? '');
        $color = trim($_POST['color'] ?? '#6366f1');
        $icon  = trim($_POST['icon'] ?? 'tag');
        if ($name && $slug) {
            $stmt = $db->prepare("INSERT INTO categories (name, slug, color, icon) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $color, $icon]);
            $msg = 'Category created successfully.';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['category_id'] ?? 0);
        if ($id) {
            $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
            $msg = 'Category deleted.';
        }
    }
}

$categories = $db->query("
    SELECT c.*, COUNT(e.id) AS events_count
    FROM categories c
    LEFT JOIN events e ON e.category_id = c.id
    GROUP BY c.id
    ORDER BY c.name
")->fetchAll();

$pageTitle = 'Categories';
?>
<?php include '../includes/head.php'; ?>
<div class="flex min-h-screen">
<?php include '../includes/sidebar.php'; ?>

<div class="flex-1 ml-64 p-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white font-mono">Categories</h1>
            <p class="text-slate-400 text-sm mt-1">Manage event categories</p>
        </div>
    </div>

    <?php if ($msg): ?><div class="flash-success"><?= e($msg) ?></div><?php endif; ?>

    <div class="grid grid-cols-3 gap-6">
        <div class="col-span-1 glass-card p-6">
            <h2 class="text-white font-semibold mb-4">Create Category</h2>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="action" value="create">
                <input class="input-field" name="name" placeholder="Name (e.g. AI & Tech)" required>
                <input class="input-field" name="slug" placeholder="Slug (e.g. ai-tech)" required>
                <input class="input-field" name="color" type="color" value="#6366f1">
                <input class="input-field" name="icon" placeholder="Icon name (tag, cpu, etc)" value="tag">
                <button class="btn-primary w-full justify-center" type="submit">Add Category</button>
            </form>
        </div>

        <div class="col-span-2 glass-card overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.08)">
                        <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Category</th>
                        <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Slug</th>
                        <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Events</th>
                        <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr class="table-row">
                        <td class="px-5 py-4">
                            <span class="category-pill" style="background:<?= e($cat['color']) ?>22;color:<?= e($cat['color']) ?>">
                                <?= e($cat['name']) ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-slate-300 text-sm"><?= e($cat['slug']) ?></td>
                        <td class="px-5 py-4 text-slate-300 text-sm"><?= (int)$cat['events_count'] ?></td>
                        <td class="px-5 py-4">
                            <form method="POST" onsubmit="return confirm('Delete this category?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="category_id" value="<?= (int)$cat['id'] ?>">
                                <button class="btn-danger" type="submit">Delete</button>
                            </form>
                        </td>
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
