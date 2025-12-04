<?php
/**
 * Core Return View
 * This file should be moved to: app/Views/returns/core.php
 */
$title = 'Core Return - PartOps';
?>

<div class="return-form">
    <header class="section-head">
        <div>
            <p class="eyebrow">Workflow</p>
            <h1>Core Return</h1>
            <p class="lede">Return used parts with core charge for rebate.</p>
        </div>
    </header>

    <form method="POST" action="/returns/core" class="form-panel">
        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="idempotency_key" value="<?= $idempotencyKey ?? '' ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="part_id">Part *</label>
                <select name="part_id" id="part_id" required>
                    <option value="">Select part...</option>
                    <?php foreach ($parts ?? [] as $part): ?>
                    <option value="<?= $part['id'] ?>">
                        <?= htmlspecialchars($part['anchor_slug'] . ' - ' . $part['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errors['part_id'] ?? null): ?>
                    <span class="error"><?= $errors['part_id'][0] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="supplier_id">Supplier *</label>
                <select name="supplier_id" id="supplier_id" required>
                    <option value="">Select supplier...</option>
                    <?php foreach ($suppliers ?? [] as $supplier): ?>
                    <option value="<?= $supplier['id'] ?>">
                        <?= htmlspecialchars($supplier['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errors['supplier_id'] ?? null): ?>
                    <span class="error"><?= $errors['supplier_id'][0] ?></span>
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
                <label for="core_charge">Core Charge (per unit) *</label>
                <input type="number" name="core_charge" id="core_charge" step="0.01" min="0" required
                       value="<?= htmlspecialchars($old['core_charge'] ?? '') ?>"
                       placeholder="0.00">
                <?php if ($errors['core_charge'] ?? null): ?>
                    <span class="error"><?= $errors['core_charge'][0] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="expected_rebate">Expected Rebate (per unit) *</label>
                <input type="number" name="expected_rebate" id="expected_rebate" step="0.01" min="0" required
                       value="<?= htmlspecialchars($old['expected_rebate'] ?? '') ?>"
                       placeholder="0.00">
                <?php if ($errors['expected_rebate'] ?? null): ?>
                    <span class="error"><?= $errors['expected_rebate'][0] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="due_date">Core Due Date</label>
                <input type="date" name="due_date" id="due_date"
                       value="<?= htmlspecialchars($old['due_date'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="core_due_state">Status</label>
                <select name="core_due_state" id="core_due_state">
                    <option value="due">Due - Pending Return</option>
                    <option value="sent">Sent - Awaiting Rebate</option>
                    <option value="rebated">Rebated - Complete</option>
                </select>
            </div>

            <div class="form-group">
                <label for="rma_number">RMA Number</label>
                <input type="text" name="rma_number" id="rma_number"
                       value="<?= htmlspecialchars($old['rma_number'] ?? '') ?>"
                       placeholder="Return authorization number">
            </div>

            <div class="form-group wide">
                <label for="notes">Notes</label>
                <textarea name="notes" id="notes" rows="3"
                          placeholder="Condition, reason, tracking info..."><?= htmlspecialchars($old['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="core-summary" id="core-summary">
            <h3>Core Return Summary</h3>
            <div class="summary-row">
                <span>Total Core Charge:</span>
                <span id="total-core-charge">$0.00</span>
            </div>
            <div class="summary-row">
                <span>Expected Rebate:</span>
                <span id="total-rebate">$0.00</span>
            </div>
        </div>

        <div class="form-hint">
            🔒 Core liability tracked · Rebate aging monitored · Full audit trail maintained
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Record Core Return</button>
            <a href="/returns" class="btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<script>
function updateSummary() {
    const qty = parseInt(document.getElementById('qty').value) || 0;
    const coreCharge = parseFloat(document.getElementById('core_charge').value) || 0;
    const expectedRebate = parseFloat(document.getElementById('expected_rebate').value) || 0;
    
    document.getElementById('total-core-charge').textContent = '$' + (qty * coreCharge).toFixed(2);
    document.getElementById('total-rebate').textContent = '$' + (qty * expectedRebate).toFixed(2);
}

document.getElementById('qty').addEventListener('input', updateSummary);
document.getElementById('core_charge').addEventListener('input', updateSummary);
document.getElementById('expected_rebate').addEventListener('input', updateSummary);
</script>
