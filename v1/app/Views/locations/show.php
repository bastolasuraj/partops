<?php ob_start(); ?>

<div style="margin-bottom: 1rem;">
    <a href="<?= url('/locations') ?>" class="btn btn-secondary">&larr; Back to Locations</a>
</div>

<div style="background: rgba(255, 255, 255, 0.05); padding: 2rem; border-radius: 8px;">
    <div style="display: flex; justify-content: space-between; align-items: start;">
        <div>
            <h1 style="margin-bottom: 0.5rem;">Location Details</h1>
            <p style="font-family: monospace; color: rgb(188, 59, 40); font-size: 2rem; font-weight: bold;">
                <?= htmlspecialchars($location['aisle'] . '-' . $location['shelf'] . '-' . $location['bay'] . ($location['bin'] ? '-' . $location['bin'] : '')) ?>
            </p>
        </div>
        <div>
            <a href="<?= url('/locations/' . $location['id'] . '/edit') ?>" class="btn">Edit Location</a>
        </div>
    </div>

    <div style="margin-top: 2rem; display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
        <div style="background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 4px; text-align: center;">
            <div style="font-size: 0.8rem; color: #aaa;">AISLE</div>
            <div style="font-size: 1.5rem;"><?= htmlspecialchars($location['aisle']) ?></div>
        </div>
        <div style="background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 4px; text-align: center;">
            <div style="font-size: 0.8rem; color: #aaa;">SHELF</div>
            <div style="font-size: 1.5rem;"><?= htmlspecialchars($location['shelf']) ?></div>
        </div>
        <div style="background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 4px; text-align: center;">
            <div style="font-size: 0.8rem; color: #aaa;">BAY</div>
            <div style="font-size: 1.5rem;"><?= htmlspecialchars($location['bay']) ?></div>
        </div>
        <div style="background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 4px; text-align: center;">
            <div style="font-size: 0.8rem; color: #aaa;">BIN</div>
            <div style="font-size: 1.5rem;"><?= htmlspecialchars($location['bin'] ?? '-') ?></div>
        </div>
    </div>
    
    <div style="margin-top: 2rem;">
        <h3>Status</h3>
        <p style="margin-top: 0.5rem;">
            <span style="
                padding: 4px 8px; 
                border-radius: 4px; 
                background: <?= $location['is_active'] ? 'rgba(40, 167, 69, 0.2)' : 'rgba(220, 53, 69, 0.2)' ?>;
                color: <?= $location['is_active'] ? '#28a745' : '#dc3545' ?>;
            ">
                <?= $location['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
        </p>
    </div>
</div>

<div style="margin-top: 2rem;">
    <h2>Current Inventory</h2>
    <p>No inventory items found in this location.</p>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
