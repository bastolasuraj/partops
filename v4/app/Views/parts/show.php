<?php
// app/Views/parts/show.php
// Part detail page with inventory, transactions, and QR code
$qrText = urlencode(($part['fowler_part_number'] ?? '') . ' - ' . ($part['name'] ?? ''));
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . $qrText;
$onHand = (int)($part['on_hand'] ?? 0);
$lowTh = isset($part['low_stock_threshold']) ? (int)$part['low_stock_threshold'] : null;
$isLow = $lowTh !== null && $onHand <= $lowTh;
?>
<div class="mb-6 flex justify-between items-start">
    <div>
        <h1 class="text-3xl font-bold text-dark mb-2"><?= htmlspecialchars($part['name'] ?? '') ?></h1>
        <p class="text-gray-600 font-mono"><?= htmlspecialchars($part['fowler_part_number'] ?? '') ?></p>
    </div>
    <div class="flex gap-3">
        <a href="<?= url('/parts/' . (int)$part['id'] . '/edit') ?>" class="bg-primary text-dark px-4 py-2 rounded-lg hover:bg-secondary">
            Edit
        </a>
        <a href="<?= url('/parts') ?>" class="bg-gray-200 text-dark px-4 py-2 rounded-lg hover:bg-gray-300">
            Back to List
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Part Details Card -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-dark mb-4">Part Details</h2>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-600">Fowler Part Number</p>
                <p class="font-mono font-semibold"><?= htmlspecialchars($part['fowler_part_number'] ?? '') ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">On Hand</p>
                <p class="font-semibold">
                    <span class="px-3 py-1 rounded text-lg <?= $isLow ? 'bg-danger text-white' : 'bg-success text-dark' ?>">
                        <?= $onHand ?>
                    </span>
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Low Stock Threshold</p>
                <p class="font-semibold"><?= $lowTh !== null ? $lowTh : 'Not set' ?></p>
            </div>
            <?php if (!empty($part['url'])): ?>
            <div class="col-span-2">
                <p class="text-sm text-gray-600">Product URL</p>
                <a href="<?= htmlspecialchars($part['url']) ?>" target="_blank" 
                   class="text-blue-700 hover:underline break-all">
                    <?= htmlspecialchars($part['url']) ?>
                </a>
            </div>
            <?php endif; ?>
            <?php if (!empty($part['notes'])): ?>
            <div class="col-span-2">
                <p class="text-sm text-gray-600">Notes</p>
                <p class="text-gray-800"><?= nl2br(htmlspecialchars($part['notes'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- QR Code Card -->
    <div class="bg-white rounded-lg shadow p-6 text-center">
        <h2 class="text-xl font-bold text-dark mb-4">QR Code</h2>
        <img src="<?= $qrUrl ?>" alt="QR Code" class="mx-auto mb-4 rounded">
        <p class="text-sm text-gray-600">Scan to view part details</p>
    </div>
</div>

<!-- Recent Transactions -->
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-bold text-dark mb-4">Recent Transactions</h2>
    <?php if (!empty($transactions)): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Quantity</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">WO/Supplier</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Notes</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($transactions as $t): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <?= date('Y-m-d H:i', strtotime($t['created_at'])) ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 rounded text-xs <?= $t['type']==='checkin' ? 'bg-success' : 'bg-warning' ?>">
                                    <?= htmlspecialchars($t['type']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap font-semibold">
                                <?= (int)$t['quantity'] ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <?= htmlspecialchars($t['reference'] ?? '') ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?= htmlspecialchars($t['notes'] ?? '') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-gray-500 text-center py-6">No transactions yet</p>
    <?php endif; ?>
</div>
