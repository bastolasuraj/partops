<?php ob_start(); ?>

<h1>Add New Location</h1>

<div style="margin-bottom: 1rem;">
    <a href="<?= url('/locations') ?>" class="btn btn-secondary">&larr; Back to Locations</a>
</div>

<div style="background: rgba(255, 255, 255, 0.05); padding: 2rem; border-radius: 8px;">
    <form action="<?= url('/locations') ?>" method="POST">
        <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token'] ?? '' ?>">
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div class="form-group">
                <label for="aisle">Aisle <span style="color: red;">*</span></label>
                <input type="text" id="aisle" name="aisle" required placeholder="e.g. A1">
            </div>
            <div class="form-group">
                <label for="shelf">Shelf <span style="color: red;">*</span></label>
                <input type="text" id="shelf" name="shelf" required placeholder="e.g. S1">
            </div>
            <div class="form-group">
                <label for="bay">Bay <span style="color: red;">*</span></label>
                <input type="text" id="bay" name="bay" required placeholder="e.g. B1">
            </div>
            <div class="form-group">
                <label for="bin">Bin</label>
                <input type="text" id="bin" name="bin" placeholder="e.g. 01">
            </div>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" checked style="width: auto;"> Active
            </label>
        </div>
        
        <div style="margin-top: 1rem;">
            <button type="submit" class="btn">Create Location</button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
