<?php
require_once '../config/database.php';

// Connect without database to create it
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$schema = "
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','organizer','attendee') DEFAULT 'attendee',
    status ENUM('active','inactive','pending') DEFAULT 'active',
    phone VARCHAR(20),
    bio TEXT,
    avatar_color VARCHAR(7) DEFAULT '#6366F1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    slug VARCHAR(60) UNIQUE NOT NULL,
    color VARCHAR(7) NOT NULL,
    icon VARCHAR(50) NOT NULL
);

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    organizer_id INT NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    end_date DATE,
    end_time TIME,
    location VARCHAR(200),
    venue VARCHAR(200),
    capacity INT DEFAULT 100,
    price DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('draft','published','cancelled','completed') DEFAULT 'published',
    featured TINYINT(1) DEFAULT 0,
    cover_gradient VARCHAR(100) DEFAULT 'from-indigo-500 to-purple-600',
    tags VARCHAR(300),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    ticket_number VARCHAR(25) UNIQUE NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('confirmed','cancelled','attended','pending') DEFAULT 'confirmed',
    booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
";

foreach (explode(';', $schema) as $stmt) {
    $stmt = trim($stmt);
    if ($stmt) $pdo->exec($stmt);
}

$pass = password_hash('password123', PASSWORD_BCRYPT);

// ── Users ──────────────────────────────────────────────────────────────
$users = [
    // Admin
    ['Admin Master', 'admin@eventpro.com', $pass, 'admin', 'active', '+1-555-0100', 'Platform administrator with full system access.', '#f59e0b'],
    // Organizers
    ['Sarah Chen', 'sarah@eventpro.com', $pass, 'organizer', 'active', '+1-555-0201', 'Tech conference organizer specializing in AI/ML events.', '#6366f1'],
    ['Marcus Rivera', 'marcus@eventpro.com', $pass, 'organizer', 'active', '+1-555-0202', 'Music festival producer and live entertainment specialist.', '#8b5cf6'],
    ['Priya Sharma', 'priya@eventpro.com', $pass, 'organizer', 'active', '+1-555-0203', 'Business summit organizer with 10+ years experience.', '#ec4899'],
    ['Jake Thompson', 'jake@eventpro.com', $pass, 'organizer', 'inactive', '+1-555-0204', 'Sports and fitness event coordinator.', '#10b981'],
    // Attendees
    ['Alex Johnson',   'alex@mail.com',   $pass, 'attendee', 'active', '+1-555-0301', null, '#6366f1'],
    ['Emma Williams',  'emma@mail.com',   $pass, 'attendee', 'active', '+1-555-0302', null, '#ec4899'],
    ['Liam Brown',     'liam@mail.com',   $pass, 'attendee', 'active', '+1-555-0303', null, '#10b981'],
    ['Olivia Davis',   'olivia@mail.com', $pass, 'attendee', 'active', '+1-555-0304', null, '#f59e0b'],
    ['Noah Martinez',  'noah@mail.com',   $pass, 'attendee', 'active', '+1-555-0305', null, '#3b82f6'],
    ['Ava Anderson',   'ava@mail.com',    $pass, 'attendee', 'active', '+1-555-0306', null, '#8b5cf6'],
    ['Ethan Wilson',   'ethan@mail.com',  $pass, 'attendee', 'active', '+1-555-0307', null, '#ef4444'],
    ['Sophia Taylor',  'sophia@mail.com', $pass, 'attendee', 'active', '+1-555-0308', null, '#14b8a6'],
    ['Mason Thomas',   'mason@mail.com',  $pass, 'attendee', 'active', '+1-555-0309', null, '#f97316'],
    ['Isabella Garcia','bella@mail.com',  $pass, 'attendee', 'pending','+1-555-0310', null, '#a855f7'],
];

$userStmt = $pdo->prepare("INSERT INTO users (name,email,password,role,status,phone,bio,avatar_color) VALUES(?,?,?,?,?,?,?,?)");
foreach ($users as $u) {
    $userStmt->execute($u);
}

// ── Categories ─────────────────────────────────────────────────────────
$categories = [
    ['Technology',         'technology',    '#6366f1', 'cpu'],
    ['Music & Arts',       'music-arts',    '#ec4899', 'music'],
    ['Sports & Fitness',   'sports',        '#10b981', 'trophy'],
    ['Business & Finance', 'business',      '#f59e0b', 'briefcase'],
    ['Food & Drink',       'food',          '#f97316', 'utensils'],
    ['Arts & Culture',     'arts-culture',  '#8b5cf6', 'palette'],
];

$catStmt = $pdo->prepare("INSERT INTO categories (name,slug,color,icon) VALUES(?,?,?,?)");
foreach ($categories as $c) {
    $catStmt->execute($c);
}

// ── Events ─────────────────────────────────────────────────────────────
$gradients = [
    'from-indigo-500 to-purple-600',
    'from-pink-500 to-rose-600',
    'from-emerald-500 to-teal-600',
    'from-amber-500 to-orange-600',
    'from-blue-500 to-cyan-600',
    'from-violet-500 to-purple-700',
    'from-rose-500 to-pink-700',
    'from-cyan-500 to-blue-600',
];

$events = [
    // Tech events (organizer=2, cat=1)
    ['AI & Machine Learning Summit 2025', 'Join 2,000+ industry leaders exploring the future of artificial intelligence. Featuring keynotes from Google, OpenAI, and Anthropic researchers. Hands-on workshops, panel discussions, and networking sessions included.', 1, 2, '2026-05-15', '09:00:00', '2026-05-17', '18:00:00', 'San Francisco, CA', 'Moscone Center', 800, 299.00, 'published', 1, 'from-indigo-500 to-purple-600', 'AI,ML,Tech,Keynote'],
    ['Web Dev Conf 2025', 'The premier conference for web developers covering React, Vue, TypeScript, and modern CSS. 60+ talks across 4 tracks, workshops, and a hackathon.', 1, 2, '2026-06-20', '08:00:00', '2026-06-22', '17:00:00', 'Austin, TX', 'Austin Convention Center', 500, 199.00, 'published', 0, 'from-blue-500 to-cyan-600', 'Web,React,TypeScript'],
    ['Startup Pitch Night', 'An evening with 20 startups pitching to top VCs. Great networking opportunity for founders, investors, and tech enthusiasts.', 1, 2, '2026-04-28', '18:30:00', '2026-04-28', '22:00:00', 'New York, NY', 'WeWork Hudson Yards', 150, 0.00, 'published', 0, 'from-violet-500 to-purple-700', 'Startup,VC,Networking'],
    ['Cybersecurity Workshop', 'Hands-on ethical hacking and cybersecurity fundamentals. Learn penetration testing, threat analysis, and security best practices.', 1, 2, '2025-12-10', '10:00:00', '2025-12-10', '17:00:00', 'Seattle, WA', 'Tech Hub Seattle', 60, 149.00, 'completed', 0, 'from-cyan-500 to-blue-600', 'Security,Hacking,Workshop'],
    // Music events (organizer=3, cat=2)
    ['Neon Dreams Music Festival', 'A 3-day outdoor music festival featuring 40+ artists across 5 stages. Electronic, indie, and hip-hop genres. Camping available.', 2, 3, '2026-07-04', '12:00:00', '2026-07-06', '23:59:00', 'Las Vegas, NV', 'Festival Grounds, NV', 5000, 189.00, 'published', 1, 'from-pink-500 to-rose-600', 'Music,Festival,EDM,Live'],
    ['Jazz Under the Stars', 'An intimate evening of jazz performances by world-class musicians in a beautiful open-air amphitheater. Wine and artisan food vendors on-site.', 2, 3, '2026-05-30', '19:00:00', '2026-05-30', '23:00:00', 'New Orleans, LA', 'City Park Amphitheater', 400, 75.00, 'published', 0, 'from-amber-500 to-orange-600', 'Jazz,Live Music,Outdoor'],
    ['Electronic Night Vol.3', 'Deep house and techno showcase with international DJs. State-of-the-art sound system, immersive light show.', 2, 3, '2026-05-10', '21:00:00', '2026-05-11', '04:00:00', 'Miami, FL', 'Club Space Miami', 1200, 45.00, 'published', 0, 'from-rose-500 to-pink-700', 'EDM,Electronic,DJ'],
    ['Indie Rock Showcase', 'Featuring 8 emerging indie rock bands competing for a record deal. Judges from major labels will be in attendance.', 2, 3, '2025-11-22', '18:00:00', '2025-11-22', '23:00:00', 'Nashville, TN', 'The Ryman Auditorium', 2400, 35.00, 'completed', 0, 'from-violet-500 to-purple-700', 'Rock,Indie,Live'],
    // Business events (organizer=4, cat=4)
    ['Global Entrepreneur Summit', 'Connect with 500+ entrepreneurs, investors, and business leaders. Featuring workshops on fundraising, scaling, and global expansion.', 4, 4, '2026-09-18', '08:00:00', '2026-09-20', '17:00:00', 'Chicago, IL', 'Marriott Marquis Chicago', 500, 599.00, 'published', 1, 'from-amber-500 to-orange-600', 'Business,Entrepreneur,Summit'],
    ['Investment & FinTech Forum', 'Explore the intersection of finance and technology. Keynotes from Wall Street leaders, crypto pioneers, and regulatory experts.', 4, 4, '2026-06-15', '09:00:00', '2026-06-16', '18:00:00', 'New York, NY', 'NYSE Conference Center', 300, 450.00, 'published', 0, 'from-emerald-500 to-teal-600', 'Finance,FinTech,Crypto'],
    ['Women in Leadership Conference', 'Empowering women in business with keynotes, mentorship sessions, and networking. Featuring Fortune 500 CEOs and executives.', 4, 4, '2026-03-08', '09:00:00', '2026-03-08', '18:00:00', 'Boston, MA', 'Boston Convention Center', 600, 120.00, 'published', 0, 'from-pink-500 to-rose-600', 'Leadership,Women,Business'],
    ['Marketing Mastery Workshop', 'Intensive 1-day workshop on digital marketing, SEO, content strategy, and paid advertising. Practical exercises included.', 4, 4, '2025-10-15', '09:00:00', '2025-10-15', '18:00:00', 'Atlanta, GA', 'Atlanta Tech Village', 80, 250.00, 'completed', 0, 'from-blue-500 to-cyan-600', 'Marketing,Digital,Workshop'],
    // Sports events (organizer=5, cat=3)
    ['Urban Marathon Challenge', '10K and 5K categories through the city\'s most scenic routes. All fitness levels welcome. Medal and finisher t-shirt included.', 3, 5, '2026-04-05', '07:00:00', '2026-04-05', '13:00:00', 'Portland, OR', 'Tom McCall Waterfront Park', 2000, 55.00, 'published', 0, 'from-emerald-500 to-teal-600', 'Marathon,Running,Sports'],
    ['CrossFit Open Qualifier', 'Regional CrossFit competition. 3 divisions: Scaled, Rx, and Elite. Top 10 qualify for nationals.', 3, 5, '2026-05-25', '08:00:00', '2026-05-25', '17:00:00', 'Denver, CO', 'Mile High CrossFit Arena', 250, 80.00, 'published', 0, 'from-amber-500 to-orange-600', 'CrossFit,Fitness,Competition'],
    ['Yoga & Wellness Retreat', '3-day immersive wellness retreat with yoga, meditation, nutrition workshops, and spa treatments. Accommodation included.', 3, 5, '2026-08-14', '15:00:00', '2026-08-17', '12:00:00', 'Sedona, AZ', 'Mii Amo Spa Resort', 40, 890.00, 'published', 1, 'from-cyan-500 to-blue-600', 'Yoga,Wellness,Retreat'],
    // Food events (organizer=4, cat=5)
    ['International Food Festival', 'Celebrate world cuisines with 80+ vendors, cooking demos by Michelin-star chefs, and wine/spirits tastings.', 5, 4, '2026-06-28', '11:00:00', '2026-06-29', '21:00:00', 'Los Angeles, CA', 'Grand Park LA', 3000, 25.00, 'published', 0, 'from-orange-500 to-red-600', 'Food,Festival,Culture'],
    ['Craft Beer & BBQ Fest', 'The ultimate outdoor BBQ competition featuring 30 pitmasters and 50+ local craft breweries. Family-friendly with live music.', 5, 3, '2026-07-19', '10:00:00', '2026-07-20', '20:00:00', 'Kansas City, MO', 'KC Convention Center Grounds', 4000, 40.00, 'published', 0, 'from-amber-500 to-yellow-600', 'BBQ,Beer,Festival'],
    ['Masterclass: Italian Cuisine', 'Cook alongside a Michelin-starred Italian chef. Learn pasta making, risotto, and classic sauces. Small group of 12 maximum.', 5, 4, '2026-05-08', '14:00:00', '2026-05-08', '19:00:00', 'San Francisco, CA', 'Culinary Institute SF', 12, 180.00, 'published', 0, 'from-red-500 to-rose-600', 'Cooking,Italian,Workshop'],
];

$evStmt = $pdo->prepare("INSERT INTO events (title,description,category_id,organizer_id,event_date,event_time,end_date,end_time,location,venue,capacity,price,status,featured,cover_gradient,tags) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
foreach ($events as $e) {
    $evStmt->execute($e);
}

// ── Tickets ────────────────────────────────────────────────────────────
$tickets = [
    // event_id, user_id, qty, unit_price, status
    [1, 6,  1, 299.00, 'confirmed'],
    [1, 7,  2, 299.00, 'confirmed'],
    [1, 8,  1, 299.00, 'confirmed'],
    [1, 9,  1, 299.00, 'confirmed'],
    [1, 10, 1, 299.00, 'confirmed'],
    [2, 6,  1, 199.00, 'confirmed'],
    [2, 11, 1, 199.00, 'confirmed'],
    [2, 12, 2, 199.00, 'confirmed'],
    [3, 7,  1, 0.00,   'confirmed'],
    [3, 8,  1, 0.00,   'confirmed'],
    [3, 13, 1, 0.00,   'confirmed'],
    [4, 6,  1, 149.00, 'attended'],
    [4, 9,  1, 149.00, 'attended'],
    [5, 7,  2, 189.00, 'confirmed'],
    [5, 10, 1, 189.00, 'confirmed'],
    [5, 14, 3, 189.00, 'confirmed'],
    [6, 8,  1, 75.00,  'confirmed'],
    [6, 11, 2, 75.00,  'confirmed'],
    [7, 6,  1, 45.00,  'confirmed'],
    [7, 12, 1, 45.00,  'confirmed'],
    [8, 9,  2, 35.00,  'attended'],
    [8, 13, 1, 35.00,  'attended'],
    [9, 6,  1, 599.00, 'confirmed'],
    [9, 7,  1, 599.00, 'confirmed'],
    [10,8,  1, 450.00, 'confirmed'],
    [10,14, 1, 450.00, 'confirmed'],
    [11,9,  1, 120.00, 'confirmed'],
    [11,10, 1, 120.00, 'confirmed'],
    [11,11, 2, 120.00, 'confirmed'],
    [12,6,  1, 250.00, 'attended'],
    [13,7,  1, 55.00,  'confirmed'],
    [13,12, 1, 55.00,  'confirmed'],
    [14,8,  1, 80.00,  'confirmed'],
    [15,9,  1, 890.00, 'confirmed'],
    [16,10, 2, 25.00,  'confirmed'],
    [16,11, 1, 25.00,  'confirmed'],
    [17,13, 2, 40.00,  'confirmed'],
    [17,14, 1, 40.00,  'confirmed'],
    [18,6,  1, 180.00, 'confirmed'],
    [18,7,  1, 180.00, 'confirmed'],
];

$tkStmt = $pdo->prepare("INSERT INTO tickets (event_id,user_id,ticket_number,quantity,unit_price,total_price,status) VALUES(?,?,?,?,?,?,?)");
foreach ($tickets as $idx => $t) {
    [$eid, $uid, $qty, $price, $status] = $t;
    $tkNum = 'TKT-' . strtoupper(substr(md5($eid . $uid . $idx), 0, 8));
    $tkStmt->execute([$eid, $uid, $tkNum, $qty, $price, $qty * $price, $status]);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>EventPro — Installation</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>body{background:linear-gradient(135deg,#020617,#0f0a2a,#020617);min-height:100vh;font-family:Inter,sans-serif}</style>
</head>
<body class="flex items-center justify-center min-h-screen p-6">
<div class="max-w-2xl w-full">
    <div class="text-center mb-8">
        <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <h1 class="text-3xl font-bold text-white font-mono">EventPro Installed!</h1>
        <p class="text-slate-400 mt-2">Database seeded successfully. Start using the system below.</p>
    </div>

    <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:16px;padding:32px" class="mb-6">
        <h2 class="text-white font-semibold text-lg mb-4">🔑 Login Credentials</h2>
        <div class="space-y-4">
            <?php foreach([
                ['Admin',     '#f59e0b', 'admin@eventpro.com'],
                ['Organizer', '#6366f1', 'sarah@eventpro.com'],
                ['Attendee',  '#10b981', 'alex@mail.com'],
            ] as [$role, $color, $email]): ?>
            <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:14px">
                <div class="flex items-center gap-3 mb-2">
                    <span style="background:<?=$color?>22;color:<?=$color?>;border:1px solid <?=$color?>44;padding:2px 10px;border-radius:9999px;font-size:0.72rem;font-weight:700;text-transform:uppercase"><?=$role?></span>
                </div>
                <p class="text-slate-300 text-sm"><span class="text-slate-500">Email:</span> <code class="text-indigo-300"><?=$email?></code></p>
                <p class="text-slate-300 text-sm mt-1"><span class="text-slate-500">Password:</span> <code class="text-indigo-300">password123</code></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.25);border-radius:12px;padding:16px" class="mb-6">
        <p class="text-emerald-300 text-sm font-medium">✓ <?= count($users) ?> users created &nbsp;|&nbsp; <?= count($categories) ?> categories &nbsp;|&nbsp; <?= count($events) ?> events &nbsp;|&nbsp; <?= count($tickets) ?> tickets seeded</p>
    </div>

    <a href="<?= BASE_URL ?>/login.php" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:white;padding:14px 32px;border-radius:12px;font-weight:600;font-size:1rem;display:block;text-align:center;text-decoration:none;transition:all 0.2s">
        → Go to Login
    </a>

    <p class="text-slate-600 text-xs text-center mt-4">Delete or restrict access to this file after setup.</p>
</div>
</body>
</html>
