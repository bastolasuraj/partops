<?php
// Get base URL from environment or detect it
$baseUrl = $_ENV['APP_URL'] ?? '';
if (empty($baseUrl)) {
    // Auto-detect base URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $baseUrl = $protocol . '://' . $host . dirname(dirname($scriptName));
}
$baseUrl = rtrim($baseUrl, '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'PartOps' ?></title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
</head>
<body>
    <header class="topbar">
        <div class="brand">PartOps</div>
        <nav class="main-nav">
            <a href="<?= $baseUrl ?>/" class="nav-link">Dashboard</a>
            <a href="<?= $baseUrl ?>/parts" class="nav-link">Parts</a>
            <a href="<?= $baseUrl ?>/receiving" class="nav-link">Receiving</a>
            <a href="<?= $baseUrl ?>/checkout" class="nav-link">Checkout</a>
            <a href="<?= $baseUrl ?>/returns" class="nav-link">Returns</a>
            <a href="<?= $baseUrl ?>/work-orders" class="nav-link">Work Orders</a>
            <a href="<?= $baseUrl ?>/locations" class="nav-link">Locations</a>
            <a href="<?= $baseUrl ?>/suppliers" class="nav-link">Suppliers</a>
            <a href="<?= $baseUrl ?>/technicians" class="nav-link">Technicians</a>
            <?php if ($user && $user['role'] === 'admin'): ?>
            <a href="<?= $baseUrl ?>/reports" class="nav-link">Reports</a>
            <a href="<?= $baseUrl ?>/audit-log" class="nav-link">Audit</a>
            <?php endif; ?>
        </nav>
        <div class="user-menu">
            <?php if ($user): ?>
                <span class="user-name"><?= htmlspecialchars($user['username']) ?></span>
                <span class="badge badge-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
                <a href="<?= $baseUrl ?>/logout" class="btn-ghost">Logout</a>
            <?php else: ?>
                <a href="<?= $baseUrl ?>/login" class="btn-primary">Login</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>">
        <?= htmlspecialchars($flash['message']) ?>
        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
    </div>
    <?php endif; ?>

    <main class="main-content">
        <?= $content ?>
    </main>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> PartOps - Parts Inventory Management</p>
    </footer>

    <script src="/assets/js/app.js"></script>
</body>
</html>
