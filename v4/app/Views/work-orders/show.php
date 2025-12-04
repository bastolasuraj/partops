<?php
// app/Views/work-orders/show.php
// Work order details with parts breakdown
?>
<div class="mb-6 flex justify-between items-start">
    <div>
        <h1 class="text-3xl font-bold text-dark mb-2">Work Order: <?= htmlspecialchars($workOrder['wo_number']) ?></h1>
        <p class="text-gray-600">View parts and details for this work order</p>
    </div>
    <a href="<?= url('/work-orders') ?>" class="bg-gray-200 text-dark px-4 py-2 rounded-lg hover:bg-gray-300">
        Back to List
    </a>
</div>

<!-- Work Order Details Card -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-xl font-bold text-dark mb-4">Work Order Details</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <p class="text-sm text-gray-600">WO Number</p>
            <p class="font-mono font-semibold text-lg"><?= htmlspecialchars($workOrder['wo_number']) ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Technician</p>
            <p class="font-semibold text-lg"><?= htmlspecialchars($workOrder['technician_name'] ?? '') ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Unit Number</p>
            <p class="font-semibold text-lg"><?= htmlspecialchars($workOrder['unit_number'] ?? '—') ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Created</p>
            <p class="font-semibold"><?= date('Y-m-d H:i', strtotime($workOrder['created_at'])) ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Unique Parts</p>
            <p class="font-semibold text-lg"><?= count($parts ?? []) ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Parts Summary</p>
            <?php
                $totalCheckedOut = array_sum(array_column($parts ?? [], 'total_checked_out'));
                $totalReturned = array_sum(array_column($parts ?? [], 'total_returned'));
            ?>
            <div class="text-sm">
                <span class="font-semibold text-success">Issued: <?= $totalCheckedOut ?></span>
                <?php if ($totalReturned > 0): ?>
                    <span class="mx-1">|</span>
                    <span class="font-semibold text-warning">Returned: <?= $totalReturned ?></span>
                <?php endif; ?>
                <div class="font-bold text-lg mt-1">Net: <?= $totalCheckedOut - $totalReturned ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Parts Breakdown -->
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-dark">Parts Used</h2>
    </div>
    
    <?php if (!empty($parts)): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Fowler #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Part Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Supplier Part #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Checked Out</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Returned</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Net Used</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php 
                    $seenParts = [];
                    foreach ($parts as $p): 
                        // Only show returns on the first occurrence of a part ID to avoid double counting visually
                        $showReturn = !in_array($p['part_id'], $seenParts);
                        if ($showReturn) {
                            $seenParts[] = $p['part_id'];
                            $returnedQty = (int)$p['total_returned'];
                        } else {
                            $returnedQty = 0;
                        }
                    ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-mono">
                                <a href="<?= url('/parts/' . (int)$p['part_id']) ?>" class="text-blue-700 hover:underline">
                                    <?= htmlspecialchars($p['fowler_part_number']) ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?= htmlspecialchars($p['part_name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?= htmlspecialchars($p['supplier_part_number'] ?? 'N/A') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 bg-info rounded">
                                    <?= (int)$p['total_checked_out'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($showReturn && $returnedQty > 0): ?>
                                    <span class="px-2 py-1 bg-warning rounded text-xs">
                                        -<?= $returnedQty ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-bold">
                                <?= (int)$p['total_checked_out'] - $returnedQty ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-gray-500 text-center py-8">No parts checked out yet.</p>
    <?php endif; ?>
</div>
