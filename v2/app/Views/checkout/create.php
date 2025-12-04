<?php
/**
 * Checkout Parts View
 * This file should be moved to: app/Views/checkout/create.php
 */
$title = 'Checkout Parts - PartOps';
?>

<div class="checkout-form">
    <header class="section-head">
        <div>
            <p class="eyebrow">Workflow</p>
            <h1>Checkout to Work Order</h1>
            <p class="lede">Assign parts to a work order and technician.</p>
        </div>
    </header>

    <form method="POST" action="/checkout" class="form-panel">
        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="idempotency_key" value="<?= $idempotencyKey ?? '' ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="work_order_id">Work Order *</label>
                <select name="work_order_id" id="work_order_id" required>
                    <option value="">Select or create work order...</option>
                    <?php foreach ($workOrders ?? [] as $wo): ?>
                    <option value="<?= $wo['id'] ?>"
                            data-technician="<?= $wo['technician_id'] ?? '' ?>">
                        <?= htmlspecialchars($wo['wo_number']) ?>
                        <?= $wo['unit_number'] ? ' - ' . htmlspecialchars($wo['unit_number']) : '' ?>
                        (<?= ucfirst($wo['status']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <small><a href="/work-orders/create" target="_blank">Create new work order</a></small>
                <?php if ($errors['work_order_id'] ?? null): ?>
                    <span class="error"><?= $errors['work_order_id'][0] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="technician_id">Technician *</label>
                <select name="technician_id" id="technician_id" required>
                    <option value="">Select technician...</option>
                    <?php foreach ($technicians ?? [] as $tech): ?>
                    <option value="<?= $tech['id'] ?>">
                        <?= htmlspecialchars($tech['name']) ?>
                        <?= $tech['employee_id'] ? ' (' . htmlspecialchars($tech['employee_id']) . ')' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errors['technician_id'] ?? null): ?>
                    <span class="error"><?= $errors['technician_id'][0] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="part_id">Part *</label>
                <select name="part_id" id="part_id" required>
                    <option value="">Search or select part...</option>
                    <?php foreach ($parts ?? [] as $part): ?>
                    <option value="<?= $part['id'] ?>"
                            data-stock="<?= ($part['total_on_hand'] ?? 0) - ($part['total_reserved'] ?? 0) ?>">
                        <?= htmlspecialchars($part['anchor_slug']) ?> - <?= htmlspecialchars($part['name']) ?>
                        (<?= ($part['total_on_hand'] ?? 0) - ($part['total_reserved'] ?? 0) ?> available)
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errors['part_id'] ?? null): ?>
                    <span class="error"><?= $errors['part_id'][0] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="location_id">From Location *</label>
                <select name="location_id" id="location_id" required>
                    <option value="">Select part first...</option>
                </select>
                <?php if ($errors['location_id'] ?? null): ?>
                    <span class="error"><?= $errors['location_id'][0] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="qty">Quantity *</label>
                <input type="number" name="qty" id="qty" min="1" required
                       value="<?= htmlspecialchars($old['qty'] ?? '1') ?>">
                <small id="stock-info"></small>
                <?php if ($errors['qty'] ?? null): ?>
                    <span class="error"><?= $errors['qty'][0] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group wide">
                <label for="notes">Notes</label>
                <textarea name="notes" id="notes" rows="3"
                          placeholder="Installation notes, special handling..."><?= htmlspecialchars($old['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-hint">
            🔒 CSRF protection enabled · Row locks prevent race conditions · Idempotency prevents double-checkout
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Checkout Parts</button>
            <a href="/checkout" class="btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<script>
// Auto-select technician when work order is selected
document.getElementById('work_order_id').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const techId = option.dataset.technician;
    if (techId) {
        document.getElementById('technician_id').value = techId;
    }
});

// Load locations when part is selected
document.getElementById('part_id').addEventListener('change', function() {
    const partId = this.value;
    const locationSelect = document.getElementById('location_id');
    const stockInfo = document.getElementById('stock-info');
    
    if (!partId) {
        locationSelect.innerHTML = '<option value="">Select part first...</option>';
        stockInfo.textContent = '';
        return;
    }
    
    fetch(`/api/parts/${partId}/locations`)
        .then(r => r.json())
        .then(data => {
            let options = '<option value="">Select location...</option>';
            data.forEach(loc => {
                const available = loc.on_hand - loc.reserved;
                options += `<option value="${loc.location_id}">${loc.aisle}/${loc.shelf}/${loc.bay} (${available} available)</option>`;
            });
            locationSelect.innerHTML = options;
        });
    
    const option = this.options[this.selectedIndex];
    stockInfo.textContent = `${option.dataset.stock} total available`;
});
</script>
