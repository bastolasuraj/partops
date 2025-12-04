<div class="page-header">
    <div>
        <p class="eyebrow">Inventory</p>
        <h1>Returns & Core</h1>
        <p class="lede">Process part returns and manage core charge tracking.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem;">
        <h3 style="color: var(--accent-2); margin-bottom: 1rem;">Return Part to Stock</h3>
        
        <form method="POST" action="/returns">
            <?= $csrf ?>
            <input type="hidden" name="idempotency_key" value="<?= htmlspecialchars($idempotencyKey) ?>">
            <input type="hidden" name="return_type" value="standard">

            <div class="form-group">
                <label for="part_id">Part *</label>
                <select id="part_id" name="part_id" class="form-control" required>
                    <option value="">Select a part...</option>
                    <?php foreach ($parts as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['anchor_slug']) ?> - <?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="location_id">Return to Location *</label>
                <select id="location_id" name="location_id" class="form-control" required>
                    <option value="">Select location...</option>
                    <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc['id'] ?>"><?= $loc['aisle'] ?> / <?= $loc['shelf'] ?> / <?= $loc['bay'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity *</label>
                <input type="number" name="quantity" class="form-control" min="1" value="1" required>
            </div>

            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Reason for return..."></textarea>
            </div>

            <button type="submit" class="btn btn-success">Return to Stock</button>
        </form>
    </div>

    <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem;">
        <h3 style="color: var(--accent-2); margin-bottom: 1rem;">Record Core Return</h3>
        
        <form method="POST" action="/returns">
            <?= $csrf ?>
            <input type="hidden" name="idempotency_key" value="<?= htmlspecialchars($idempotencyKey) ?>-core">
            <input type="hidden" name="return_type" value="core">
            <input type="hidden" name="location_id" value="<?= $locations[0]['id'] ?? 0 ?>">

            <div class="form-group">
                <label for="core_part_id">Part *</label>
                <select name="part_id" class="form-control" required>
                    <option value="">Select a part...</option>
                    <?php foreach ($parts as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['anchor_slug']) ?> - <?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Quantity *</label>
                <input type="number" name="quantity" class="form-control" min="1" value="1" required>
            </div>

            <div class="form-group">
                <label>Core Status *</label>
                <select name="core_due_state" class="form-control" required>
                    <option value="sent">Sent to Supplier</option>
                    <option value="rebated">Rebate Received</option>
                </select>
            </div>

            <div class="form-group">
                <label>Original Core Charge ($)</label>
                <input type="number" name="core_charge" class="form-control" step="0.01" min="0" placeholder="0.00">
            </div>

            <div class="form-group">
                <label>Rebate Received ($)</label>
                <input type="number" name="rebate_received" class="form-control" step="0.01" min="0" placeholder="0.00">
            </div>

            <button type="submit" class="btn btn-primary">Record Core Return</button>
        </form>
    </div>
</div>

<?php if (!empty($pendingCores)): ?>
<h3 style="color: #fff; margin: 2rem 0 1rem;">Pending Core Returns</h3>
<table class="grid">
    <thead>
        <tr>
            <th>Part</th>
            <th>Supplier</th>
            <th>Core Charge</th>
            <th>Expected Rebate</th>
            <th>Status</th>
            <th>Received Date</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pendingCores as $core): ?>
        <tr>
            <td>
                <a href="/parts/<?= $core['part_id'] ?>" style="color: var(--accent-2); text-decoration: none;">
                    <?= htmlspecialchars($core['anchor_slug']) ?>
                </a>
            </td>
            <td><?= htmlspecialchars($core['supplier_name'] ?? '-') ?></td>
            <td>$<?= number_format($core['core_charge_at_tx'] ?? 0, 2) ?></td>
            <td>$<?= number_format($core['core_rebate_expected'] ?? 0, 2) ?></td>
            <td>
                <span class="badge <?= $core['core_due_state'] === 'due' ? 'badge-warning' : 'badge-info' ?>">
                    <?= ucfirst($core['core_due_state']) ?>
                </span>
            </td>
            <td><?= date('M j, Y', strtotime($core['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
