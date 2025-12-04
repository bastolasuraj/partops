<?php ob_start(); ?>

<div class="flex flex-col space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <p class="text-xs uppercase tracking-wider text-gray-500 font-bold mb-1">Inventory</p>
            <h1 class="text-3xl font-bold text-gray-900">Parts Catalog</h1>
        </div>
        <div class="flex gap-3">
            <a href="<?= url('/parts/create') ?>" class="btn">
                <i class="fas fa-plus mr-2"></i>New Part
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-wrap gap-4 items-center">
        <div class="flex-1 min-w-[200px]">
            <form action="<?= url('/parts/search') ?>" method="GET" class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="q" placeholder="Search by anchor, part #, name..." value="<?= htmlspecialchars($query ?? '') ?>" 
                    class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </form>
        </div>
        <div class="flex gap-2">
            <!-- Placeholders for future filters -->
            <select class="bg-gray-50 border border-gray-200 rounded-lg py-2 px-3 text-sm text-gray-600">
                <option>All Status</option>
                <option>Active</option>
                <option>Inactive</option>
            </select>
        </div>
    </div>

    <!-- Parts Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="bg-gray-50 text-gray-700 uppercase text-xs">
                    <tr>
                        <th class="px-6 py-3">Anchor</th>
                        <th class="px-6 py-3">Active #</th>
                        <th class="px-6 py-3">Name</th>
                        <th class="px-6 py-3 text-center">On Hand</th>
                        <th class="px-6 py-3">Location</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($parts)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500 italic">No parts found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($parts as $part): ?>
                        <tr class="hover:bg-gray-50 group transition-colors">
                            <td class="px-6 py-4">
                                <a href="<?= url('/parts/' . $part['id']) ?>" class="font-mono text-blue-600 hover:text-blue-800 font-medium">
                                    <?= htmlspecialchars($part['anchor_slug']) ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900">
                                <?= htmlspecialchars($part['primary_number'] ?? '-') ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-gray-900 font-medium"><?= htmlspecialchars($part['name']) ?></div>
                                <div class="text-xs text-gray-500 truncate max-w-[200px]"><?= htmlspecialchars($part['description'] ?? '') ?></div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="font-mono font-bold <?= ($part['total_stock'] ?? 0) > 0 ? 'text-gray-900' : 'text-red-500' ?>">
                                    <?= $part['total_stock'] ?? 0 ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <?= htmlspecialchars($part['locations'] ?? '-') ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($part['is_active']): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="<?= url('/parts/' . $part['id'] . '/edit') ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="<?= url('/parts/' . $part['id'] . '/delete') ?>" method="POST" class="inline-block" onsubmit="return confirm('Delete this part?');">
                                    <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token'] ?? '' ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>