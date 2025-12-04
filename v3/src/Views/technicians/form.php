<div class="page-header">
    <div>
        <p class="eyebrow">Technicians</p>
        <h1><?= $technician ? 'Edit Technician' : 'New Technician' ?></h1>
    </div>
    <div class="actions">
        <a href="/technicians" class="btn btn-ghost">Cancel</a>
    </div>
</div>

<form method="POST" action="<?= $technician ? '/technicians/' . $technician['id'] : '/technicians' ?>">
    <?= $csrf ?>

    <div class="form-grid">
        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" class="form-control" 
                   value="<?= htmlspecialchars($technician['name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" 
                   value="<?= htmlspecialchars($technician['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" class="form-control" 
                   value="<?= htmlspecialchars($technician['phone'] ?? '') ?>">
        </div>
    </div>

    <div style="margin-top: 1.5rem;">
        <button type="submit" class="btn btn-primary"><?= $technician ? 'Save Changes' : 'Create Technician' ?></button>
    </div>
</form>

<?php if ($technician): ?>
<div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border);">
    <form method="POST" action="/technicians/<?= $technician['id'] ?>/delete" 
          onsubmit="return confirm('Deactivate this technician?');">
        <?= $csrf ?>
        <button type="submit" class="btn btn-danger">Deactivate Technician</button>
    </form>
</div>
<?php endif; ?>
