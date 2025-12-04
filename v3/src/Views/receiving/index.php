<div class="page-header">
    <div>
        <p class="eyebrow">Inventory</p>
        <h1>Receiving</h1>
        <p class="lede">Record incoming parts from suppliers with pricing and core charge details.</p>
    </div>
</div>

<form method="POST" action="/receiving" id="receivingForm">
    <?= $csrf ?>
    <input type="hidden" name="idempotency_key" value="<?= htmlspecialchars($idempotencyKey) ?>">

    <div class="form-grid">
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
            <label for="supplier_id">Supplier *</label>
            <select id="supplier_id" name="supplier_id" class="form-control" required>
                <option value="">Select a supplier...</option>
                <?php foreach ($suppliers as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $s['is_preferred'] ? 'data-preferred="true"' : '' ?>>
                    <?= htmlspecialchars($s['name']) ?><?= $s['is_preferred'] ? ' (Preferred)' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="location_id">Location *</label>
            <select id="location_id" name="location_id" class="form-control" required>
                <option value="">Select location...</option>
                <?php foreach ($locations as $loc): ?>
                <option value="<?= $loc['id'] ?>"><?= $loc['aisle'] ?> / <?= $loc['shelf'] ?> / <?= $loc['bay'] ?><?= $loc['bin'] ? ' / ' . $loc['bin'] : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="quantity">Quantity *</label>
            <input type="number" id="quantity" name="quantity" class="form-control" min="1" value="1" required>
        </div>

        <div class="form-group">
            <label for="sku">Supplier SKU</label>
            <input type="text" id="sku" name="sku" class="form-control" placeholder="Supplier's part number">
        </div>

        <div class="form-group">
            <label for="price">Unit Price ($)</label>
            <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" placeholder="0.00">
        </div>

        <div class="form-group">
            <label for="core_charge">Core Charge ($)</label>
            <input type="number" id="core_charge" name="core_charge" class="form-control" step="0.01" min="0" placeholder="0.00">
        </div>

        <div class="form-group">
            <label for="expected_rebate">Expected Rebate ($)</label>
            <input type="number" id="expected_rebate" name="expected_rebate" class="form-control" step="0.01" min="0" placeholder="0.00">
        </div>

        <div class="form-group wide">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-control" rows="2" placeholder="Invoice number, condition notes..."></textarea>
        </div>
    </div>

    <div class="form-hint">
        Price and core charge values will be snapshot at the time of this transaction for accurate historical reporting.
    </div>

    <div style="margin-top: 1.5rem;">
        <button type="submit" class="btn btn-primary">Receive Parts</button>
    </div>
</form>
