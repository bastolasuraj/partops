<div class="page-header">
    <div>
        <p class="eyebrow">Administration</p>
        <h1>User Management</h1>
        <p class="lede">Manage admin users (database-stored accounts).</p>
    </div>
    <div class="actions">
        <a href="/admin/users/new" class="btn btn-primary">Add Admin User</a>
    </div>
</div>

<div class="form-hint" style="margin-bottom: 1.5rem;">
    <strong>Authentication Note:</strong> Regular users authenticate via LDAP. Admin users are stored locally in the database with hashed passwords.
</div>

<?php if (!empty($users)): ?>
<table class="grid">
    <thead>
        <tr>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Auth Source</th>
            <th>Last Login</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td style="font-weight: 600;"><?= htmlspecialchars($u['username']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
                <span class="badge <?= $u['role'] === 'admin' ? 'badge-warning' : 'badge-info' ?>">
                    <?= ucfirst($u['role']) ?>
                </span>
            </td>
            <td><?= ucfirst($u['auth_source']) ?></td>
            <td><?= $u['last_login_at'] ? date('M j, g:ia', strtotime($u['last_login_at'])) : 'Never' ?></td>
            <td>
                <span class="badge <?= $u['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                    <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
            </td>
            <td>
                <?php if ($u['id'] !== ($user['id'] ?? null)): ?>
                <form method="POST" action="/admin/users/<?= $u['id'] ?>/toggle" style="display: inline;">
                    <?= $csrf ?>
                    <button type="submit" class="btn btn-ghost btn-sm">
                        <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                    </button>
                </form>
                <?php else: ?>
                <span style="color: var(--muted); font-size: 0.85rem;">(You)</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="empty-state">
    <h3>No users</h3>
    <p>Create an admin user to get started.</p>
</div>
<?php endif; ?>
