<?php ob_start(); ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h1><?= $title ?? 'Parts' ?></h1>
    <div style="display: flex; gap: 1rem;">
        <form action="<?= url('/parts/search') ?>" method="GET" style="display: flex; gap: 0.5rem;">
            <input type="text" name="q" placeholder="Search parts..." value="<?= htmlspecialchars($query ?? '') ?>" style="width: 200px;">
            <button type="submit" class="btn btn-secondary">Search</button>
            <?php if (isset($query)): ?>
                <a href="<?= url('/parts') ?>" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
        <a href="<?= url('/parts/create') ?>" class="btn">Add New Part</a>
    </div>
</div>

<?php if (empty($parts)): ?>
    <p>No parts found. <a href="<?= url('/parts/create') ?>">Create your first part</a>.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Slug</th>
                <th>Name</th>
                <th>Description</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($parts as $part): ?>
            <tr>
                <td style="font-family: monospace;"><?= htmlspecialchars($part['anchor_slug']) ?></td>
                <td><?= htmlspecialchars($part['name']) ?></td>
                <td><?= htmlspecialchars(substr($part['description'] ?? '', 0, 50)) . (strlen($part['description'] ?? '') > 50 ? '...' : '') ?></td>
                <td>
                    <span style="
                        padding: 2px 6px; 
                        border-radius: 4px; 
                        font-size: 0.85em; 
                        background: <?= $part['is_active'] ? 'rgba(40, 167, 69, 0.2)' : 'rgba(220, 53, 69, 0.2)' ?>;
                        color: <?= $part['is_active'] ? '#28a745' : '#dc3545' ?>;
                    ">
                        <?= $part['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </td>
                <td style="display: flex; gap: 0.5rem; align-items: center;">
                    <a href="<?= url('/parts/' . $part['id']) ?>" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.9em;">View</a>
                    <a href="<?= url('/parts/' . $part['id'] . '/edit') ?>" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.9em;">Edit</a>
                    
                    <form action="<?= url('/parts/' . $part['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Are you sure? This cannot be undone.');" style="display: inline;">
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