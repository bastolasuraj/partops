<?php ob_start(); ?>

<div class="flex justify-between items-center mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Work Orders</h1>
    <a href="<?= url('/work-orders/create') ?>" class="btn">
        <i class="fas fa-plus mr-2"></i>Create Work Order
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <?php if (empty($workOrders)): ?>
        <div class="p-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-50 text-blue-600 mb-4">
                <i class="fas fa-clipboard-list text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Work Orders</h3>
            <p class="text-gray-500 mb-6">Get started by creating a new work order.</p>
            <a href="<?= url('/work-orders/create') ?>" class="btn">Create Work Order</a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-700 uppercase text-xs">
                    <tr>
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">External Ref</th>
                        <th class="px-6 py-3">Vehicle</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Opened</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($workOrders as $wo): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-gray-500">#<?= htmlspecialchars($wo['id']) ?></td>
                        <td class="px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars($wo['external_ref']) ?></td>
                        <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($wo['vehicle_ref'] ?? '-') ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                <?= $wo['status'] === 'open' ? 'bg-blue-100 text-blue-800' : 
                                   ($wo['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                   ($wo['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')) ?>">
                                <?= htmlspecialchars(ucfirst($wo['status'])) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($wo['opened_at']))) ?></td>
                        <td class="px-6 py-4">
                            <a href="<?= url('/work-orders/' . $wo['id']) ?>" class="text-blue-600 hover:text-blue-800 font-medium">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
