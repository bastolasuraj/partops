<?php ob_start(); ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h1>Warehouse Locations</h1>
    <a href="<?= url('/locations/create') ?>" class="btn">Add New Location</a>
</div>

<?php if (empty($locations)): ?>
    <p>No locations found. <a href="<?= url('/locations/create') ?>">Create your first location</a>.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Location Code</th>
                <th>Aisle</th>
                <th>Shelf</th>
                <th>Bay</th>
                <th>Bin</th>
                <th>Status</th>
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
                <td><strong><?= htmlspecialchars($code) ?></strong></td>
                <td><?= htmlspecialchars($location['aisle']) ?></td>
                <td><?= htmlspecialchars($location['shelf']) ?></td>
                <td><?= htmlspecialchars($location['bay']) ?></td>
                <td><?= htmlspecialchars($location['bin'] ?? '-') ?></td>
                <td>
                    <span style="
                        padding: 2px 6px; 
                        border-radius: 4px; 
                        font-size: 0.85em; 
                        background: <?= $location['is_active'] ? 'rgba(40, 167, 69, 0.2)' : 'rgba(220, 53, 69, 0.2)' ?>;
                        color: <?= $location['is_active'] ? '#28a745' : '#dc3545' ?>;
                    ">
                        <?= $location['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </td>
                <td style="display: flex; gap: 0.5rem; align-items: center;">
                    <a href="<?= url('/locations/' . $location['id']) ?>" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.9em;">View</a>
                    <a href="<?= url('/locations/' . $location['id'] . '/edit') ?>" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.9em;">Edit</a>
                    
                    <form action="<?= url('/locations/' . $location['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Are you sure? This cannot be undone.');" style="display: inline;">
                        <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token'] ?? '' ?>">
                        <button type="submit" class="btn" style="padding: 0.25rem 0.5rem; font-size: 0.9em; background: #dc3545;">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>