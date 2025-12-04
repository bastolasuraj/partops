<?php
/**
 * Receiving - Work Order Return View
 * This file should be moved to: app/Views/receiving/wo_return.php
 */
$title = 'Work Order Return - PartOps';
?>

<div class="receiving-form">
    <header class="section-head">
        <div>
            <p class="eyebrow">Workflow</p>
            <h1>Work Order Return</h1>
            <p class="lede">Return unused parts from a work order back to inventory.</p>
        </div>
    </header>

    <form method="POST" action="/receiving/wo-return" class="form-panel">
        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="idempotency_key" value="<?= $idempotencyKey ?? '' ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="work_order_id">Work Order *</label>
                <select name="work_order_id" id="work_order_id" required>
                    <option value="">Select work order...</option>
                    <?php foreach ($workOrders ?? [] as $wo): ?>
                    <option value="<?= $wo['id'] ?>">
                        <?= htmlspecialchars($wo['wo_number']) ?>
                        <?= $wo['unit_number'] ? ' - ' . htmlspecialchars($wo['unit_number']) : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errors['work_order_id'] ?? null): ?>
                    <span class="error"><?= $errors['work_order_id'][0] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="technician_id">Technician</label>
                <select name="technician_id" id="technician_id">
                    <option value="">Select technician...</option>
                    <?php foreach ($technicians ?? [] as $tech): ?>
                    <option value="<?= $tech['id'] ?>">
                        <?= htmlspecialchars($tech['name']) ?>
                        <?= $tech['employee_id'] ? ' (' . htmlspecialchars($tech['employee_id']) . ')' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

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
                <label for="location_id">Return to Location *</label>
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

            <div class="form-group wide">
                <label for="notes">Notes</label>
                <textarea name="notes" id="notes" rows="3"
                          placeholder="Reason for return, condition, etc."><?= htmlspecialchars($old['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-hint">
            🔒 Parts returned from work orders are added back to inventory with full audit trail.
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Return Parts</button>
            <a href="/receiving" class="btn-ghost">Cancel</a>
        </div>
    </form>
</div>
