<?php ob_start(); ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h1>Parts</h1>
    <a href="/parts/create" class="btn">Add New Part</a>
</div>

<?php if (empty($parts)): ?>
    <p>No parts found. <a href="/parts/create">Create your first part</a>.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Anchor Slug</th>
                <th>Name</th>
                <th>Description</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($parts as $part): ?>
            <tr>
                <td><?= htmlspecialchars($part['id']) ?></td>
                <td><?= htmlspecialchars($part['anchor_slug']) ?></td>
                <td><?= htmlspecialchars($part['name']) ?></td>
                <td><?= htmlspecialchars(substr($part['description'] ?? '', 0, 50)) ?></td>
                <td><?= $part['is_active'] ? 'Active' : 'Inactive' ?></td>
                <td>
                    <a href="/parts/<?= $part['id'] ?>">View</a> |
                    <a href="/parts/<?= $part['id'] ?>/edit">Edit</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
