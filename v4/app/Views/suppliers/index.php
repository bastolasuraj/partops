<?php
// app/Views/suppliers/index.php
// List with inline add/edit form and table view
?>
<div class="mb-6">
    <h1 class="text-3xl font-bold text-dark mb-2">Suppliers</h1>
    <p class="text-gray-600">Manage supplier information</p>
</div>

<!-- Add New Supplier Form -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-xl font-bold text-dark mb-4">Add New Supplier</h2>
    <form method="POST" action="<?= url('/suppliers') ?>" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Name <span class="text-danger">*</span>
            </label>
            <input type="text" name="name" required
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Phone
            </label>
            <input type="tel" name="phone"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Email
            </label>
            <input type="email" name="email"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Address
            </label>
            <input type="text" name="address"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Website URL
            </label>
            <input type="url" name="url"
                   placeholder="https://example.com"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full bg-primary text-dark px-6 py-2 rounded-lg hover:bg-secondary transition">
                Add Supplier
            </button>
        </div>
    </form>
</div>

<!-- Suppliers Table -->
<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-dark">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Address</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Website</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Supplied Parts</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($suppliers)): ?>
                    <?php foreach ($suppliers as $s): ?>
                        <tr class="hover:bg-info" id="supplier-<?= (int)$s['id'] ?>">
                            <td class="px-6 py-4 whitespace-nowrap font-semibold">
                                <?= htmlspecialchars($s['name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($s['phone'])): ?>
                                    <a href="tel:<?= htmlspecialchars($s['phone']) ?>" class="text-blue-700 hover:underline">
                                        <?= htmlspecialchars($s['phone']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($s['email'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($s['email']) ?>" class="text-blue-700 hover:underline">
                                        <?= htmlspecialchars($s['email']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?= !empty($s['address']) ? htmlspecialchars($s['address']) : '<span class="text-gray-400">—</span>' ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if (!empty($s['url'])): ?>
                                    <a href="<?= htmlspecialchars($s['url']) ?>" target="_blank" class="text-blue-700 hover:underline">
                                        Visit
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php $parts = $suppliedParts[$s['id']] ?? []; ?>
                                <?php if (!empty($parts)): ?>
                                    <div class="space-y-1">
                                        <?php foreach ($parts as $p): ?>
                                            <div class="flex items-center justify-between bg-gray-50 border rounded px-3 py-2">
                                                <div>
                                                    <p class="font-semibold text-sm"><?= htmlspecialchars($p['part_name']) ?></p>
                                                    <p class="font-mono text-xs text-gray-600">
                                                        <?= htmlspecialchars($p['fowler_part_number']) ?> → <?= htmlspecialchars($p['supplier_part_number']) ?>
                                                    </p>
                                                </div>
                                                <a href="<?= url('/parts/' . (int)$p['part_id']) ?>" class="text-primary text-xs hover:underline">View</a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm">No linked parts yet</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <button onclick="editSupplier(<?= (int)$s['id'] ?>)" class="text-blue-700 hover:underline mr-3">
                                    Edit
                                </button>
                                <form method="POST" action="<?= url('/suppliers/' . (int)$s['id'] . '/delete') ?>" class="inline" 
                                      onsubmit="return confirm('Delete this supplier?')">
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-6 text-center text-gray-500">No suppliers found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function editSupplier(id) {
    // Simple inline edit - could be enhanced with modal or inline form
    window.location.href = '<?= url('/suppliers/') ?>' + id + '/edit';
}
</script>
