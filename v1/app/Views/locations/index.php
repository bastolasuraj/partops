<?php ob_start(); ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h1>Warehouse Locations</h1>
    <a href="/locations/create" class="btn">Add New Location</a>
</div>

<?php if (empty($locations)): ?>
    <p>No locations found. <a href="/locations/create">Create your first location</a>.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Aisle</th>
                <th>Shelf</th>
                <th>Bay</th>
                <th>Bin</th>
                <th>Location Code</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($locations as $location): ?>
            <?php
                $code = $location['aisle'] . '-' . $location['shelf'] . '-' . $location['bay'];
                if (!empty($location['bin'])) {
                    $code .= '-' . $location['bin'];
                }
            ?>
            <tr>
                <td><?= htmlspecialchars($location['id']) ?></td>
                <td><?= htmlspecialchars($location['aisle']) ?></td>
                <td><?= htmlspecialchars($location['shelf']) ?></td>
                <td><?= htmlspecialchars($location['bay']) ?></td>
                <td><?= htmlspecialchars($location['bin'] ?? '-') ?></td>
                <td><strong><?= htmlspecialchars($code) ?></strong></td>
                <td>
                    <a href="/locations/<?= $location['id'] ?>">View</a> |
                    <a href="/locations/<?= $location['id'] ?>/edit">Edit</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
