<?php ob_start(); ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h1>Work Orders</h1>
    <a href="/work-orders/create" class="btn">Create Work Order</a>
</div>

<?php if (empty($workOrders)): ?>
    <p>No work orders found. <a href="/work-orders/create">Create your first work order</a>.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>External Ref</th>
                <th>Vehicle</th>
                <th>Status</th>
                <th>Opened</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($workOrders as $wo): ?>
            <tr>
                <td><?= htmlspecialchars($wo['id']) ?></td>
                <td><strong><?= htmlspecialchars($wo['external_ref']) ?></strong></td>
                <td><?= htmlspecialchars($wo['vehicle_ref'] ?? '-') ?></td>
                <td>
                    <span style="padding: 0.25rem 0.5rem; border-radius: 4px; background: rgba(255,255,255,0.1);">
                        <?= htmlspecialchars(ucfirst($wo['status'])) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($wo['opened_at']))) ?></td>
                <td>
                    <a href="/work-orders/<?= $wo['id'] ?>">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
