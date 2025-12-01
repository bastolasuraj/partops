<?php ob_start(); ?>

<h1>Edit Location: <?= htmlspecialchars($location['aisle'] . '-' . $location['shelf'] . '-' . $location['bay'] . ($location['bin'] ? '-' . $location['bin'] : '')) ?></h1>

<div style="margin-bottom: 1rem;">
    <a href="<?= url('/locations') ?>" class="btn btn-secondary">&larr; Back to Locations</a>
</div>

<div style="background: rgba(255, 255, 255, 0.05); padding: 2rem; border-radius: 8px;">
    <form action="<?= url('/locations/' . $location['id']) ?>" method="POST">
        <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token'] ?? '' ?>">
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div class="form-group">
                <label for="aisle">Aisle <span style="color: red;">*</span></label>
                <input type="text" id="aisle" name="aisle" value="<?= htmlspecialchars($location['aisle']) ?>" required>
            </div>
            <div class="form-group">
                <label for="shelf">Shelf <span style="color: red;">*</span></label>
                <input type="text" id="shelf" name="shelf" value="<?= htmlspecialchars($location['shelf']) ?>" required>
            </div>
            <div class="form-group">
                <label for="bay">Bay <span style="color: red;">*</span></label>
                <input type="text" id="bay" name="bay" value="<?= htmlspecialchars($location['bay']) ?>" required>
            </div>
            <div class="form-group">
                <label for="bin">Bin</label>
                <input type="text" id="bin" name="bin" value="<?= htmlspecialchars($location['bin'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?= $location['is_active'] ? 'checked' : '' ?> style="width: auto;"> Active
            </label>
        </div>
        
        <div style="margin-top: 1rem;">
            <button type="submit" class="btn">Update Location</button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
