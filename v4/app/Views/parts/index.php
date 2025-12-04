<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-3xl font-bold text-dark">Parts Inventory</h1>
        <p class="text-gray-600">Browse parts, check stock, and jump to details.</p>
    </div>
    <a href="<?= url('/parts/create') ?>" class="bg-primary text-dark px-4 py-2 rounded-lg shadow hover:bg-secondary transition">
        + Add Part
    </a>
</div>

<?php
    $totalParts = count($parts);
    $lowStockCount = array_reduce($parts, static function ($carry, $part) {
        return $carry + (!empty($part['is_low_stock']) ? 1 : 0);
    }, 0);
?>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
        <p class="text-sm text-gray-500">Total Parts</p>
        <p class="text-2xl font-bold text-dark"><?= $totalParts ?></p>
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
        <p class="text-sm text-gray-500">Low Stock</p>
        <p class="text-2xl font-bold text-danger"><?= $lowStockCount ?></p>
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
        <p class="text-sm text-gray-500">On-Hand Avg</p>
        <p class="text-2xl font-bold text-dark">
            <?php
                $avg = $totalParts > 0
                    ? array_sum(array_column($parts, 'on_hand_quantity')) / $totalParts
                    : 0;
                echo number_format($avg, 1);
            ?>
        </p>
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
        <p class="text-sm text-gray-500">View Prototype</p>
        <a href="<?= url('/parts/create') ?>" class="text-primary underline">Add another part</a>
    </div>
</div>

<?php if (empty($parts)): ?>
    <div class="bg-white border border-gray-200 rounded-lg p-6 text-center">
        <p class="text-gray-600">No parts found.</p>
        <a href="<?= url('/parts/create') ?>" class="text-primary underline">Create the first part</a>
    </div>
<?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-dark">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Fowler #</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">On Hand</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Low Stock</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">QR</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($parts as $part): ?>
                    <?php
                        $isLow = !empty($part['is_low_stock']);
                    ?>
                    <tr class="<?= $isLow ? 'bg-red-50' : 'hover:bg-gray-50' ?>">
                        <td class="px-4 py-3 whitespace-nowrap font-semibold text-dark">
                            <a href="<?= url('/parts/' . $part['part_id']) ?>" class="text-primary hover:underline">
                                <?= htmlspecialchars($part['fowler_part_number']) ?>
                            </a>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <?= htmlspecialchars($part['part_name']) ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap font-semibold <?= $isLow ? 'text-danger' : 'text-dark' ?>">
                            <?= (int)($part['on_hand_quantity'] ?? 0) ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <?php if ($isLow): ?>
                                <span class="px-2 py-1 text-xs font-semibold text-white bg-danger rounded-full">Low</span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs font-semibold text-white bg-success rounded-full">OK</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=<?= urlencode($part['fowler_part_number']) ?>" alt="QR <?= htmlspecialchars($part['fowler_part_number']) ?>" class="w-10 h-10 rounded">
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap space-x-2">
                            <a href="<?= url('/parts/' . $part['part_id']) ?>" class="text-primary hover:underline">View</a>
                            <a href="<?= url('/parts/' . $part['part_id'] . '/edit') ?>" class="text-dark hover:underline">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
