<?php
require_once 'config/database.php';
require_once 'config/auth.php';

if (isLoggedIn()) {
    $role = currentUser()['role'];
    header('Location: ' . BASE_URL . "/$role/dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'inactive') {
                $error = 'Your account has been deactivated. Contact support.';
            } elseif ($user['status'] === 'pending') {
                $error = 'Your account is pending approval.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['role']    = $user['role'];
                header('Location: ' . BASE_URL . '/' . $user['role'] . '/dashboard.php');
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<?php $pageTitle = 'Login'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — EventPro</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Fira+Code:wght@500;700&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; }
body { background: linear-gradient(135deg, #020617 0%, #0f0a2a 40%, #0c0c1d 70%, #020617 100%); min-height: 100vh; font-family: 'Inter', sans-serif; }
.glass { background: rgba(255,255,255,0.05); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border: 1px solid rgba(255,255,255,0.1); }
.input-field { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 10px; color: white; padding: 12px 16px; font-size: 0.9rem; width: 100%; outline: none; transition: all 0.15s ease; }
.input-field::placeholder { color: rgba(148,163,184,0.6); }
.input-field:focus { border-color: rgba(99,102,241,0.5); background: rgba(255,255,255,0.08); box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }
.orb { position: fixed; border-radius: 50%; filter: blur(80px); pointer-events: none; opacity: 0.15; }
</style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

<!-- Ambient orbs -->
<div class="orb w-96 h-96 bg-indigo-600 top-[-10%] left-[-10%]"></div>
<div class="orb w-80 h-80 bg-violet-600 bottom-[-5%] right-[-5%]"></div>
<div class="orb w-64 h-64 bg-purple-500 top-[40%] right-[20%]"></div>

<div class="w-full max-w-md relative z-10">
    <!-- Logo -->
    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);box-shadow:0 8px 32px rgba(99,102,241,0.35)">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-white font-mono tracking-tight">EventPro</h1>
        <p class="text-slate-400 text-sm mt-1">Sign in to your account</p>
    </div>

    <!-- Card -->
    <div class="glass rounded-2xl p-8">
        <?php if ($error): ?>
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;padding:12px 16px;border-radius:10px;font-size:0.875rem;margin-bottom:20px">
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-5">
                <label class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                <input type="email" name="email" class="input-field" placeholder="you@example.com"
                       value="<?= e($_POST['email'] ?? '') ?>" required autocomplete="email">
            </div>
            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm font-medium text-slate-300">Password</label>
                </div>
                <input type="password" name="password" class="input-field" placeholder="••••••••" required autocomplete="current-password">
            </div>
            <button type="submit" class="w-full py-3 rounded-xl font-semibold text-white text-sm transition-all duration-200"
                    style="background:linear-gradient(135deg,#4f46e5,#7c3aed);box-shadow:0 4px 15px rgba(99,102,241,0.3)"
                    onmouseover="this.style.boxShadow='0 8px 25px rgba(99,102,241,0.45)'"
                    onmouseout="this.style.boxShadow='0 4px 15px rgba(99,102,241,0.3)'">
                Sign In
            </button>
        </form>

        <p class="text-center text-slate-500 text-sm mt-6">
            Don't have an account?
            <a href="<?= BASE_URL ?>/register.php" class="text-indigo-400 hover:text-indigo-300 font-medium">Register</a>
        </p>
    </div>

    <!-- Demo credentials -->
    <div class="mt-5 glass rounded-xl p-4">
        <p class="text-slate-400 text-xs font-medium mb-3 text-center uppercase tracking-wider">Demo Credentials</p>
        <div class="grid grid-cols-3 gap-2 text-center">
            <?php foreach([
                ['Admin',     '#f59e0b', 'admin@eventpro.com'],
                ['Organizer', '#6366f1', 'sarah@eventpro.com'],
                ['Attendee',  '#10b981', 'alex@mail.com'],
            ] as [$role, $color, $email]): ?>
            <button onclick="document.querySelector('[name=email]').value='<?=$email?>';document.querySelector('[name=password]').value='password123'"
                    class="rounded-lg p-2 transition-all hover:opacity-80"
                    style="background:<?=$color?>18;border:1px solid <?=$color?>33;cursor:pointer">
                <p style="color:<?=$color?>" class="text-xs font-bold"><?=$role?></p>
                <p class="text-slate-500 text-xs mt-0.5 truncate"><?=explode('@',$email)[0]?>@…</p>
            </button>
            <?php endforeach; ?>
        </div>
        <p class="text-center text-slate-600 text-xs mt-2">Password: <span class="text-slate-400">password123</span></p>
    </div>
</div>
</body>
</html>
