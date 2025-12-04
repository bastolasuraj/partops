<?php
/**
 * Standard Return View
 * This file should be moved to: app/Views/returns/standard.php
 */
$title = 'Standard Return - PartOps';
?>

<div class="return-form">
    <header class="section-head">
        <div>
            <p class="eyebrow">Workflow</p>
            <h1>Standard Return</h1>
            <p class="lede">Return new unused parts back to inventory.</p>
        </div>
    </header>

    <form method="POST" action="/returns/standard" class="form-panel">
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
                <label for="supplier_id">Return to Supplier</label>
                <select name="supplier_id" id="supplier_id">
                    <option value="">Select supplier (optional)...</option>
                    <?php foreach ($suppliers ?? [] as $supplier): ?>
                    <option value="<?= $supplier['id'] ?>">
                        <?= htmlspecialchars($supplier['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="location_id">From Location *</label>
                <select name="location_id" id="location_id" required>
                    <option value="">Select location...</option>
                    <?php foreach ($locations ?? [] as $location): ?>
                    <option value="<?= $location['id'] ?>">
                        <?= htmlspecialchars($location['aisle'] . ' / ' . $location['shelf'] . ' / ' . $location['bay']) ?>
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
                <label for="notes">Reason for Return *</label>
                <textarea name="notes" id="notes" rows="3" required
                          placeholder="Wrong part ordered, excess inventory, defective, etc."><?= htmlspecialchars($old['notes'] ?? '') ?></textarea>
                <?php if ($errors['notes'] ?? null): ?>
                    <span class="error"><?= $errors['notes'][0] ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-hint">
            🔒 Standard returns remove parts from inventory. For core returns with rebate tracking, use Core Return.
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Process Return</button>
            <a href="/returns" class="btn-ghost">Cancel</a>
        </div>
    </form>
</div>
