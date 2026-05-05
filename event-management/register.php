<?php
require_once 'config/database.php';
require_once 'config/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/' . currentUser()['role'] . '/dashboard.php');
    exit;
}

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $role     = in_array($_POST['role'] ?? '', ['organizer', 'attendee']) ? $_POST['role'] : 'attendee';

    if (!$name || !$email || !$password || !$confirm) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $colors = ['#6366f1','#8b5cf6','#ec4899','#10b981','#f59e0b','#3b82f6','#f97316'];
            $color  = $colors[array_rand($colors)];
            $stmt   = $db->prepare("INSERT INTO users (name, email, password, role, avatar_color) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT), $role, $color]);
            $success = 'Account created! You can now sign in.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — EventPro</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Fira+Code:wght@500;700&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; }
body { background: linear-gradient(135deg, #020617 0%, #0f0a2a 40%, #0c0c1d 70%, #020617 100%); min-height: 100vh; font-family: 'Inter', sans-serif; }
.glass { background: rgba(255,255,255,0.05); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border: 1px solid rgba(255,255,255,0.1); }
.input-field { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 10px; color: white; padding: 12px 16px; font-size: 0.9rem; width: 100%; outline: none; transition: all 0.15s ease; }
.input-field::placeholder { color: rgba(148,163,184,0.6); }
.input-field:focus { border-color: rgba(99,102,241,0.5); background: rgba(255,255,255,0.08); box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }
.role-card { border: 2px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 16px; cursor: pointer; transition: all 0.2s ease; text-align: center; }
.role-card:hover { border-color: rgba(99,102,241,0.4); background: rgba(99,102,241,0.08); }
.orb { position: fixed; border-radius: 50%; filter: blur(80px); pointer-events: none; opacity: 0.12; }
</style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
<div class="orb w-96 h-96 bg-violet-600 top-[-10%] right-[-10%]"></div>
<div class="orb w-80 h-80 bg-indigo-600 bottom-[5%] left-[-5%]"></div>

<div class="w-full max-w-md relative z-10">
    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);box-shadow:0 8px 32px rgba(99,102,241,0.35)">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-white font-mono tracking-tight">Join EventPro</h1>
        <p class="text-slate-400 text-sm mt-1">Create your account</p>
    </div>

    <div class="glass rounded-2xl p-8">
        <?php if ($error): ?>
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;padding:12px 16px;border-radius:10px;font-size:0.875rem;margin-bottom:20px"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);color:#6ee7b7;padding:12px 16px;border-radius:10px;font-size:0.875rem;margin-bottom:20px">
            <?= e($success) ?> <a href="<?= BASE_URL ?>/login.php" class="underline font-medium">Sign in now →</a>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-5">
                <label class="block text-sm font-medium text-slate-300 mb-2">Full Name</label>
                <input type="text" name="name" class="input-field" placeholder="Jane Smith"
                       value="<?= e($_POST['name'] ?? '') ?>" required>
            </div>
            <div class="mb-5">
                <label class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                <input type="email" name="email" class="input-field" placeholder="you@example.com"
                       value="<?= e($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="mb-5">
                <label class="block text-sm font-medium text-slate-300 mb-2">Account Type</label>
                <div class="grid grid-cols-2 gap-3">
                    <?php foreach ([
                        ['attendee',  'Attendee',  '#10b981', 'Browse & book events'],
                        ['organizer', 'Organizer', '#6366f1', 'Create & manage events'],
                    ] as [$val, $label, $color, $desc]): ?>
                    <label class="role-card" id="card-<?=$val?>" style="<?= ($_POST['role'] ?? 'attendee') === $val ? "border-color:$color;background:{$color}18" : '' ?>">
                        <input type="radio" name="role" value="<?=$val?>" class="sr-only"
                               <?= ($_POST['role'] ?? 'attendee') === $val ? 'checked' : '' ?>
                               onchange="selectRole('<?=$val?>','<?=$color?>')">
                        <p style="color:<?=$color?>" class="font-semibold text-sm"><?=$label?></p>
                        <p class="text-slate-500 text-xs mt-1"><?=$desc?></p>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="mb-5">
                <label class="block text-sm font-medium text-slate-300 mb-2">Password</label>
                <input type="password" name="password" class="input-field" placeholder="Min. 8 characters" required>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-300 mb-2">Confirm Password</label>
                <input type="password" name="confirm" class="input-field" placeholder="Repeat password" required>
            </div>
            <button type="submit" class="w-full py-3 rounded-xl font-semibold text-white text-sm transition-all"
                    style="background:linear-gradient(135deg,#4f46e5,#7c3aed);box-shadow:0 4px 15px rgba(99,102,241,0.3)">
                Create Account
            </button>
        </form>

        <p class="text-center text-slate-500 text-sm mt-6">
            Already have an account?
            <a href="<?= BASE_URL ?>/login.php" class="text-indigo-400 hover:text-indigo-300 font-medium">Sign in</a>
        </p>
    </div>
</div>

<script>
function selectRole(val, color) {
    document.querySelectorAll('.role-card').forEach(c => {
        c.style.borderColor = 'rgba(255,255,255,0.1)';
        c.style.background = 'transparent';
    });
    const card = document.getElementById('card-' + val);
    if (card) { card.style.borderColor = color; card.style.background = color + '18'; }
}
</script>
</body>
</html>
