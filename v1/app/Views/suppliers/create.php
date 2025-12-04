<?php ob_start(); ?>

<h1>Add New Supplier</h1>

<div style="margin-bottom: 1rem;">
    <a href="<?= url('/suppliers') ?>" class="btn btn-secondary">&larr; Back to Suppliers</a>
</div>

<div style="background: white; padding: 2rem; border-radius: 8px;">
    <form action="<?= url('/suppliers') ?>" method="POST">
        <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token'] ?? '' ?>">
        
        <div class="form-group">
            <label for="name">Supplier Name <span style="color: red;">*</span></label>
            <input type="text" id="name" name="name" required placeholder="e.g. AutoZone">
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="contact_name">Contact Name</label>
                <input type="text" id="contact_name" name="contact_name" placeholder="e.g. John Doe">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="e.g. sales@supplier.com">
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" placeholder="e.g. 555-0123">
            </div>
            <div class="form-group">
                <label for="reorder_url">Reorder URL</label>
                <input type="url" id="reorder_url" name="reorder_url" placeholder="https://...">
            </div>
        </div>
        
        <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address" rows="3"></textarea>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_preferred" value="1" style="width: auto;"> Preferred Supplier
            </label>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" checked style="width: auto;"> Active
            </label>
        </div>
        
        <div style="margin-top: 1rem;">
            <button type="submit" class="btn">Create Supplier</button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
