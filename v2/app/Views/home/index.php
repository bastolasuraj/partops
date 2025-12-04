<?php
/**
 * Home/Dashboard View
 * This file should be moved to: app/Views/home/index.php
 */
$title = 'Dashboard - PartOps';
?>

<div class="dashboard">
    <header class="section-head">
        <div>
            <p class="eyebrow">Overview</p>
            <h1>Dashboard</h1>
            <p class="lede">Quick overview of inventory status and recent activity.</p>
        </div>
    </header>

    <div class="cards">
        <div class="card">
            <span class="card-label">Total Parts</span>
            <span class="metric"><?= number_format($stats['total_parts'] ?? 0) ?></span>
        </div>
        <div class="card">
            <span class="card-label">Low Stock Alerts</span>
            <span class="metric metric-warning"><?= number_format($stats['low_stock'] ?? 0) ?></span>
        </div>
        <div class="card">
            <span class="card-label">Open Work Orders</span>
            <span class="metric"><?= number_format($stats['open_work_orders'] ?? 0) ?></span>
        </div>
        <div class="card">
            <span class="card-label">Pending Core Returns</span>
            <span class="metric metric-warning"><?= number_format($stats['pending_cores'] ?? 0) ?></span>
        </div>
    </div>

    <div class="dashboard-grid">
        <section class="panel">
            <h2>Recent Activity</h2>
            <?php if (!empty($recentMoves)): ?>
            <table class="grid">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Action</th>
                        <th>Part</th>
                        <th>Qty</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentMoves as $move): ?>
                    <tr>
                        <td><?= date('M j, g:i a', strtotime($move['created_at'])) ?></td>
                        <td>
                            <span class="badge badge-<?= $move['direction'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $move['reason'])) ?>
                            </span>
                        </td>
                        <td>
                            <a href="/parts/<?= $move['part_id'] ?>">
                                <?= htmlspecialchars($move['part_name']) ?>
                            </a>
                        </td>
                        <td class="<?= $move['qty'] > 0 ? 'text-success' : 'text-danger' ?>">
                            <?= $move['qty'] > 0 ? '+' : '' ?><?= $move['qty'] ?>
                        </td>
                        <td><?= htmlspecialchars($move['aisle'] . '/' . $move['shelf'] . '/' . $move['bay']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="empty-state">No recent activity.</p>
            <?php endif; ?>
        </section>

        <section class="panel">
            <h2>Low Stock Items</h2>
            <?php if (!empty($lowStock)): ?>
            <table class="grid">
                <thead>
                    <tr>
                        <th>Part</th>
                        <th>Stock</th>
                        <th>Min</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lowStock as $part): ?>
                    <tr>
                        <td>
                            <a href="/parts/<?= $part['id'] ?>">
                                <?= htmlspecialchars($part['name']) ?>
                            </a>
                            <small class="text-muted"><?= htmlspecialchars($part['primary_number'] ?? '') ?></small>
                        </td>
                        <td class="text-danger"><?= $part['total_stock'] ?></td>
                        <td><?= $part['min_stock_level'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="empty-state">All items are adequately stocked.</p>
            <?php endif; ?>
        </section>

        <section class="panel">
            <h2>Pending Core Returns</h2>
            <?php if (!empty($pendingCores)): ?>
            <table class="grid">
                <thead>
                    <tr>
                        <th>Part</th>
                        <th>Supplier</th>
                        <th>Due</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingCores as $core): ?>
                    <tr class="<?= ($core['days_until_due'] ?? 0) < 0 ? 'row-danger' : '' ?>">
                        <td><?= htmlspecialchars($core['part_name']) ?></td>
                        <td><?= htmlspecialchars($core['supplier_name']) ?></td>
                        <td>
                            <?php if (($core['days_until_due'] ?? 0) < 0): ?>
                                <span class="text-danger">Overdue</span>
                            <?php else: ?>
                                <?= $core['days_until_due'] ?> days
                            <?php endif; ?>
                        </td>
                        <td>$<?= number_format($core['expected_rebate'] * $core['qty_due'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="/core-liabilities" class="btn-ghost">View All</a>
            <?php else: ?>
            <p class="empty-state">No pending core returns.</p>
            <?php endif; ?>
        </section>
    </div>

    <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <a href="/receiving/new" class="btn-primary">Receive New Parts</a>
            <a href="/checkout/create" class="btn-primary">Checkout Parts</a>
            <a href="/returns/standard" class="btn-ghost">Standard Return</a>
            <a href="/returns/core" class="btn-ghost">Core Return</a>
            <a href="/parts/create" class="btn-ghost">Add New Part</a>
        </div>
    </div>
</div>
