<div class="page-header">
    <div>
        <p class="eyebrow">Inventory</p>
        <h1>Checkout to Technician</h1>
        <p class="lede">Issue parts to technicians for work orders.</p>
    </div>
</div>

<form method="POST" action="/checkout" id="checkoutForm">
    <?= $csrf ?>
    <input type="hidden" name="idempotency_key" value="<?= htmlspecialchars($idempotencyKey) ?>">

    <div class="form-grid">
        <div class="form-group">
            <label for="part_id">Part *</label>
            <select id="part_id" name="part_id" class="form-control" required>
                <option value="">Select a part...</option>
                <?php foreach ($parts as $p): ?>
                <option value="<?= $p['id'] ?>" data-stock="<?= (int)($p['total_stock'] ?? 0) ?>">
                    <?= htmlspecialchars($p['anchor_slug']) ?> - <?= htmlspecialchars($p['name']) ?> (<?= (int)($p['total_stock'] ?? 0) ?> in stock)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="technician_id">Technician *</label>
            <select id="technician_id" name="technician_id" class="form-control" required>
                <option value="">Select technician...</option>
                <?php foreach ($technicians as $tech): ?>
                <option value="<?= $tech['id'] ?>"><?= htmlspecialchars($tech['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="location_id">From Location *</label>
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
            <label for="work_order">Work Order #</label>
            <input type="text" id="work_order" name="work_order" class="form-control" placeholder="e.g., WO-12345">
        </div>

        <div class="form-group">
            <label for="unit_number">Unit/Vehicle #</label>
            <input type="text" id="unit_number" name="unit_number" class="form-control" placeholder="e.g., UNIT-101">
        </div>

        <div class="form-group wide">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-control" rows="2" placeholder="Additional checkout notes..."></textarea>
        </div>
    </div>

    <div class="form-hint">
        Enter a work order number to link this checkout to a specific job. Parts checked out will be tracked for returns and usage reporting.
    </div>

    <div style="margin-top: 1.5rem;">
        <button type="submit" class="btn btn-primary">Checkout Part</button>
    </div>
</form>
