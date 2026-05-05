<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — EventPro' : 'EventPro' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                        mono: ['Fira Code', 'ui-monospace'],
                    },
                    colors: {
                        glass: 'rgba(255,255,255,0.05)',
                    },
                    backgroundImage: {
                        'app': 'linear-gradient(135deg, #020617 0%, #0f0a2a 40%, #0c0c1d 70%, #020617 100%)',
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Fira+Code:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { background: linear-gradient(135deg, #020617 0%, #0f0a2a 40%, #0c0c1d 70%, #020617 100%); min-height: 100vh; font-family: 'Inter', sans-serif; }
        .glass { background: rgba(255,255,255,0.05); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); }
        .glass-dark { background: rgba(0,0,0,0.25); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.08); }
        .glass-card { background: rgba(255,255,255,0.04); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border: 1px solid rgba(255,255,255,0.09); border-radius: 16px; transition: all 0.2s ease; }
        .glass-card:hover { background: rgba(255,255,255,0.07); border-color: rgba(255,255,255,0.15); transform: translateY(-1px); }
        .sidebar { background: rgba(15,10,42,0.6); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border-right: 1px solid rgba(255,255,255,0.08); }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 16px; border-radius: 10px; color: rgba(148,163,184,1); font-size: 0.875rem; font-weight: 500; transition: all 0.15s ease; text-decoration: none; margin: 2px 0; }
        .nav-item:hover { background: rgba(255,255,255,0.07); color: white; }
        .nav-item.active { background: rgba(99,102,241,0.2); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.3); }
        .btn-primary { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 0.875rem; border: none; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary:hover { background: linear-gradient(135deg, #4338ca, #6d28d9); transform: translateY(-1px); box-shadow: 0 8px 25px rgba(99,102,241,0.3); }
        .btn-danger { background: rgba(239,68,68,0.15); color: #fca5a5; padding: 6px 14px; border-radius: 8px; font-size: 0.8rem; font-weight: 500; border: 1px solid rgba(239,68,68,0.3); cursor: pointer; transition: all 0.15s ease; }
        .btn-danger:hover { background: rgba(239,68,68,0.25); }
        .btn-ghost { background: rgba(255,255,255,0.06); color: #94a3b8; padding: 8px 16px; border-radius: 10px; font-size: 0.875rem; font-weight: 500; border: 1px solid rgba(255,255,255,0.1); cursor: pointer; transition: all 0.15s ease; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-ghost:hover { background: rgba(255,255,255,0.1); color: white; }
        .badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 9999px; font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        .badge-green { background: rgba(16,185,129,0.15); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.25); }
        .badge-red { background: rgba(239,68,68,0.15); color: #fca5a5; border: 1px solid rgba(239,68,68,0.25); }
        .badge-yellow { background: rgba(245,158,11,0.15); color: #fde68a; border: 1px solid rgba(245,158,11,0.25); }
        .badge-blue { background: rgba(59,130,246,0.15); color: #93c5fd; border: 1px solid rgba(59,130,246,0.25); }
        .badge-purple { background: rgba(139,92,246,0.15); color: #c4b5fd; border: 1px solid rgba(139,92,246,0.25); }
        .badge-gray { background: rgba(100,116,139,0.15); color: #94a3b8; border: 1px solid rgba(100,116,139,0.25); }
        .input-field { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 10px; color: white; padding: 10px 14px; font-size: 0.875rem; width: 100%; outline: none; transition: all 0.15s ease; }
        .input-field::placeholder { color: rgba(148,163,184,0.6); }
        .input-field:focus { border-color: rgba(99,102,241,0.5); background: rgba(255,255,255,0.07); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .input-field option { background: #1e1b4b; color: white; }
        .stat-card { background: rgba(255,255,255,0.04); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.09); border-radius: 16px; padding: 24px; position: relative; overflow: hidden; }
        .stat-card::before { content:''; position:absolute; inset:0; background: radial-gradient(circle at top right, var(--accent-color, rgba(99,102,241,0.15)), transparent 60%); pointer-events: none; }
        .table-row { border-bottom: 1px solid rgba(255,255,255,0.06); transition: background 0.1s ease; }
        .table-row:hover { background: rgba(255,255,255,0.03); }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .progress-bar { background: rgba(255,255,255,0.08); border-radius: 9999px; height: 6px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 9999px; background: linear-gradient(90deg, #6366f1, #8b5cf6); transition: width 0.4s ease; }
        .flash-success { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); color: #6ee7b7; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.875rem; }
        .flash-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.875rem; }
        .category-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
    </style>
</head>
<body>
