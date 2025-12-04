<?php
// app/Views/checkouts/index.php
// Recent checkouts list
?>
<div class="mb-6">
    <h1 class="text-3xl font-bold text-dark mb-2">Parts Checkouts</h1>
    <p class="text-gray-600">View recent parts issued to work orders</p>
</div>

<div class="mb-6">
    <a href="<?= url('/checkouts/create') ?>" class="inline-block bg-primary text-dark px-6 py-2 rounded-lg hover:bg-secondary">
        New Checkout
    </a>
</div>

<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-dark">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Work Order</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Technician</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Unit #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Part</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Quantity</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($checkouts)): ?>
                    <?php foreach ($checkouts as $co): ?>
                        <tr class="hover:bg-info">
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?= date('Y-m-d H:i', strtotime($co['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-mono">
                                <a href="<?= url('/work-orders/' . (int)$co['work_order_id']) ?>" class="text-blue-700 hover:underline">
                                    <?= htmlspecialchars($co['wo_number']) ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?= htmlspecialchars($co['technician_name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?= htmlspecialchars($co['unit_number'] ?? '—') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="<?= url('/parts/' . (int)$co['part_id']) ?>" class="text-blue-700 hover:underline">
                                    <?= htmlspecialchars($co['part_name']) ?>
                                </a>
                                <div class="text-xs text-gray-500 font-mono">
                                    <?= htmlspecialchars($co['fowler_part_number']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 bg-warning rounded font-semibold">
                                    -<?= (int)$co['quantity'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-6 text-center text-gray-500">No checkouts found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
