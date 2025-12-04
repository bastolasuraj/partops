<div class="page-header">
    <div>
        <p class="eyebrow">Supplier</p>
        <h1><?= htmlspecialchars($supplier['name']) ?></h1>
    </div>
    <div class="actions">
        <a href="/suppliers/<?= $supplier['id'] ?>/edit" class="btn btn-ghost">Edit</a>
        <a href="/suppliers" class="btn btn-ghost">Back</a>
    </div>
</div>

<div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem;">
    <p><strong>Email:</strong> <?= htmlspecialchars($supplier['contact_email'] ?? 'N/A') ?></p>
    <p><strong>Phone:</strong> <?= htmlspecialchars($supplier['contact_phone'] ?? 'N/A') ?></p>
    <?php if ($supplier['reorder_url']): ?>
    <p><strong>Reorder Portal:</strong> <a href="<?= htmlspecialchars($supplier['reorder_url']) ?>" target="_blank" style="color: var(--accent-2);">Open Portal</a></p>
    <?php endif; ?>
    <p><strong>Preferred:</strong> <?= $supplier['is_preferred'] ? 'Yes' : 'No' ?></p>
</div>

<h3 style="color: #fff; margin-bottom: 1rem;">Parts from this Supplier</h3>
<?php if (!empty($parts)): ?>
<table class="grid">
    <thead>
        <tr>
            <th>Anchor</th>
            <th>Part Name</th>
            <th>SKU</th>
            <th>Price</th>
            <th>Core Charge</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($parts as $p): ?>
        <tr>
            <td><a href="/parts/<?= $p['part_id'] ?>" style="color: var(--accent-2);"><?= htmlspecialchars($p['anchor_slug']) ?></a></td>
            <td><?= htmlspecialchars($p['part_name']) ?></td>
            <td><?= htmlspecialchars($p['sku'] ?? '-') ?></td>
            <td>$<?= number_format($p['price'], 2) ?></td>
            <td>$<?= number_format($p['core_charge'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p style="color: var(--muted);">No parts linked to this supplier yet.</p>
<?php endif; ?>
