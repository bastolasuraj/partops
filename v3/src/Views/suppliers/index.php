<div class="page-header">
    <div>
        <p class="eyebrow">Data</p>
        <h1>Suppliers</h1>
        <p class="lede">Manage part suppliers and ordering information.</p>
    </div>
    <div class="actions">
        <a href="/suppliers/new" class="btn btn-primary">Add Supplier</a>
    </div>
</div>

<div class="filters">
    <form method="GET" action="/suppliers" style="display: flex; gap: 0.75rem;">
        <input type="search" name="q" placeholder="Search suppliers..." value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" class="btn btn-ghost">Search</button>
    </form>
</div>

<?php if (!empty($suppliers)): ?>
<table class="grid">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Parts</th>
            <th>Preferred</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($suppliers as $s): ?>
        <tr>
            <td>
                <a href="/suppliers/<?= $s['id'] ?>" style="color: var(--accent-2); text-decoration: none; font-weight: 600;">
                    <?= htmlspecialchars($s['name']) ?>
                </a>
            </td>
            <td><?= htmlspecialchars($s['contact_email'] ?? '-') ?></td>
            <td><?= htmlspecialchars($s['contact_phone'] ?? '-') ?></td>
            <td><?= $s['parts_count'] ?? 0 ?></td>
            <td><?= $s['is_preferred'] ? '★' : '-' ?></td>
            <td>
                <a href="/suppliers/<?= $s['id'] ?>/edit" class="btn btn-ghost btn-sm">Edit</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="empty-state">
    <h3>No suppliers found</h3>
    <p>Add your first supplier to get started.</p>
    <a href="/suppliers/new" class="btn btn-primary" style="margin-top: 1rem;">Add Supplier</a>
</div>
<?php endif; ?>
