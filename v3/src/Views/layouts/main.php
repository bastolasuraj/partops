<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <title><?= htmlspecialchars($pageTitle ?? 'PartOps') ?> - PartOps</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        :root {
            --bg: #131013;
            --panel: #231f20;
            --card: #1d191a;
            --accent: rgb(188, 59, 40);
            --accent-2: rgb(255, 222, 63);
            --text: #f3f0f0;
            --muted: #c3bfbf;
            --border: rgba(255, 255, 255, 0.14);
            --shadow: 0 12px 36px rgba(0, 0, 0, 0.4);
            --success: rgb(76, 175, 80);
            --warning: rgb(255, 193, 7);
            --danger: rgb(244, 67, 54);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: radial-gradient(circle at 18% 20%, rgba(255, 222, 63, 0.07), transparent 32%), 
                        radial-gradient(circle at 82% 15%, rgba(188, 59, 40, 0.14), transparent 32%), 
                        linear-gradient(135deg, #161215 0%, #1f1a1c 55%, #171316 100%);
            color: var(--text);
            font-family: "Inter", "Segoe UI", system-ui, sans-serif;
            min-height: 100vh;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: var(--panel);
            border-bottom: 1px solid var(--border);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .brand { font-weight: 700; font-size: 1.25rem; letter-spacing: 0.5px; color: var(--accent-2); }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .user-badge { 
            padding: 0.35rem 0.8rem; 
            border-radius: 999px; 
            font-size: 0.85rem;
            font-weight: 600;
            background: rgba(188, 59, 40, 0.2);
            border: 1px solid var(--border);
        }
        .user-badge.admin { background: rgba(255, 222, 63, 0.2); }
        .layout {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 1rem;
            padding: 1rem;
            max-width: 1400px;
            margin: 0 auto;
            min-height: calc(100vh - 70px);
        }
        .sidebar {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1rem;
            box-shadow: var(--shadow);
            height: fit-content;
            position: sticky;
            top: 90px;
        }
        .sidebar h2 {
            font-size: 0.85rem;
            color: var(--muted);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 0.75rem;
        }
        .sidebar nav a {
            display: block;
            padding: 0.6rem 0.75rem;
            margin-bottom: 0.25rem;
            color: var(--text);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 140ms ease;
            border: 1px solid transparent;
        }
        .sidebar nav a:hover {
            background: rgba(255, 222, 63, 0.14);
            transform: translateX(4px);
        }
        .sidebar nav a.active {
            background: rgba(188, 59, 40, 0.18);
            border-color: rgba(188, 59, 40, 0.4);
            color: var(--accent-2);
        }
        .sidebar .divider {
            height: 1px;
            background: var(--border);
            margin: 0.75rem 0;
        }
        .content {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .page-header h1 { font-size: 1.75rem; color: #fff; }
        .page-header .eyebrow {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            margin-bottom: 0.25rem;
        }
        .page-header .lede { color: var(--muted); margin-top: 0.25rem; }
        .actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s;
        }
        .btn-primary { background: var(--accent); color: #fff; border-color: rgba(255, 222, 63, 0.4); }
        .btn-primary:hover { background: rgb(200, 70, 50); }
        .btn-ghost { background: transparent; color: var(--text); border-color: var(--border); }
        .btn-ghost:hover { background: rgba(255, 255, 255, 0.05); }
        .btn-success { background: var(--success); color: #fff; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-sm { padding: 0.4rem 0.75rem; font-size: 0.85rem; }
        .flash {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .flash-success { background: rgba(76, 175, 80, 0.2); border: 1px solid rgba(76, 175, 80, 0.4); color: var(--success); }
        .flash-error { background: rgba(244, 67, 54, 0.2); border: 1px solid rgba(244, 67, 54, 0.4); color: var(--danger); }
        .flash-info { background: rgba(33, 150, 243, 0.2); border: 1px solid rgba(33, 150, 243, 0.4); color: #2196f3; }
        table.grid {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            margin: 1rem 0;
        }
        table.grid th, table.grid td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        table.grid th {
            background: rgba(188, 59, 40, 0.18);
            font-weight: 700;
            font-size: 0.9rem;
        }
        table.grid tbody tr:hover { background: rgba(255, 255, 255, 0.02); }
        table.grid tbody tr:nth-child(even) td { background: rgba(255, 255, 255, 0.01); }
        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block;
            margin-bottom: 0.35rem;
            color: var(--muted);
            font-size: 0.9rem;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 0.65rem 0.85rem;
            background: #0f0c0d;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 0.95rem;
            font-family: inherit;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(188, 59, 40, 0.2);
        }
        .form-control[readonly] { background: #1a1617; color: var(--muted); }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }
        .form-grid .wide { grid-column: 1 / -1; }
        .form-hint {
            margin-top: 1rem;
            padding: 0.75rem 1rem;
            background: rgba(255, 222, 63, 0.08);
            border: 1px solid rgba(255, 222, 63, 0.2);
            border-radius: 10px;
            color: var(--muted);
            font-size: 0.9rem;
        }
        .badge {
            display: inline-flex;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        .badge-success { background: rgba(76, 175, 80, 0.2); color: var(--success); }
        .badge-warning { background: rgba(255, 193, 7, 0.2); color: var(--warning); }
        .badge-danger { background: rgba(244, 67, 54, 0.2); color: var(--danger); }
        .badge-info { background: rgba(33, 150, 243, 0.2); color: #2196f3; }
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        .card {
            background: rgba(255, 222, 63, 0.08);
            border: 1px solid rgba(255, 222, 63, 0.14);
            border-radius: 12px;
            padding: 1rem;
            box-shadow: var(--shadow);
        }
        .card .metric {
            display: block;
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--accent-2);
            margin-top: 0.5rem;
        }
        .filters {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .filters input, .filters select {
            padding: 0.55rem 0.75rem;
            background: #0f0c0d;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            min-width: 180px;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--muted);
        }
        .empty-state h3 { margin-bottom: 0.5rem; color: var(--text); }
        @media (max-width: 768px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar { position: static; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a href="/" class="brand">PartOps</a>
        <div class="user-info">
            <?php if ($user ?? false): ?>
                <span class="user-badge <?= $isAdmin ? 'admin' : '' ?>">
                    <?= htmlspecialchars($user['username']) ?> (<?= $user['role'] ?>)
                </span>
                <a href="/logout" class="btn btn-ghost btn-sm">Logout</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <h2>Inventory</h2>
            <nav>
                <a href="/" class="<?= ($_SERVER['REQUEST_URI'] ?? '') === '/' ? 'active' : '' ?>">Dashboard</a>
                <a href="/parts" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/parts') ? 'active' : '' ?>">Parts</a>
                <a href="/receiving" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/receiving') ? 'active' : '' ?>">Receiving</a>
                <a href="/checkout" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/checkout') ? 'active' : '' ?>">Checkout</a>
                <a href="/returns" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/returns') ? 'active' : '' ?>">Returns / Core</a>
            </nav>
            <div class="divider"></div>
            <h2>Data</h2>
            <nav>
                <a href="/suppliers" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/suppliers') ? 'active' : '' ?>">Suppliers</a>
                <a href="/locations" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/locations') ? 'active' : '' ?>">Locations</a>
                <a href="/technicians" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/technicians') ? 'active' : '' ?>">Technicians</a>
            </nav>
            <div class="divider"></div>
            <h2>Tools</h2>
            <nav>
                <a href="/qr" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/qr') ? 'active' : '' ?>">QR Scanner</a>
                <a href="/reports" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/reports') ? 'active' : '' ?>">Reports</a>
                <?php if ($isAdmin ?? false): ?>
                <a href="/admin" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/admin') ? 'active' : '' ?>">Admin</a>
                <?php endif; ?>
            </nav>
        </aside>

        <main class="content">
            <?php if ($flash['success'] ?? false): ?>
                <div class="flash flash-success"><?= htmlspecialchars($flash['success']) ?></div>
            <?php endif; ?>
            <?php if ($flash['error'] ?? false): ?>
                <div class="flash flash-error"><?= htmlspecialchars($flash['error']) ?></div>
            <?php endif; ?>
            <?php if ($flash['info'] ?? false): ?>
                <div class="flash flash-info"><?= htmlspecialchars($flash['info']) ?></div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>

    <script>
        const csrfToken = '<?= $csrfToken ?? '' ?>';
    </script>
</body>
</html>
