<div class="page-header">
    <div>
        <p class="eyebrow">Location</p>
        <h1><?= $location['aisle'] ?> / <?= $location['shelf'] ?> / <?= $location['bay'] ?><?= $location['bin'] ? ' / ' . $location['bin'] : '' ?></h1>
    </div>
    <div class="actions">
        <a href="/locations/<?= $location['id'] ?>/edit" class="btn btn-ghost">Edit</a>
        <a href="/locations" class="btn btn-ghost">Back</a>
    </div>
</div>

<h3 style="color: #fff; margin-bottom: 1rem;">Inventory at this Location</h3>
<?php if (!empty($inventory)): ?>
<table class="grid">
    <thead>
        <tr>
            <th>Anchor</th>
            <th>Part #</th>
            <th>Part Name</th>
            <th>On Hand</th>
            <th>Reserved</th>
            <th>Available</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($inventory as $inv): ?>
        <tr>
            <td><a href="/parts/<?= $inv['part_id'] ?>" style="color: var(--accent-2);"><?= htmlspecialchars($inv['anchor_slug']) ?></a></td>
            <td><?= htmlspecialchars($inv['part_number'] ?? '-') ?></td>
            <td><?= htmlspecialchars($inv['part_name']) ?></td>
            <td><?= $inv['on_hand'] ?></td>
            <td><?= $inv['reserved'] ?></td>
            <td><?= $inv['on_hand'] - $inv['reserved'] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p style="color: var(--muted);">No inventory at this location.</p>
<?php endif; ?>
