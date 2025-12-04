<div class="page-header">
    <div>
        <p class="eyebrow">Suppliers</p>
        <h1><?= $supplier ? 'Edit Supplier' : 'New Supplier' ?></h1>
    </div>
    <div class="actions">
        <a href="/suppliers" class="btn btn-ghost">Cancel</a>
    </div>
</div>

<form method="POST" action="<?= $supplier ? '/suppliers/' . $supplier['id'] : '/suppliers' ?>">
    <?= $csrf ?>

    <div class="form-grid">
        <div class="form-group">
            <label for="name">Supplier Name *</label>
            <input type="text" id="name" name="name" class="form-control" 
                   value="<?= htmlspecialchars($supplier['name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="contact_email">Contact Email</label>
            <input type="email" id="contact_email" name="contact_email" class="form-control" 
                   value="<?= htmlspecialchars($supplier['contact_email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="contact_phone">Contact Phone</label>
            <input type="text" id="contact_phone" name="contact_phone" class="form-control" 
                   value="<?= htmlspecialchars($supplier['contact_phone'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="reorder_url">Reorder Portal URL</label>
            <input type="url" id="reorder_url" name="reorder_url" class="form-control" 
                   value="<?= htmlspecialchars($supplier['reorder_url'] ?? '') ?>" placeholder="https://...">
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="checkbox" name="is_preferred" value="1" <?= ($supplier['is_preferred'] ?? false) ? 'checked' : '' ?>>
                Preferred Supplier
            </label>
        </div>
    </div>

    <div style="margin-top: 1.5rem;">
        <button type="submit" class="btn btn-primary"><?= $supplier ? 'Save Changes' : 'Create Supplier' ?></button>
    </div>
</form>

<?php if ($supplier): ?>
<div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border);">
    <form method="POST" action="/suppliers/<?= $supplier['id'] ?>/delete" 
          onsubmit="return confirm('Deactivate this supplier?');">
        <?= $csrf ?>
        <button type="submit" class="btn btn-danger">Deactivate Supplier</button>
    </form>
</div>
<?php endif; ?>
