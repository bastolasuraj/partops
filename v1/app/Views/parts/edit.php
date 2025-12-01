<?php ob_start(); ?>

<h1>Edit Part: <?= htmlspecialchars($part['name']) ?></h1>

<div style="margin-bottom: 1rem;">
    <a href="<?= url('/parts') ?>" class="btn btn-secondary">&larr; Back to Parts</a>
</div>

<div style="background: rgba(255, 255, 255, 0.05); padding: 2rem; border-radius: 8px;">
    <form action="<?= url('/parts/' . $part['id']) ?>" method="POST">
        <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token'] ?? '' ?>">
        
        <div class="form-group">
            <label for="name">Part Name <span style="color: red;">*</span></label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($part['name']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="anchor_slug">Anchor Slug <span style="color: red;">*</span></label>
            <input type="text" id="anchor_slug" name="anchor_slug" value="<?= htmlspecialchars($part['anchor_slug']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3"><?= htmlspecialchars($part['description'] ?? '') ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="2"><?= htmlspecialchars($part['notes'] ?? '') ?></textarea>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?= $part['is_active'] ? 'checked' : '' ?> style="width: auto;"> Active
            </label>
        </div>
        
        <div style="margin-top: 1rem;">
            <button type="submit" class="btn">Update Part</button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
