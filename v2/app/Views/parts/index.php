<?php
/**
 * Parts List View
 * This file should be moved to: app/Views/parts/index.php
 */
$title = 'Parts Catalog - PartOps';
?>

<div class="parts-list">
    <header class="section-head">
        <div>
            <p class="eyebrow">Inventory</p>
            <h1>Parts Catalog</h1>
            <p class="lede">Search by anchor, any part number (OEM/aftermarket/historical), or supplier.</p>
        </div>
        <div class="actions">
            <a href="/parts/create" class="btn-primary">New Part</a>
        </div>
    </header>

    <div class="filters">
        <form method="GET" action="/parts" class="filter-form">
            <input type="search" name="q" placeholder="Search parts..." 
                   value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" class="search-input">
            <select name="status">
                <option value="">All Status</option>
                <option value="1" <?= ($_GET['status'] ?? '') === '1' ? 'selected' : '' ?>>Active Only</option>
                <option value="0" <?= ($_GET['status'] ?? '') === '0' ? 'selected' : '' ?>>Inactive Only</option>
            </select>
            <select name="category">
                <option value="">All Categories</option>
                <?php foreach ($categories ?? [] as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" 
                        <?= ($_GET['category'] ?? '') === $cat ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-ghost">Filter</button>
        </form>
    </div>

    <?php if (!empty($parts)): ?>
    <table class="grid">
        <thead>
            <tr>
                <th>Anchor</th>
                <th>Part #</th>
                <th>Name</th>
                <th>Manufacturer</th>
                <th>On Hand</th>
                <th>Reserved</th>
                <th>Available</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($parts as $part): ?>
            <tr class="<?= !$part['is_active'] ? 'row-inactive' : '' ?>">
                <td>
                    <a href="/parts/<?= $part['id'] ?>" class="anchor-link">
                        <?= htmlspecialchars($part['anchor_slug']) ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($part['primary_number'] ?? '-') ?></td>
                <td><?= htmlspecialchars($part['name']) ?></td>
                <td><?= htmlspecialchars($part['manufacturer'] ?? '-') ?></td>
                <td><?= $part['total_on_hand'] ?? 0 ?></td>
                <td><?= $part['total_reserved'] ?? 0 ?></td>
                <td class="<?= ($part['total_on_hand'] - $part['total_reserved']) <= ($part['min_stock_level'] ?? 0) ? 'text-warning' : '' ?>">
                    <?= ($part['total_on_hand'] ?? 0) - ($part['total_reserved'] ?? 0) ?>
                </td>
                <td>
                    <a href="/parts/<?= $part['id'] ?>" class="btn-ghost btn-sm">View</a>
                    <a href="/parts/<?= $part['id'] ?>/edit" class="btn-ghost btn-sm">Edit</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (isset($pagination)): ?>
    <div class="pagination">
        <?php if ($pagination['current_page'] > 1): ?>
            <a href="?page=<?= $pagination['current_page'] - 1 ?>" class="btn-ghost">&laquo; Previous</a>
        <?php endif; ?>
        
        <span class="page-info">
            Page <?= $pagination['current_page'] ?> of <?= $pagination['last_page'] ?>
            (<?= $pagination['total'] ?> total)
        </span>
        
        <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
            <a href="?page=<?= $pagination['current_page'] + 1 ?>" class="btn-ghost">Next &raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty-state">
        <p>No parts found.</p>
        <a href="/parts/create" class="btn-primary">Add Your First Part</a>
    </div>
    <?php endif; ?>
</div>
