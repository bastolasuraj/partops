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
                <i class="fas fa-box-open text-purple-600 mr-3"></i>Checkout Parts
            </h1>
        </div>
        
        <form action="<?= url('/inventory/checkout') ?>" method="POST" class="space-y-6">
            <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="idempotency_key" value="<?= $idempotency_key ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Part Selection -->
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

                <!-- Location Entry -->
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

            <!-- Context -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                <div>
                    <label for="work_order_ref">Work Order #</label>
                    <input type="text" name="work_order_ref" id="work_order_ref" required placeholder="e.g. WO-2045">
                </div>
                <div>
                    <label for="technician_id">Technician</label>
                    <select name="technician_id" id="technician_id" required>
                        <option value="">-- Select Tech --</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?= $tech['id'] ?>"><?= htmlspecialchars($tech['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label for="unit_number">Unit Number / Ref</label>
                    <input type="text" name="unit_number" id="unit_number" placeholder="e.g. Unit-123">
                </div>
            </div>

            <div>
                <label for="qty">Quantity</label>
                <input type="number" name="qty" id="qty" min="1" required placeholder="1" class="font-bold text-lg">
                <p class="text-xs text-gray-500 mt-1">Stock availability will be verified.</p>
            </div>

            <div>
                <label for="notes">Notes</label>
                <textarea name="notes" id="notes" rows="2" placeholder="Installation notes, special handling..."></textarea>
            </div>

            <div class="pt-4">
                <button type="submit" class="btn w-full justify-center text-lg bg-purple-600 hover:bg-purple-700">
                    <i class="fas fa-check mr-2"></i>Process Checkout
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
