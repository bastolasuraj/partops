<?php ob_start(); ?>

<h1>Dashboard</h1>

<p>Welcome to PartOps, <?= htmlspecialchars($user) ?>!</p>

<div style="margin-top: 2rem;">
    <h2>Quick Actions</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
        <a href="<?= url('/parts') ?>" class="btn">View Parts</a>
        <a href="<?= url('/suppliers') ?>" class="btn">View Suppliers</a>
        <a href="<?= url('/inventory') ?>" class="btn">Manage Inventory</a>
        <a href="<?= url('/work-orders') ?>" class="btn">Work Orders</a>
    </div>
</div>

<div style="margin-top: 2rem;">
    <h2>System Status</h2>
    <p>Phase 1: Foundation - In Development</p>
    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
        <li>✓ MVC Structure</li>
        <li>✓ Database Schema</li>
        <li>✓ Authentication</li>
        <li>⧗ CRUD Operations (In Progress)</li>
        <li>��� Search Functionality (Pending)</li>
    </ul>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
