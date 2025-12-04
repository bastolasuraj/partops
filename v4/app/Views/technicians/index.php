<?php
// app/Views/technicians/index.php
// List with inline add/edit form and call buttons
?>
<div class="mb-6">
    <h1 class="text-3xl font-bold text-dark mb-2">Technicians</h1>
    <p class="text-gray-600">Manage technician information</p>
</div>

<!-- Add New Technician Form -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-xl font-bold text-dark mb-4">Add New Technician</h2>
    <form method="POST" action="<?= url('/technicians') ?>" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
        <div class="flex items-end">
            <button type="submit" class="w-full bg-primary text-dark px-6 py-2 rounded-lg hover:bg-secondary transition">
                Add Technician
            </button>
        </div>
    </form>
</div>

<!-- Technicians Table -->
<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-dark">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Email</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($technicians)): ?>
                    <?php foreach ($technicians as $t): ?>
                        <tr class="hover:bg-info">
                            <td class="px-6 py-4 whitespace-nowrap font-semibold">
                                <?= htmlspecialchars($t['name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($t['phone'])): ?>
                                    <a href="tel:<?= htmlspecialchars($t['phone']) ?>" 
                                       class="inline-flex items-center gap-2 bg-success text-dark px-3 py-1 rounded-lg hover:bg-green-400 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        <?= htmlspecialchars($t['phone']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($t['email'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($t['email']) ?>" class="text-blue-700 hover:underline">
                                        <?= htmlspecialchars($t['email']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <button onclick="editTechnician(<?= (int)$t['id'] ?>)" class="text-blue-700 hover:underline mr-3">
                                    Edit
                                </button>
                                <form method="POST" action="<?= url('/technicians/' . (int)$t['id'] . '/delete') ?>" class="inline" 
                                      onsubmit="return confirm('Delete this technician?')">
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-6 text-center text-gray-500">No technicians found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function editTechnician(id) {
    // Simple inline edit - could be enhanced with modal or inline form
    window.location.href = '<?= url('/technicians/') ?>' + id + '/edit';
}
</script>
