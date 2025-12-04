<?php
// app/Views/returns/index.php
// Returns dashboard with links to WO and supplier returns
?>
<div class="mb-6">
    <h1 class="text-3xl font-bold text-dark mb-2">Returns</h1>
    <p class="text-gray-600">Manage work order and supplier returns</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Supplier Returns Card -->
    <div class="bg-gradient-to-br from-info to-blue-200 rounded-lg shadow-xl p-8 text-dark hover:shadow-2xl transition">
        <div class="flex items-center mb-4">
            <svg class="w-12 h-12 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            <h2 class="text-2xl font-bold">Supplier Returns</h2>
        </div>
        <p class="mb-6 text-dark opacity-90">Return defective parts or cores to suppliers for credit or rebate</p>
        <a href="<?= url('/returns/supplier') ?>" 
           class="inline-block bg-dark text-white px-6 py-3 rounded-lg hover:bg-gray-800 transition">
            Process Supplier Return
        </a>
    </div>
</div>

<!-- Outstanding Core Charges -->
<div class="mt-8 bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-bold text-dark mb-4">Outstanding Core Charges</h2>
    <?php if (!empty($outstandingCores)): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Part</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Expected Rebate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($outstandingCores as $core): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?= htmlspecialchars($core['part_name']) ?>
                                <div class="text-xs text-gray-500 font-mono">
                                    <?= htmlspecialchars($core['fowler_part_number']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?= htmlspecialchars($core['supplier_name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 bg-warning rounded font-semibold">
                                    $<?= number_format($core['expected_rebate'], 2) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?= date('Y-m-d', strtotime($core['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <a href="<?= url('/returns/supplier?core_id=' . (int)$core['id']) ?>" 
                                   class="text-blue-700 hover:underline">
                                    Process Return
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-gray-500 text-center py-6">No outstanding core charges</p>
    <?php endif; ?>
</div>
