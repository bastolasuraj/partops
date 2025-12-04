<div class="page-header">
    <div>
        <p class="eyebrow">Overview</p>
        <h1>Dashboard</h1>
        <p class="lede">Quick overview of your parts inventory status.</p>
    </div>
</div>

<div class="cards">
    <div class="card">
        <div>Low Stock Alerts</div>
        <span class="metric"><?= $stats['lowStock'] ?? 0 ?></span>
    </div>
    <div class="card">
        <div>Pending Core Rebates</div>
        <span class="metric">$<?= number_format($stats['pendingCoresValue'] ?? 0, 2) ?></span>
    </div>
    <div class="card">
        <div>Aging Inventory (90d+)</div>
        <span class="metric"><?= $stats['agingInventory'] ?? 0 ?></span>
    </div>
    <div class="card">
        <div>Weekly Usage</div>
        <span class="metric"><?= $stats['weeklyUsage'] ?? 0 ?></span>
    </div>
</div>

<h2 style="margin: 2rem 0 1rem; color: #fff;">Recent Activity</h2>

<?php if (!empty($recentMoves)): ?>
<table class="grid">
    <thead>
        <tr>
            <th>Part</th>
            <th>Action</th>
            <th>Qty</th>
            <th>Location</th>
            <th>By</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($recentMoves as $move): ?>
        <tr>
            <td>
                <a href="/parts/<?= $move['part_id'] ?>" style="color: var(--accent-2); text-decoration: none;">
                    <?= htmlspecialchars($move['anchor_slug'] ?? 'Unknown') ?>
                </a>
            </td>
            <td>
                <span class="badge <?= $move['direction'] === 'in' ? 'badge-success' : 'badge-warning' ?>">
                    <?= ucfirst($move['reason']) ?>
                </span>
            </td>
            <td><?= $move['direction'] === 'in' ? '+' : '-' ?><?= $move['qty'] ?></td>
            <td><?= $move['aisle'] ?? '-' ?>/<?= $move['shelf'] ?? '-' ?>/<?= $move['bay'] ?? '-' ?></td>
            <td><?= htmlspecialchars($move['created_by_name'] ?? 'System') ?></td>
            <td><?= date('M j, g:ia', strtotime($move['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="empty-state">
    <h3>No recent activity</h3>
    <p>Start by receiving some parts into inventory.</p>
    <a href="/receiving" class="btn btn-primary" style="margin-top: 1rem;">Go to Receiving</a>
</div>
<?php endif; ?>

<div style="margin-top: 2rem;">
    <h3 style="color: #fff; margin-bottom: 1rem;">Quick Actions</h3>
    <div class="actions">
        <a href="/receiving" class="btn btn-primary">Receive Parts</a>
        <a href="/checkout" class="btn btn-ghost">Checkout to Tech</a>
        <a href="/qr" class="btn btn-ghost">QR Scanner</a>
        <a href="/reports" class="btn btn-ghost">View Reports</a>
    </div>
</div>
