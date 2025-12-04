<div class="page-header">
    <div>
        <p class="eyebrow">Data</p>
        <h1>Technicians</h1>
        <p class="lede">Manage technicians who check out parts.</p>
    </div>
    <div class="actions">
        <a href="/technicians/new" class="btn btn-primary">Add Technician</a>
    </div>
</div>

<?php if (!empty($technicians)): ?>
<table class="grid">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Total Checkouts</th>
            <th>Active Checkouts</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($technicians as $tech): ?>
        <tr>
            <td style="font-weight: 600;"><?= htmlspecialchars($tech['name']) ?></td>
            <td><?= htmlspecialchars($tech['email'] ?? '-') ?></td>
            <td><?= htmlspecialchars($tech['phone'] ?? '-') ?></td>
            <td><?= $tech['total_checkouts'] ?? 0 ?></td>
            <td>
                <?php if (($tech['active_checkouts'] ?? 0) > 0): ?>
                <span class="badge badge-warning"><?= $tech['active_checkouts'] ?></span>
                <?php else: ?>
                0
                <?php endif; ?>
            </td>
            <td>
                <a href="/technicians/<?= $tech['id'] ?>/edit" class="btn btn-ghost btn-sm">Edit</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="empty-state">
    <h3>No technicians</h3>
    <p>Add technicians to track parts checkouts.</p>
    <a href="/technicians/new" class="btn btn-primary" style="margin-top: 1rem;">Add Technician</a>
</div>
<?php endif; ?>
