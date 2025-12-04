<?php ob_start(); ?>

<h1 class="text-3xl font-bold text-gray-900 mb-8">Recent Movements</h1>

<?php if (empty($moves)): ?>
    <div class="p-8 text-center text-gray-500 italic bg-white rounded-xl shadow-sm border border-gray-100">
        No inventory movements recorded yet.
    </div>
<?php else: ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-bold text-gray-900">Recent Movements</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-700 uppercase text-xs">
                    <tr>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Part</th>
                        <th class="px-6 py-3">Action</th>
                        <th class="px-6 py-3">Qty</th>
                        <th class="px-6 py-3">Location</th>
                        <th class="px-6 py-3">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($moves as $move): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-gray-500 whitespace-nowrap"><?= htmlspecialchars($move['created_at']) ?></td>
                        <td class="px-6 py-4 font-medium text-gray-900">
                            <?= htmlspecialchars($move['anchor_slug']) ?>
                            <span class="block text-xs text-gray-500 font-normal"><?= htmlspecialchars($move['part_name']) ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                <?= $move['direction'] === 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= ucfirst($move['reason']) ?> (<?= strtoupper($move['direction']) ?>)
                            </span>
                        </td>
                        <td class="px-6 py-4 font-mono font-bold <?= $move['direction'] === 'in' ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $move['direction'] === 'in' ? '+' : '-' ?><?= $move['qty'] ?>
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            <?= htmlspecialchars($move['aisle'] . '-' . $move['shelf'] . '-' . $move['bay']) ?>
                        </td>
                        <td class="px-6 py-4 text-gray-500 truncate max-w-xs">
                            <?= htmlspecialchars($move['notes'] ?? '-') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
