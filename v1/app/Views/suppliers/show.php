<?php ob_start(); ?>

<div style="margin-bottom: 1rem;">
    <a href="<?= url('/suppliers') ?>" class="btn btn-secondary">&larr; Back to Suppliers</a>
</div>

<div style="background: rgba(255, 255, 255, 0.05); padding: 2rem; border-radius: 8px;">
    <div style="display: flex; justify-content: space-between; align-items: start;">
        <div>
            <h1 style="margin-bottom: 0.5rem;">
                <?= htmlspecialchars($supplier['name']) ?>
                <?php if ($supplier['is_preferred']): ?>
                    <span style="font-size: 0.5em; background: rgb(255, 222, 63); color: rgb(35, 31, 32); padding: 2px 6px; border-radius: 4px; vertical-align: middle;">Preferred</span>
                <?php endif; ?>
            </h1>
        </div>
        <div>
            <a href="<?= url('/suppliers/' . $supplier['id'] . '/edit') ?>" class="btn">Edit Supplier</a>
        </div>
    </div>

    <div style="margin-top: 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div>
            <h3>Contact Info</h3>
            <p style="margin-top: 0.5rem; color: #ccc;">
                <strong>Contact:</strong> <?= htmlspecialchars($supplier['contact_name'] ?? '-') ?><br>
                <strong>Email:</strong> <?= htmlspecialchars($supplier['email'] ?? '-') ?><br>
                <strong>Phone:</strong> <?= htmlspecialchars($supplier['phone'] ?? '-') ?>
            </p>
        </div>
        <div>
            <h3>Ordering</h3>
            <p style="margin-top: 0.5rem; color: #ccc;">
                <strong>Address:</strong><br>
                <?= nl2br(htmlspecialchars($supplier['address'] ?? '-')) ?>
            </p>
            <?php if (!empty($supplier['reorder_url'])): ?>
                <p style="margin-top: 0.5rem;">
                    <a href="<?= htmlspecialchars($supplier['reorder_url']) ?>" target="_blank" style="color: rgb(188, 59, 40);">Visit Order Page &rarr;</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="margin-top: 2rem;">
        <h3>Status</h3>
        <p style="margin-top: 0.5rem;">
            <span style="
                padding: 4px 8px; 
                border-radius: 4px; 
                background: <?= $supplier['is_active'] ? 'rgba(40, 167, 69, 0.2)' : 'rgba(220, 53, 69, 0.2)' ?>;
                color: <?= $supplier['is_active'] ? '#28a745' : '#dc3545' ?>;
            ">
                <?= $supplier['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
        </p>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
