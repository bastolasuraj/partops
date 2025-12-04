<div class="page-header">
    <div>
        <p class="eyebrow">Administration</p>
        <h1>New Admin User</h1>
        <p class="lede">Create a local admin account.</p>
    </div>
    <div class="actions">
        <a href="/admin" class="btn btn-ghost">Cancel</a>
    </div>
</div>

<form method="POST" action="/admin/users" style="max-width: 500px;">
    <?= $csrf ?>

    <div class="form-group">
        <label for="username">Username *</label>
        <input type="text" id="username" name="username" class="form-control" required>
    </div>

    <div class="form-group">
        <label for="email">Email *</label>
        <input type="email" id="email" name="email" class="form-control" required>
    </div>

    <div class="form-group">
        <label for="password">Password *</label>
        <input type="password" id="password" name="password" class="form-control" required minlength="8">
        <small style="color: var(--muted);">Minimum 8 characters</small>
    </div>

    <div class="form-hint">
        This will create a local admin account. Admins have full access to the system including user management.
    </div>

    <div style="margin-top: 1.5rem;">
        <button type="submit" class="btn btn-primary">Create Admin User</button>
    </div>
</form>
