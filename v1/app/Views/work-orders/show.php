<?php ob_start(); ?>

<div class="mb-6">
    <a href="<?= url('/work-orders') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-2"></i>Back to Work Orders
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-8">
        <!-- Main Details -->
        <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-1">Work Order #<?= htmlspecialchars($workOrder['id']) ?></h1>
                    <p class="font-mono text-lg text-blue-600"><?= htmlspecialchars($workOrder['external_ref']) ?></p>
                </div>
                <div>
                    <span class="px-4 py-2 rounded-full text-sm font-bold uppercase tracking-wide 
                        <?= $workOrder['status'] === 'open' ? 'bg-blue-100 text-blue-800' : 
                           ($workOrder['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                           ($workOrder['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')) ?>">
                        <?= htmlspecialchars($workOrder['status']) ?>
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase mb-1">Vehicle Reference</h3>
                    <p class="text-lg font-medium text-gray-900"><?= htmlspecialchars($workOrder['vehicle_ref'] ?? 'N/A') ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase mb-1">Opened At</h3>
                    <p class="text-gray-900"><?= htmlspecialchars($workOrder['opened_at']) ?></p>
                </div>
                <?php if ($workOrder['closed_at']): ?>
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase mb-1">Closed At</h3>
                    <p class="text-gray-900"><?= htmlspecialchars($workOrder['closed_at']) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Parts Used (Placeholder) -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 opacity-75">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Parts Used</h2>
            <p class="text-gray-500 italic">Parts tracking on work orders coming soon.</p>
        </div>
    </div>

    <div class="lg:col-span-1 space-y-8">
        <!-- Actions -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Manage Status</h2>
            
            <form action="<?= url('/work-orders/' . $workOrder['id'] . '/status') ?>" method="POST" class="space-y-4">
                <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token'] ?? '' ?>">
                
                <label for="status" class="sr-only">Change Status</label>
                <select name="status" id="status" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5">
                    <option value="open" <?= $workOrder['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                    <option value="in_progress" <?= $workOrder['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="completed" <?= $workOrder['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $workOrder['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>

                <button type="submit" class="btn w-full justify-center">Update Status</button>
            </form>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
