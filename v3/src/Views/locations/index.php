<div class="page-header">
    <div>
        <p class="eyebrow">Data</p>
        <h1>Locations</h1>
        <p class="lede">Warehouse locations by aisle, shelf, and bay.</p>
    </div>
    <div class="actions">
        <a href="/locations/new" class="btn btn-primary">Add Location</a>
    </div>
</div>

<?php if (!empty($locations)): ?>
<table class="grid">
    <thead>
        <tr>
            <th>Aisle</th>
            <th>Shelf</th>
            <th>Bay</th>
            <th>Bin</th>
            <th>Items</th>
            <th>Stock</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($locations as $loc): ?>
        <tr>
            <td style="font-weight: 600;"><?= htmlspecialchars($loc['aisle']) ?></td>
            <td><?= htmlspecialchars($loc['shelf']) ?></td>
            <td><?= htmlspecialchars($loc['bay']) ?></td>
            <td><?= htmlspecialchars($loc['bin'] ?? '-') ?></td>
            <td><?= $loc['items_count'] ?? 0 ?></td>
            <td><?= $loc['total_stock'] ?? 0 ?></td>
            <td>
                <a href="/locations/<?= $loc['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                <a href="/locations/<?= $loc['id'] ?>/edit" class="btn btn-ghost btn-sm">Edit</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="empty-state">
    <h3>No locations defined</h3>
    <p>Create warehouse locations to track inventory placement.</p>
    <a href="/locations/new" class="btn btn-primary" style="margin-top: 1rem;">Add Location</a>
</div>
<?php endif; ?>
