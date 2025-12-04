<div class="page-header">
    <div>
        <p class="eyebrow">Locations</p>
        <h1><?= $location ? 'Edit Location' : 'New Location' ?></h1>
    </div>
    <div class="actions">
        <a href="/locations" class="btn btn-ghost">Cancel</a>
    </div>
</div>

<form method="POST" action="<?= $location ? '/locations/' . $location['id'] : '/locations' ?>">
    <?= $csrf ?>

    <div class="form-grid">
        <div class="form-group">
            <label for="aisle">Aisle *</label>
            <input type="text" id="aisle" name="aisle" class="form-control" 
                   value="<?= htmlspecialchars($location['aisle'] ?? '') ?>" required placeholder="e.g., A1">
        </div>

        <div class="form-group">
            <label for="shelf">Shelf *</label>
            <input type="text" id="shelf" name="shelf" class="form-control" 
                   value="<?= htmlspecialchars($location['shelf'] ?? '') ?>" required placeholder="e.g., S1">
        </div>

        <div class="form-group">
            <label for="bay">Bay *</label>
            <input type="text" id="bay" name="bay" class="form-control" 
                   value="<?= htmlspecialchars($location['bay'] ?? '') ?>" required placeholder="e.g., B1">
        </div>

        <div class="form-group">
            <label for="bin">Bin (optional)</label>
            <input type="text" id="bin" name="bin" class="form-control" 
                   value="<?= htmlspecialchars($location['bin'] ?? '') ?>" placeholder="e.g., BIN-1">
        </div>
    </div>

    <div style="margin-top: 1.5rem;">
        <button type="submit" class="btn btn-primary"><?= $location ? 'Save Changes' : 'Create Location' ?></button>
    </div>
</form>
