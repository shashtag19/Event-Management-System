<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('admin');

$db   = getDB();
$user = currentUser();
$msg  = $err = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['uid'] ?? 0);

    if ($action === 'toggle_status' && $uid) {
        $current = $db->prepare("SELECT status FROM users WHERE id = ?");
        $current->execute([$uid]);
        $row = $current->fetch();
        if ($row) {
            $newStatus = $row['status'] === 'active' ? 'inactive' : 'active';
            $db->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $uid]);
            $msg = "User status updated to $newStatus.";
        }
    } elseif ($action === 'change_role' && $uid) {
        $newRole = in_array($_POST['role'] ?? '', ['admin','organizer','attendee']) ? $_POST['role'] : null;
        if ($newRole) {
            $db->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $uid]);
            $msg = "User role updated to $newRole.";
        }
    } elseif ($action === 'delete' && $uid) {
        $db->prepare("DELETE FROM users WHERE id = ? AND id != ?")->execute([$uid, $user['id']]);
        $msg = 'User deleted.';
    }
}

// Filters
$roleFilter   = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$search       = trim($_GET['q'] ?? '');

$where  = [];
$params = [];
if ($roleFilter)   { $where[] = "role = ?";   $params[] = $roleFilter; }
if ($statusFilter) { $where[] = "status = ?"; $params[] = $statusFilter; }
if ($search)       { $where[] = "(name LIKE ? OR email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$sql   = "SELECT * FROM users" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY created_at DESC";
$stmt  = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'User Management';
?>
<?php include '../includes/head.php'; ?>
<div class="flex min-h-screen">
<?php include '../includes/sidebar.php'; ?>

<div class="flex-1 ml-64 p-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white font-mono">User Management</h1>
            <p class="text-slate-400 text-sm mt-1"><?= count($users) ?> users found</p>
        </div>
    </div>

    <?php if ($msg): ?><div class="flash-success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="flash-error"><?= e($err) ?></div><?php endif; ?>

    <!-- Filters -->
    <div class="glass-card p-4 mb-6">
        <form method="GET" class="flex gap-3 items-center flex-wrap">
            <input type="text" name="q" value="<?= e($search) ?>" class="input-field" style="max-width:240px"
                   placeholder="Search name or email…">
            <select name="role" class="input-field" style="max-width:160px" onchange="this.form.submit()">
                <option value="">All Roles</option>
                <?php foreach(['admin','organizer','attendee'] as $r): ?>
                <option value="<?=$r?>" <?= $roleFilter===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="input-field" style="max-width:160px" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach(['active','inactive','pending'] as $s): ?>
                <option value="<?=$s?>" <?= $statusFilter===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary">Search</button>
            <?php if ($search || $roleFilter || $statusFilter): ?>
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn-ghost">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <div class="glass-card overflow-hidden">
        <table class="w-full">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.08)">
                    <?php foreach(['User','Email','Role','Status','Joined','Actions'] as $h): ?>
                    <th class="text-left text-slate-400 text-xs font-semibold uppercase tracking-wider px-5 py-4"><?= $h ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr class="table-row">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                                 style="background:<?=e($u['avatar_color'])?>33;color:<?=e($u['avatar_color'])?>">
                                <?= strtoupper(substr($u['name'],0,1)) ?>
                            </div>
                            <div>
                                <p class="text-white text-sm font-medium"><?= e($u['name']) ?></p>
                                <?php if($u['phone']): ?><p class="text-slate-500 text-xs"><?= e($u['phone']) ?></p><?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-slate-300 text-sm"><?= e($u['email']) ?></td>
                    <td class="px-5 py-4">
                        <form method="POST" class="flex items-center gap-2">
                            <input type="hidden" name="action" value="change_role">
                            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                            <select name="role" onchange="this.form.submit()" class="input-field" style="padding:4px 8px;font-size:0.78rem">
                                <?php foreach(['admin','organizer','attendee'] as $r): ?>
                                <option value="<?=$r?>" <?= $u['role']===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td class="px-5 py-4">
                        <?php $sBadge=['active'=>'badge-green','inactive'=>'badge-red','pending'=>'badge-yellow']; ?>
                        <span class="badge <?= $sBadge[$u['status']] ?? 'badge-gray' ?>"><?= $u['status'] ?></span>
                    </td>
                    <td class="px-5 py-4 text-slate-400 text-sm"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2">
                            <form method="POST">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn-ghost" style="padding:4px 10px;font-size:0.78rem">
                                    <?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                            <?php if ($u['id'] !== (int)$user['id']): ?>
                            <form method="POST" onsubmit="return confirm('Delete this user and all their data?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn-danger">Delete</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$users): ?>
                <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">No users found matching your filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</body>
</html>
