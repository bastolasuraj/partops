<div class="page-header">
    <div>
        <p class="eyebrow">Inventory</p>
        <h1>Parts Catalog</h1>
        <p class="lede">Search by anchor, part number (OEM/aftermarket/historical), or supplier.</p>
    </div>
    <div class="actions">
        <a href="/parts/new" class="btn btn-primary">New Part</a>
    </div>
</div>

<div class="filters">
    <form method="GET" action="/parts" style="display: flex; gap: 0.75rem; flex-wrap: wrap; width: 100%;">
        <input type="search" name="q" placeholder="Search parts..." value="<?= htmlspecialchars($search ?? '') ?>" style="flex: 1; min-width: 250px;">
        <select name="status">
            <option value="active" <?= ($status ?? 'active') === 'active' ? 'selected' : '' ?>>Active Only</option>
            <option value="all" <?= ($status ?? '') === 'all' ? 'selected' : '' ?>>All Parts</option>
        </select>
        <button type="submit" class="btn btn-ghost">Search</button>
    </form>
</div>

<?php if (!empty($parts)): ?>
<table class="grid">
    <thead>
        <tr>
            <th>Anchor</th>
            <th>Active #</th>
            <th>Alternates</th>
            <th>Supplier</th>
            <th>On Hand</th>
            <th>Location</th>
            <th>Core</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($parts as $part): ?>
        <tr>
            <td>
                <a href="/parts/<?= $part['id'] ?>" style="color: var(--accent-2); text-decoration: none; font-weight: 600;">
                    <?= htmlspecialchars($part['anchor_slug']) ?>
                </a>
            </td>
            <td><?= htmlspecialchars($part['active_number'] ?? '-') ?></td>
            <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;">
                <?= htmlspecialchars($part['alternates'] ?? '-') ?>
            </td>
            <td><?= htmlspecialchars($part['supplier_name'] ?? '-') ?></td>
            <td>
                <?php 
                $stock = (int)($part['total_stock'] ?? 0);
                $badgeClass = $stock < 5 ? 'badge-danger' : ($stock < 10 ? 'badge-warning' : 'badge-success');
                ?>
                <span class="badge <?= $badgeClass ?>"><?= $stock ?></span>
            </td>
            <td><?= htmlspecialchars($part['location'] ?? '-') ?></td>
            <td>
                <?php if (($part['core_charge'] ?? 0) > 0): ?>
                    $<?= number_format($part['core_charge'], 2) ?> / $<?= number_format($part['expected_rebate'] ?? 0, 2) ?> exp.
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
            <td>
                <a href="/parts/<?= $part['id'] ?>/edit" class="btn btn-ghost btn-sm">Edit</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="empty-state">
    <h3>No parts found</h3>
    <p>Create your first part to get started.</p>
    <a href="/parts/new" class="btn btn-primary" style="margin-top: 1rem;">Create Part</a>
</div>
<?php endif; ?>
