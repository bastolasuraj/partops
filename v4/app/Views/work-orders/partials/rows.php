<?php if (!empty($workOrders)): ?>
    <?php foreach ($workOrders as $wo): ?>
        <tr class="hover:bg-info">
            <td class="px-6 py-4 whitespace-nowrap font-mono font-semibold">
                <a href="<?= url('/work-orders/' . (int)$wo['id']) ?>" class="text-blue-700 hover:underline">
                    <?= htmlspecialchars(strtoupper($wo['wo_number'])) ?>
                </a>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <?= htmlspecialchars($wo['technician_name'] ?? '') ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <?= htmlspecialchars($wo['unit_number'] ?? '—') ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <?= date('Y-m-d H:i', strtotime($wo['created_at'])) ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                <a href="<?= url('/work-orders/' . (int)$wo['id']) ?>" class="text-blue-700 hover:underline">
                    View Details
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="5" class="px-6 py-6 text-center text-gray-500">No work orders found matching your criteria.</td>
    </tr>
<?php endif; ?>