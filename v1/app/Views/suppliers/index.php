<?php ob_start(); ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h1>Suppliers</h1>
    <a href="<?= url('/suppliers/create') ?>" class="btn">Add New Supplier</a>
</div>

<?php if (empty($suppliers)): ?>
    <p>No suppliers found. <a href="<?= url('/suppliers/create') ?>">Create your first supplier</a>.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Contact</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Preferred</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($suppliers as $supplier): ?>
            <tr>
                <td><?= htmlspecialchars($supplier['name']) ?></td>
                <td><?= htmlspecialchars($supplier['contact_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($supplier['email'] ?? '-') ?></td>
                <td><?= htmlspecialchars($supplier['phone'] ?? '-') ?></td>
                <td>
                    <?php if ($supplier['is_preferred']): ?>
                        <span style="color: rgb(255, 222, 63); text-shadow: 0 0 2px rgba(0,0,0,0.5);">⭐</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span style="
                        padding: 2px 6px; 
                        border-radius: 4px; 
                        font-size: 0.85em; 
                        background: <?= $supplier['is_active'] ? 'rgba(40, 167, 69, 0.2)' : 'rgba(220, 53, 69, 0.2)' ?>;
                        color: <?= $supplier['is_active'] ? '#28a745' : '#dc3545' ?>;
                    ">
                        <?= $supplier['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </td>
                <td style="display: flex; gap: 0.5rem; align-items: center;">
                    <a href="<?= url('/suppliers/' . $supplier['id']) ?>" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.9em;">View</a>
                    <a href="<?= url('/suppliers/' . $supplier['id'] . '/edit') ?>" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.9em;">Edit</a>
                    
                    <form action="<?= url('/suppliers/' . $supplier['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Are you sure? This cannot be undone.');" style="display: inline;">
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