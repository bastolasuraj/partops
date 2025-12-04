<?php ob_start(); ?>

<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="<?= url('/inventory') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Inventory
        </a>
    </div>

    <div class="bg-white p-8 rounded-xl shadow-md border border-gray-100">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                <i class="fas fa-undo text-orange-600 mr-3"></i>Returns & Core
            </h1>
        </div>
        
        <form action="<?= url('/inventory/return') ?>" method="POST" class="space-y-6">
            <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="idempotency_key" value="<?= $idempotency_key ?>">
            
            <!-- Main Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="part_id">Part</label>
                    <select name="part_id" id="part_id" required class="font-mono">
                        <option value="">Select Part...</option>
                        <?php foreach ($parts as $part): ?>
                            <option value="<?= $part['id'] ?>">
                                <?= htmlspecialchars($part['anchor_slug']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="reason">Return Type</label>
                    <select name="reason" id="reason">
                        <option value="return">Standard Return (Restock?)</option>
                        <option value="core_return">Core Return</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Standard: Returning unused part to supplier.<br>Core: Returning used core for rebate.</p>
                </div>
            </div>

            <!-- Supplier & Location -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="supplier_id">Supplier</label>
                    <select name="supplier_id" id="supplier_id">
                        <option value="">Unknown / None</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label for="location_aisle">Aisle</label>
                        <input type="text" name="location_aisle" id="location_aisle" placeholder="A1" required>
                    </div>
                    <div>
                        <label for="location_shelf">Shelf</label>
                        <input type="text" name="location_shelf" id="location_shelf" placeholder="S1" required>
                    </div>
                    <div>
                        <label for="location_bay">Bay</label>
                        <input type="text" name="location_bay" id="location_bay" placeholder="B1" required>
                    </div>
                </div>
            </div>

            <!-- Core Logic -->
            <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
                <h3 class="text-sm font-bold text-orange-800 uppercase mb-3">Core Management</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="core_due_state">Core State</label>
                        <select name="core_due_state" id="core_due_state">
                            <option value="none">None</option>
                            <option value="due">Due</option>
                            <option value="sent">Sent</option>
                            <option value="rebated">Rebated</option>
                        </select>
                    </div>
                    <div>
                        <label for="core_charge">Core Charge @ Tx</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">$</div>
                            <input type="number" name="core_charge" id="core_charge" step="0.01" min="0" placeholder="0.00" class="pl-7">
                        </div>
                    </div>
                    <div>
                        <label for="expected_rebate">Rebate Expected</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">$</div>
                            <input type="number" name="expected_rebate" id="expected_rebate" step="0.01" min="0" placeholder="0.00" class="pl-7">
                        </div>
                    </div>
                    <div>
                        <label for="rebate_received">Rebate Received</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">$</div>
                            <input type="number" name="rebate_received" id="rebate_received" step="0.01" min="0" placeholder="0.00" class="pl-7">
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label for="qty">Quantity</label>
                <input type="number" name="qty" id="qty" min="1" required placeholder="1" class="font-bold text-lg">
            </div>

            <div>
                <label for="notes">Notes / RMA</label>
                <textarea name="notes" id="notes" rows="3" placeholder="Reason for return..."></textarea>
            </div>

            <div class="pt-4">
                <button type="submit" class="btn w-full justify-center text-lg bg-orange-600 hover:bg-orange-700">
                    <i class="fas fa-paper-plane mr-2"></i>Process Return
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function() {
        const partLocations = <?= json_encode($partLocations ?? []) ?>;
        const partSelect = document.getElementById('part_id');
        const aisle = document.getElementById('location_aisle');
        const shelf = document.getElementById('location_shelf');
        const bay = document.getElementById('location_bay');

        function fillLocation(id) {
            const loc = partLocations[id];
            if (!loc) {
                aisle.value = '';
                shelf.value = '';
                bay.value = '';
                return;
            }
            aisle.value = loc.aisle || '';
            shelf.value = loc.shelf || '';
            bay.value = loc.bay || '';
        }

        if (partSelect) {
            partSelect.addEventListener('change', (e) => fillLocation(e.target.value));
        }
    })();
</script>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
