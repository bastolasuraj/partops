<div class="page-header">
    <div>
        <p class="eyebrow">Part Detail</p>
        <h1><?= htmlspecialchars($part['anchor_slug']) ?></h1>
        <p class="lede"><?= htmlspecialchars($part['name']) ?></p>
    </div>
    <div class="actions">
        <a href="/parts/<?= $part['id'] ?>/edit" class="btn btn-ghost">Edit</a>
        <a href="/parts" class="btn btn-ghost">Back to List</a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
    <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 12px; padding: 1rem;">
        <h3 style="color: var(--accent-2); margin-bottom: 1rem;">Basic Information</h3>
        <p><strong>Description:</strong> <?= htmlspecialchars($part['description'] ?? 'N/A') ?></p>
        <p><strong>Notes:</strong> <?= htmlspecialchars($part['notes'] ?? 'N/A') ?></p>
        <p><strong>Status:</strong> 
            <span class="badge <?= $part['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                <?= $part['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
        </p>
    </div>
    <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 12px; padding: 1rem;">
        <h3 style="color: var(--accent-2); margin-bottom: 1rem;">QR Code</h3>
        <p style="font-family: monospace; font-size: 0.8rem; word-break: break-all; background: #0f0c0d; padding: 0.5rem; border-radius: 6px;">
            <?= htmlspecialchars($qrPayload) ?>
        </p>
        <p style="color: var(--muted); font-size: 0.85rem; margin-top: 0.5rem;">
            Signed token for secure QR scanning
        </p>
    </div>
</div>

<h3 style="color: #fff; margin: 1.5rem 0 1rem;">Part Numbers</h3>
<?php if (!empty($part['part_numbers'])): ?>
<table class="grid">
    <thead>
        <tr>
            <th>Number</th>
            <th>Type</th>
            <th>Manufacturer</th>
            <th>Primary</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($part['part_numbers'] as $pn): ?>
        <tr>
            <td><?= htmlspecialchars($pn['value']) ?></td>
            <td>
                <span class="badge <?= $pn['type'] === 'active' ? 'badge-success' : ($pn['type'] === 'aftermarket' ? 'badge-info' : 'badge-warning') ?>">
                    <?= ucfirst($pn['type']) ?>
                </span>
            </td>
            <td><?= htmlspecialchars($pn['manufacturer'] ?? '-') ?></td>
            <td><?= $pn['is_primary'] ? '✓' : '-' ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p style="color: var(--muted);">No part numbers assigned.</p>
<?php endif; ?>

<h3 style="color: #fff; margin: 1.5rem 0 1rem;">Suppliers & Pricing</h3>
<?php if (!empty($part['suppliers'])): ?>
<table class="grid">
    <thead>
        <tr>
            <th>Supplier</th>
            <th>SKU</th>
            <th>Price</th>
            <th>Core Charge</th>
            <th>Expected Rebate</th>
            <th>Preferred</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($part['suppliers'] as $ps): ?>
        <tr>
            <td>
                <a href="/suppliers/<?= $ps['supplier_id'] ?>" style="color: var(--accent-2); text-decoration: none;">
                    <?= htmlspecialchars($ps['supplier_name']) ?>
                </a>
            </td>
            <td><?= htmlspecialchars($ps['sku'] ?? '-') ?></td>
            <td>$<?= number_format($ps['price'], 2) ?></td>
            <td>$<?= number_format($ps['core_charge'], 2) ?></td>
            <td>$<?= number_format($ps['expected_rebate'], 2) ?></td>
            <td><?= $ps['is_preferred'] ? '★' : '-' ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p style="color: var(--muted);">No supplier pricing assigned.</p>
<?php endif; ?>

<h3 style="color: #fff; margin: 1.5rem 0 1rem;">Inventory Levels</h3>
<?php if (!empty($part['inventory'])): ?>
<table class="grid">
    <thead>
        <tr>
            <th>Location</th>
            <th>On Hand</th>
            <th>Reserved</th>
            <th>Available</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($part['inventory'] as $inv): ?>
        <tr>
            <td><?= htmlspecialchars($inv['location_formatted'] ?? '-') ?></td>
            <td><?= $inv['on_hand'] ?></td>
            <td><?= $inv['reserved'] ?></td>
            <td><?= $inv['on_hand'] - $inv['reserved'] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p style="color: var(--muted);">No inventory at any location.</p>
<?php endif; ?>

<h3 style="color: #fff; margin: 1.5rem 0 1rem;">Recent Movement History</h3>
<?php if (!empty($moves)): ?>
<table class="grid">
    <thead>
        <tr>
            <th>Date</th>
            <th>Action</th>
            <th>Qty</th>
            <th>Location</th>
            <th>Technician</th>
            <th>Work Order</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($moves as $move): ?>
        <tr>
            <td><?= date('M j, Y g:ia', strtotime($move['created_at'])) ?></td>
            <td>
                <span class="badge <?= $move['direction'] === 'in' ? 'badge-success' : 'badge-warning' ?>">
                    <?= ucfirst($move['reason']) ?>
                </span>
            </td>
            <td><?= $move['direction'] === 'in' ? '+' : '-' ?><?= $move['qty'] ?></td>
            <td><?= $move['aisle'] ?? '-' ?>/<?= $move['shelf'] ?? '-' ?>/<?= $move['bay'] ?? '-' ?></td>
            <td><?= htmlspecialchars($move['technician_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($move['work_order_ref'] ?? '-') ?></td>
            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                <?= htmlspecialchars($move['notes'] ?? '-') ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p style="color: var(--muted);">No movement history yet.</p>
<?php endif; ?>
