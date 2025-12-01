<?php ob_start(); ?>

<div style="margin-bottom: 1rem;">
    <a href="<?= url('/parts') ?>" class="btn btn-secondary">&larr; Back to Parts</a>
</div>

<div style="background: rgba(255, 255, 255, 0.05); padding: 2rem; border-radius: 8px;">
    <div style="display: flex; justify-content: space-between; align-items: start;">
        <div>
            <h1 style="margin-bottom: 0.5rem;"><?= htmlspecialchars($part['name']) ?></h1>
            <p style="font-family: monospace; color: rgb(188, 59, 40); font-size: 1.2rem;">
                <?= htmlspecialchars($part['anchor_slug']) ?>
            </p>
        </div>
        <div>
            <a href="<?= url('/parts/' . $part['id'] . '/edit') ?>" class="btn">Edit Part</a>
        </div>
    </div>

    <div style="margin-top: 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div>
            <h3>Description</h3>
            <p style="margin-top: 0.5rem; color: #ccc;">
                <?= nl2br(htmlspecialchars($part['description'] ?? 'No description provided.')) ?>
            </p>
        </div>
        <div>
            <h3>Notes</h3>
            <p style="margin-top: 0.5rem; color: #ccc;">
                <?= nl2br(htmlspecialchars($part['notes'] ?? 'No notes.')) ?>
            </p>
        </div>
    </div>
    
    <div style="margin-top: 2rem;">
        <h3>Status</h3>
        <p style="margin-top: 0.5rem;">
            <span style="
                padding: 4px 8px; 
                border-radius: 4px; 
                background: <?= $part['is_active'] ? 'rgba(40, 167, 69, 0.2)' : 'rgba(220, 53, 69, 0.2)' ?>;
                color: <?= $part['is_active'] ? '#28a745' : '#dc3545' ?>;
            ">
                <?= $part['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
        </p>
    </div>
</div>

<?php if (!empty($part['numbers'])): ?>
<div style="margin-top: 2rem;">
    <h2>Part Numbers</h2>
    <table>
        <thead>
            <tr>
                <th>Number</th>
                <th>Type</th>
                <th>Manufacturer</th>
                <th>Primary</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($part['numbers'] as $number): ?>
            <tr>
                <td><?= htmlspecialchars($number['value']) ?></td>
                <td><?= ucfirst($number['type']) ?></td>
                <td><?= htmlspecialchars($number['manufacturer'] ?? '-') ?></td>
                <td><?= $number['is_primary'] ? '✓' : '' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
