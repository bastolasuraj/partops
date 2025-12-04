<?php 
// Helper for phone formatting
function format_phone_display($phone) {
    $digits = preg_replace('/\D/', '', $phone ?? '');
    if (empty($digits)) return $phone;
    
    $len = strlen($digits);
    if ($len == 10) {
        return '+1 (' . substr($digits, 0, 3) . ') ' . substr($digits, 3, 3) . '-' . substr($digits, 6);
    } elseif ($len > 10) {
        $p = substr($digits, -10);
        $cc = substr($digits, 0, $len - 10);
        return '+' . $cc . ' (' . substr($p, 0, 3) . ') ' . substr($p, 3, 3) . '-' . substr($p, 6);
    }
    return $phone;
}

ob_start(); 
?>

<div class="flex flex-col space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Suppliers</h1>
    </div>

    <!-- Quick Add Supplier Card -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-plus-circle text-blue-600 mr-2"></i>Add New Supplier
        </h3>
        <form action="<?= url('/suppliers') ?>" method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
            <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="is_active" value="1">

            <div class="md:col-span-1">
                <label for="new_name" class="sr-only">Name</label>
                <input type="text" name="name" id="new_name" required placeholder="Supplier Name" 
                    class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
            </div>

            <div class="md:col-span-1">
                <label for="new_website" class="sr-only">Website</label>
                <input type="text" name="website" id="new_website" placeholder="Website URL" 
                    class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
            </div>

            <div class="md:col-span-1">
                <label for="new_contact" class="sr-only">Contact</label>
                <input type="text" name="contact_name" id="new_contact" placeholder="Contact Person"
                    class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
            </div>

            <div class="md:col-span-1">
                <label for="new_email" class="sr-only">Email</label>
                <input type="email" name="email" id="new_email" placeholder="Email"
                    class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
            </div>
            
            <div class="md:col-span-1">
                <label for="new_phone" class="sr-only">Phone</label>
                <input type="text" name="phone" id="new_phone" placeholder="Phone"
                    class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
            </div>

            <div class="md:col-span-1">
                <button type="submit" class="btn w-full justify-center">
                    <i class="fas fa-save mr-2"></i>Add
                </button>
            </div>
        </form>
    </div>

    <!-- Suppliers Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="bg-gray-50 text-gray-700 uppercase text-xs">
                    <tr>
                        <th class="px-6 py-3">Name / Website</th>
                        <th class="px-6 py-3">Contact</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Phone</th>
                        <th class="px-6 py-3 text-center">Preferred</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($suppliers as $supplier): ?>
                        <?php if ($editId === $supplier['id']): ?>
                            <!-- Edit Mode Row -->
                            <tr class="bg-blue-50/50">
                                <!-- Hidden Form Definition -->
                                <form id="edit-form-<?= $supplier['id'] ?>" action="<?= url('/suppliers/' . $supplier['id']) ?>" method="POST">
                                    <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
                                    <input type="hidden" name="is_active" value="<?= $supplier['is_active'] ?>">
                                </form>

                                <td class="px-6 py-4">
                                    <input type="text" name="name" value="<?= htmlspecialchars($supplier['name']) ?>" form="edit-form-<?= $supplier['id'] ?>" required
                                        class="w-full bg-white border border-blue-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2 mb-2" placeholder="Name">
                                    <input type="text" name="website" value="<?= htmlspecialchars($supplier['website'] ?? '') ?>" form="edit-form-<?= $supplier['id'] ?>"
                                        class="w-full bg-white border border-blue-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2" placeholder="Website URL">
                                </td>
                                <td class="px-6 py-4">
                                    <input type="text" name="contact_name" value="<?= htmlspecialchars($supplier['contact_name'] ?? '') ?>" form="edit-form-<?= $supplier['id'] ?>"
                                        class="w-full bg-white border border-blue-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2">
                                </td>
                                <td class="px-6 py-4">
                                    <input type="email" name="email" value="<?= htmlspecialchars($supplier['email'] ?? '') ?>" form="edit-form-<?= $supplier['id'] ?>"
                                        class="w-full bg-white border border-blue-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2">
                                </td>
                                <td class="px-6 py-4">
                                    <input type="text" name="phone" value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>" form="edit-form-<?= $supplier['id'] ?>"
                                        class="w-full bg-white border border-blue-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2">
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" name="is_preferred" value="1" <?= $supplier['is_preferred'] ? 'checked' : '' ?> form="edit-form-<?= $supplier['id'] ?>"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <button type="submit" form="edit-form-<?= $supplier['id'] ?>" class="text-green-600 hover:text-green-900 mr-3 font-medium">
                                        <i class="fas fa-check mr-1"></i>Save
                                    </button>
                                    <a href="<?= url('/suppliers') ?>" class="text-gray-500 hover:text-gray-700 font-medium">
                                        <i class="fas fa-times mr-1"></i>Cancel
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <!-- Read Mode Row -->
                            <tr class="hover:bg-gray-50 group">
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    <?php if (!empty($supplier['website'])): ?>
                                        <a href="<?= htmlspecialchars($supplier['website']) ?>" target="_blank" class="hover:text-blue-600 flex items-center group-link">
                                            <?= htmlspecialchars($supplier['name']) ?>
                                            <i class="fas fa-external-link-alt text-xs ml-2 text-gray-400 group-link-hover:text-blue-500"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-900">
                                            <?= htmlspecialchars($supplier['name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4"><?= htmlspecialchars($supplier['contact_name'] ?? '-') ?></td>
                                <td class="px-6 py-4 text-blue-600 hover:underline">
                                    <?= $supplier['email'] ? '<a href="mailto:'.htmlspecialchars($supplier['email']).'">'.htmlspecialchars($supplier['email']).'</a>' : '-' ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (!empty($supplier['phone'])): ?>
                                        <a href="tel:<?= htmlspecialchars($supplier['phone']) ?>" class="text-blue-600 hover:text-blue-800">
                                            <?= htmlspecialchars(format_phone_display($supplier['phone'])) ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($supplier['is_preferred']): ?>
                                        <span class="text-yellow-500 text-lg"><i class="fas fa-star"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="<?= url('/suppliers?edit=' . $supplier['id']) ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="<?= url('/suppliers/' . $supplier['id'] . '/delete') ?>" method="POST" class="inline-block" onsubmit="return confirm('Delete supplier?');">
                                        <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
