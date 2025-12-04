<?php ob_start(); ?>

<div class="mb-6 flex justify-between items-center">
    <a href="<?= url('/parts') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-2"></i>Back to Parts
    </a>
    <div class="flex gap-2">
        <a href="<?= url('/parts/' . $part['id'] . '/edit') ?>" class="btn">
            <i class="fas fa-edit mr-2"></i>Edit
        </a>
        <form action="<?= url('/parts/' . $part['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete this part?');">
            <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token'] ?? '' ?>">
            <button type="submit" class="btn bg-red-600 hover:bg-red-700 text-white">
                <i class="fas fa-trash-alt mr-2"></i>Delete
            </button>
        </form>
    </div>
</div>

<!-- Header Card -->
<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($part['name']) ?></h1>
            <div class="flex items-center gap-3">
                <span class="font-mono text-lg text-blue-600 bg-blue-50 px-2 py-1 rounded">
                    <?= htmlspecialchars($part['anchor_slug']) ?>
                </span>
                <?php if ($part['is_active']): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Active
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        Inactive
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-500 uppercase tracking-wider font-semibold">Total On Hand</p>
            <p class="text-3xl font-bold text-gray-900">
                <?php 
                    $total = 0;
                    foreach ($part['inventory'] as $inv) $total += $inv['on_hand'];
                    echo $total;
                ?>
            </p>
        </div>
    </div>
    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-100 pt-4">
        <div>
            <h3 class="text-sm font-bold text-gray-500 uppercase mb-1">Description</h3>
            <p class="text-gray-700"><?= nl2br(htmlspecialchars($part['description'] ?? '-')) ?></p>
        </div>
        <div>
            <h3 class="text-sm font-bold text-gray-500 uppercase mb-1">Notes</h3>
            <p class="text-gray-700"><?= nl2br(htmlspecialchars($part['notes'] ?? '-')) ?></p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Left Column: Numbers & Suppliers -->
    <div class="lg:col-span-2 space-y-6">
        
        <!-- Part Numbers -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-900">Part Numbers</h3>
            </div>
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3">Number</th>
                        <th class="px-6 py-3">Type</th>
                        <th class="px-6 py-3">Manufacturer</th>
                        <th class="px-6 py-3">Primary</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($part['numbers'] as $number): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 font-medium"><?= htmlspecialchars($number['value']) ?></td>
                        <td class="px-6 py-3"><?= ucfirst($number['type']) ?></td>
                        <td class="px-6 py-3"><?= htmlspecialchars($number['manufacturer'] ?? '-') ?></td>
                        <td class="px-6 py-3"><?= $number['is_primary'] ? '<i class="fas fa-check text-green-500"></i>' : '' ?></td>
                        <td class="px-6 py-3 text-right">
                            <form action="<?= url('/parts/' . $part['id'] . '/numbers/' . $number['id'] . '/delete') ?>" method="POST" class="inline" onsubmit="return confirm('Delete?');">
                                <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token'] ?? '' ?>">
                                <button class="text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <!-- Add Row -->
                    <tr class="bg-blue-50/30">
                        <form action="<?= url('/parts/' . $part['id'] . '/numbers') ?>" method="POST">
                            <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token'] ?? '' ?>">
                            <td class="px-6 py-3"><input type="text" name="value" placeholder="New Number" required class="w-full text-sm border-gray-300 rounded"></td>
                            <td class="px-6 py-3">
                                <select name="type" class="w-full text-sm border-gray-300 rounded">
                                    <option value="active">Active</option>
                                    <option value="historical">Historical</option>
                                    <option value="aftermarket">Aftermarket</option>
                                </select>
                            </td>
                            <td class="px-6 py-3"><input type="text" name="manufacturer" placeholder="Mfr" class="w-full text-sm border-gray-300 rounded"></td>
                            <td class="px-6 py-3 text-center"><input type="checkbox" name="is_primary" value="1" class="rounded border-gray-300"></td>
                            <td class="px-6 py-3 text-right"><button type="submit" class="text-blue-600 font-bold text-sm">Add</button></td>
                        </form>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Suppliers -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-900">Suppliers & Pricing</h3>
            </div>
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3">Supplier</th>
                        <th class="px-6 py-3">SKU</th>
                        <th class="px-6 py-3 text-right">Price</th>
                        <th class="px-6 py-3 text-right">Core</th>
                        <th class="px-6 py-3 text-right">Rebate</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($part['suppliers'] as $ps): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 font-medium">
                            <?= htmlspecialchars($ps['supplier_name']) ?>
                            <?php if ($ps['is_preferred']): ?><i class="fas fa-star text-yellow-400 ml-1"></i><?php endif; ?>
                        </td>
                        <td class="px-6 py-3"><?= htmlspecialchars($ps['supplier_sku'] ?? '-') ?></td>
                        <td class="px-6 py-3 text-right">$<?= number_format((float)$ps['price'], 2) ?></td>
                        <td class="px-6 py-3 text-right">$<?= number_format((float)$ps['core_charge'], 2) ?></td>
                        <td class="px-6 py-3 text-right text-green-600">$<?= number_format((float)$ps['expected_rebate'], 2) ?></td>
                        <td class="px-6 py-3 text-right">
                            <form action="<?= url('/parts/' . $part['id'] . '/suppliers/' . $ps['id'] . '/delete') ?>" method="POST" class="inline" onsubmit="return confirm('Remove supplier?');">
                                <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token'] ?? '' ?>">
                                <button class="text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <!-- Add Row -->
                    <tr class="bg-blue-50/30">
                        <form action="<?= url('/parts/' . $part['id'] . '/suppliers') ?>" method="POST">
                            <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token'] ?? '' ?>">
                            <td class="px-6 py-3">
                                <select name="supplier_id" required class="w-full text-sm border-gray-300 rounded">
                                    <option value="">Select...</option>
                                    <?php foreach ($allSuppliers as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="px-6 py-3"><input type="text" name="supplier_sku" placeholder="SKU" class="w-full text-sm border-gray-300 rounded"></td>
                            <td class="px-6 py-3"><input type="number" step="0.01" name="price" placeholder="0.00" class="w-full text-sm border-gray-300 rounded text-right"></td>
                            <td class="px-6 py-3"><input type="number" step="0.01" name="core_charge" placeholder="0.00" class="w-full text-sm border-gray-300 rounded text-right"></td>
                            <td class="px-6 py-3"><input type="number" step="0.01" name="expected_rebate" placeholder="0.00" class="w-full text-sm border-gray-300 rounded text-right"></td>
                            <td class="px-6 py-3 text-right"><button type="submit" class="text-blue-600 font-bold text-sm">Add</button></td>
                        </form>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Right Column: Inventory -->
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-900">Inventory Levels</h3>
            </div>
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3">Location</th>
                        <th class="px-6 py-3 text-right">On Hand</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($part['inventory'])): ?>
                        <tr><td colspan="2" class="px-6 py-4 text-center text-gray-500 italic">No stock</td></tr>
                    <?php else: ?>
                        <?php foreach ($part['inventory'] as $inv): ?>
                        <tr>
                            <td class="px-6 py-3">
                                <?= htmlspecialchars($inv['aisle'] . '-' . $inv['shelf'] . '-' . $inv['bay']) ?>
                                <?php if ($inv['bin']): ?><span class="text-gray-400 text-xs">(<?= htmlspecialchars($inv['bin']) ?>)</span><?php endif; ?>
                            </td>
                            <td class="px-6 py-3 text-right font-mono font-bold"><?= $inv['on_hand'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="p-4 bg-gray-50 border-t border-gray-100">
                <a href="<?= url('/inventory/adjust') ?>" class="btn btn-secondary w-full justify-center text-xs">Adjust Inventory</a>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>