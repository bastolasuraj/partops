<?php ob_start(); ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h1>Suppliers</h1>
    <a href="/suppliers/create" class="btn">Add New Supplier</a>
</div>

<?php if (empty($suppliers)): ?>
    <p>No suppliers found. <a href="/suppliers/create">Create your first supplier</a>.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Preferred</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($suppliers as $supplier): ?>
            <tr>
                <td><?= htmlspecialchars($supplier['id']) ?></td>
                <td><?= htmlspecialchars($supplier['name']) ?></td>
                <td><?= htmlspecialchars($supplier['contact_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($supplier['email'] ?? '-') ?></td>
                <td><?= htmlspecialchars($supplier['phone'] ?? '-') ?></td>
                <td><?= $supplier['is_preferred'] ? '⭐' : '' ?></td>
                <td>
                    <a href="/suppliers/<?= $supplier['id'] ?>">View</a> |
                    <a href="/suppliers/<?= $supplier['id'] ?>/edit">Edit</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
