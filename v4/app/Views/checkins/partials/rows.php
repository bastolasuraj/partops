<?php if (!empty($checkins)): ?>
    <?php foreach ($checkins as $c): ?>
        <tr class="hover:bg-info">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                <?php if ($c['type'] === 'New Part'): ?>
                    <span class="px-2 py-1 bg-success text-white rounded-full text-xs">New Part</span>
                <?php else: ?>
                    <span class="px-2 py-1 bg-blue-500 text-white rounded-full text-xs">WO Return</span>
                <?php endif; ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <a href="<?= url('/parts/' . (int)$c['part_id']) ?>" class="text-blue-700 hover:underline">
                    <?= htmlspecialchars($c['part_name']) ?>
                </a>
                <div class="text-xs text-gray-500 font-mono">
                    <?= htmlspecialchars($c['fowler_part_number']) ?>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <?= htmlspecialchars($c['source_name'] ?? '—') ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap font-mono text-sm">
                <?= htmlspecialchars($c['supplier_part_number'] ?? '—') ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">
                <?php
                    // Format: A:1 S:1 B:1
                    $parts = [];
                    if (!empty($c['location_aisle'])) $parts[] = "A:{$c['location_aisle']}";
                    if (!empty($c['location_shelf'])) $parts[] = "S:{$c['location_shelf']}";
                    if (!empty($c['location_bay']))   $parts[] = "B:{$c['location_bay']}";
                    echo !empty($parts) ? htmlspecialchars(implode(' ', $parts)) : '—';
                ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 bg-gray-100 rounded font-semibold">
                    +<?= (int)$c['quantity'] ?>
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <?php if (isset($c['price'])): ?>
                    $<?= number_format((float)$c['price'], 2) ?>
                <?php else: ?>
                    <span class="text-gray-400">—</span>
                <?php endif; ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <?php if (!empty($c['has_core_charge'])): ?>
                    <span class="px-2 py-1 bg-warning rounded text-xs">
                        Core: $<?= number_format((float)($c['expected_rebate'] ?? 0), 2) ?>
                    </span>
                <?php else: ?>
                    <span class="text-gray-400">—</span>
                <?php endif; ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <?= date('Y-m-d', strtotime($c['created_at'])) ?>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="9" class="px-6 py-6 text-center text-gray-500">No check-ins found matching your criteria.</td>
    </tr>
<?php endif; ?>