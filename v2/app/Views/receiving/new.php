<?php
/**
 * Receiving - New Parts View
 * This file should be moved to: app/Views/receiving/new.php
 */
$title = 'Receive New Parts - PartOps';
?>

<div class="receiving-form">
    <header class="section-head">
        <div>
            <p class="eyebrow">Workflow</p>
            <h1>Receive New Parts</h1>
            <p class="lede">Log new parts from supplier with price, core charge, and quantity.</p>
        </div>
    </header>

    <form method="POST" action="/receiving/new" class="form-panel">
        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="idempotency_key" value="<?= $idempotencyKey ?? '' ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="part_id">Part *</label>
                <select name="part_id" id="part_id" required class="part-select">
                    <option value="">Select or search part...</option>
                    <?php foreach ($parts ?? [] as $part): ?>
                    <option value="<?= $part['id'] ?>" 
                            data-anchor="<?= htmlspecialchars($part['anchor_slug']) ?>">
                        <?= htmlspecialchars($part['anchor_slug'] . ' - ' . $part['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errors['part_id'] ?? null): ?>
                    <span class="error"><?= $errors['part_id'][0] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="part_number_id">Part Number</label>
                <select name="part_number_id" id="part_number_id">
                    <option value="">Select part first...</option>
                </select>
            </div>

            <div class="form-group">
                <label for="supplier_id">Supplier *</label>
                <select name="supplier_id" id="supplier_id" required>
                    <option value="">Select supplier...</option>
                    <?php foreach ($suppliers ?? [] as $supplier): ?>
                    <option value="<?= $supplier['id'] ?>"
                            <?= ($supplier['is_preferred'] ?? false) ? 'class="preferred"' : '' ?>>
                        <?= htmlspecialchars($supplier['name']) ?>
                        <?= ($supplier['is_preferred'] ?? false) ? '★' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errors['supplier_id'] ?? null): ?>
                    <span class="error"><?= $errors['supplier_id'][0] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="supplier_sku">Supplier SKU</label>
                <input type="text" name="supplier_sku" id="supplier_sku" 
                       value="<?= htmlspecialchars($old['supplier_sku'] ?? '') ?>"
                       placeholder="Supplier's part number">
            </div>

            <div class="form-group">
                <label for="location_id">Location *</label>
                <select name="location_id" id="location_id" required>
                    <option value="">Select location...</option>
                    <?php foreach ($locations ?? [] as $location): ?>
                    <option value="<?= $location['id'] ?>">
                        <?= htmlspecialchars($location['aisle'] . ' / ' . $location['shelf'] . ' / ' . $location['bay']) ?>
                        <?= $location['bin'] ? ' / ' . htmlspecialchars($location['bin']) : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errors['location_id'] ?? null): ?>
                    <span class="error"><?= $errors['location_id'][0] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="qty">Quantity *</label>
                <input type="number" name="qty" id="qty" min="1" required
                       value="<?= htmlspecialchars($old['qty'] ?? '1') ?>">
                <?php if ($errors['qty'] ?? null): ?>
                    <span class="error"><?= $errors['qty'][0] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="price">Unit Price ($)</label>
                <input type="number" name="price" id="price" step="0.01" min="0"
                       value="<?= htmlspecialchars($old['price'] ?? '') ?>"
                       placeholder="0.00">
            </div>

            <div class="form-group">
                <label for="core_charge">Core Charge ($)</label>
                <input type="number" name="core_charge" id="core_charge" step="0.01" min="0"
                       value="<?= htmlspecialchars($old['core_charge'] ?? '') ?>"
                       placeholder="0.00">
            </div>

            <div class="form-group">
                <label for="expected_rebate">Expected Rebate ($)</label>
                <input type="number" name="expected_rebate" id="expected_rebate" step="0.01" min="0"
                       value="<?= htmlspecialchars($old['expected_rebate'] ?? '') ?>"
                       placeholder="0.00">
            </div>

            <div class="form-group">
                <label for="po_number">PO Number</label>
                <input type="text" name="po_number" id="po_number"
                       value="<?= htmlspecialchars($old['po_number'] ?? '') ?>"
                       placeholder="Purchase order reference">
            </div>

            <div class="form-group wide">
                <label for="notes">Notes</label>
                <textarea name="notes" id="notes" rows="3"
                          placeholder="Packaging, damage, lot number, etc."><?= htmlspecialchars($old['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-hint">
            🔒 CSRF protection enabled · Transactions use row locks · Price/core snapshotted at movement time
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Receive Parts</button>
            <a href="/receiving" class="btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<script>
// Load part numbers when part is selected
document.getElementById('part_id').addEventListener('change', function() {
    const partId = this.value;
    const partNumberSelect = document.getElementById('part_number_id');
    
    if (!partId) {
        partNumberSelect.innerHTML = '<option value="">Select part first...</option>';
        return;
    }
    
    fetch(`/api/parts/${partId}/numbers`)
        .then(r => r.json())
        .then(data => {
            let options = '<option value="">Select part number...</option>';
            data.forEach(pn => {
                options += `<option value="${pn.id}">${pn.number} (${pn.type}) - ${pn.manufacturer || 'N/A'}</option>`;
            });
            partNumberSelect.innerHTML = options;
        });
});
</script>
