<?php ob_start(); ?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="<?= url('/work-orders') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Work Orders
        </a>
    </div>

    <div class="bg-white p-8 rounded-xl shadow-md border border-gray-100">
        <h1 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-clipboard-list text-blue-600 mr-3"></i>Create Work Order
        </h1>
        
        <form action="<?= url('/work-orders') ?>" method="POST" class="space-y-6">
            <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
            
            <div>
                <label for="external_ref">External Reference ID (Required)</label>
                <input type="text" name="external_ref" id="external_ref" required 
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                    placeholder="e.g. WO-2024-001">
                <p class="text-xs text-gray-500 mt-1">Unique identifier for this work order.</p>
            </div>

            <div>
                <label for="vehicle_ref">Vehicle Reference / VIN</label>
                <input type="text" name="vehicle_ref" id="vehicle_ref"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                    placeholder="e.g. Ford F-150 or VIN">
            </div>

            <div class="pt-4">
                <button type="submit" class="btn w-full justify-center text-lg">
                    <i class="fas fa-plus mr-2"></i>Create Work Order
                </button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
