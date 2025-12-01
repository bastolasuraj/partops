<?php ob_start(); ?>

<h1>Add New Part</h1>

<div style="margin-bottom: 1rem;">
    <a href="<?= url('/parts') ?>" class="btn btn-secondary">&larr; Back to Parts</a>
</div>

<div style="background: rgba(255, 255, 255, 0.05); padding: 2rem; border-radius: 8px;">
    <form action="<?= url('/parts') ?>" method="POST">
        <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token'] ?? '' ?>">
        
        <div class="form-group">
            <label for="name">Part Name <span style="color: red;">*</span></label>
            <input type="text" id="name" name="name" required placeholder="e.g. Oil Filter">
        </div>
        
        <div class="form-group">
            <label for="anchor_slug">Anchor Slug (Unique ID) <span style="color: red;">*</span></label>
            <input type="text" id="anchor_slug" name="anchor_slug" required placeholder="e.g. FIL-OIL-001">
            <small style="color: #aaa;">Must be unique. Used for barcodes and permanent identification.</small>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3"></textarea>
        </div>
        
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="2"></textarea>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" checked style="width: auto;"> Active
            </label>
        </div>
        
        <div style="margin-top: 1rem;">
            <button type="submit" class="btn">Create Part</button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
