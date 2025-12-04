<div class="page-header">
    <div>
        <p class="eyebrow">Parts</p>
        <h1><?= $part ? 'Edit Part' : 'New Part' ?></h1>
        <p class="lede"><?= $part ? 'Update part information' : 'Add a new part to the inventory' ?></p>
    </div>
    <div class="actions">
        <a href="<?= $part ? '/parts/' . $part['id'] : '/parts' ?>" class="btn btn-ghost">Cancel</a>
    </div>
</div>

<form method="POST" action="<?= $part ? '/parts/' . $part['id'] : '/parts' ?>">
    <?= $csrf ?>

    <div class="form-grid">
        <?php if (!$part): ?>
        <div class="form-group">
            <label for="anchor_slug">Anchor Slug *</label>
            <input type="text" id="anchor_slug" name="anchor_slug" class="form-control" 
                   placeholder="e.g., brake-kit-001" required pattern="[a-z0-9]+(-[a-z0-9]+)*">
            <small style="color: var(--muted);">Lowercase letters, numbers, and hyphens only</small>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="name">Part Name *</label>
            <input type="text" id="name" name="name" class="form-control" 
                   value="<?= htmlspecialchars($part['name'] ?? '') ?>" required>
        </div>

        <?php if (!$part): ?>
        <div class="form-group">
            <label for="part_number">Primary Part Number *</label>
            <input type="text" id="part_number" name="part_number" class="form-control" 
                   placeholder="e.g., OEM-9981" required>
        </div>

        <div class="form-group">
            <label for="manufacturer">Manufacturer</label>
            <input type="text" id="manufacturer" name="manufacturer" class="form-control" 
                   placeholder="e.g., OEMCo">
        </div>
        <?php endif; ?>

        <div class="form-group wide">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="2"><?= htmlspecialchars($part['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group wide">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-control" rows="2" 
                      placeholder="Stock handling, compatibility notes..."><?= htmlspecialchars($part['notes'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="form-hint">
        Required fields are marked with *. After creating a part, you can add more part numbers and supplier pricing.
    </div>

    <div style="margin-top: 1.5rem;">
        <button type="submit" class="btn btn-primary"><?= $part ? 'Save Changes' : 'Create Part' ?></button>
    </div>
</form>

<?php if ($part): ?>
<div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border);">
    <h3 style="color: var(--danger); margin-bottom: 1rem;">Danger Zone</h3>
    <form method="POST" action="/parts/<?= $part['id'] ?>/delete" 
          onsubmit="return confirm('Are you sure you want to deactivate this part?');">
        <?= $csrf ?>
        <button type="submit" class="btn btn-danger">Deactivate Part</button>
    </form>
</div>
<?php endif; ?>
