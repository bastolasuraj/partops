<?php ob_start(); ?>

<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="<?= url('/inventory') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Inventory
        </a>
    </div>

    <div class="bg-white p-8 rounded-xl shadow-md border border-gray-100">
        <h1 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-sliders-h text-gray-600 mr-3"></i>Inventory Adjustments
        </h1>
        
        <div class="mb-6 text-sm text-gray-500 bg-blue-50 p-4 rounded-lg border border-blue-100">
            <i class="fas fa-info-circle mr-2 text-blue-600"></i>
            Use this form to correct stock levels, update catalog definitions (part numbers, suppliers), or handle supersessions.
        </div>

        <form action="<?= url('/inventory/adjust') ?>" method="POST" class="space-y-6">
            <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
            
            <!-- Part Selection -->
            <div>
                <label for="part_id">Part</label>
                <select name="part_id" id="part_id" required class="font-mono">
                    <option value="">Select Part...</option>
                    <?php foreach ($parts as $part): ?>
                        <option value="<?= $part['id'] ?>">
                            <?= htmlspecialchars($part['anchor_slug']) ?> - <?= htmlspecialchars($part['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Adjustment Type Selector -->
            <div>
                <label for="adjustment_type">Action Type</label>
                <select name="adjustment_type" id="adjustment_type" required onchange="toggleSections(this.value)">
                    <option value="quantity">Stock Quantity Correction</option>
                    <option value="part_number">Add Part Number / Supersession</option>
                    <option value="supplier">Update Supplier / Catalog</option>
                </select>
            </div>

            <!-- Section: Stock Quantity -->
            <div id="sec-quantity" class="adjustment-section space-y-4 border-l-4 border-gray-300 pl-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="location_id">Location</label>
                        <select name="location_id" id="location_id">
                            <option value="">Select Location...</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= $loc['id'] ?>">
                                    <?= htmlspecialchars($loc['aisle'] . '-' . $loc['shelf'] . '-' . $loc['bay']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="qty_delta">Adjustment (+/-)</label>
                        <input type="number" name="qty_delta" id="qty_delta" placeholder="-1 or 5">
                        <p class="text-xs text-gray-500 mt-1">Negative to remove, positive to add.</p>
                    </div>
                </div>
            </div>

            <!-- Section: Part Number -->
            <div id="sec-part_number" class="adjustment-section hidden space-y-4 border-l-4 border-blue-300 pl-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="pn_value">New Part Number</label>
                        <input type="text" name="pn_value" id="pn_value" placeholder="e.g. ABC-U-123">
                    </div>
                    <div>
                        <label for="pn_type">Type</label>
                        <select name="pn_type" id="pn_type">
                            <option value="active">Active (Supersession)</option>
                            <option value="aftermarket">Aftermarket (Alternate)</option>
                            <option value="historical">Historical</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="pn_mfr">Manufacturer</label>
                        <input type="text" name="pn_mfr" id="pn_mfr" placeholder="e.g. ABC Motors">
                    </div>
                </div>
            </div>

            <!-- Section: Supplier -->
            <div id="sec-supplier" class="adjustment-section hidden space-y-4 border-l-4 border-green-300 pl-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="supplier_id">Supplier</label>
                        <select name="supplier_id" id="supplier_id">
                            <option value="">Select Supplier...</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="supplier_sku">Supplier SKU</label>
                        <input type="text" name="supplier_sku" id="supplier_sku" placeholder="e.g. SUP-999">
                    </div>
                    <div>
                        <label for="price">New Price</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">$</div>
                            <input type="number" name="price" id="price" step="0.01" class="pl-7">
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label for="notes">Reason / Notes</label>
                <textarea name="notes" id="notes" rows="2" placeholder="Why is this adjustment being made?"></textarea>
            </div>

            <div class="pt-4">
                <button type="submit" class="btn w-full justify-center text-lg bg-gray-700 hover:bg-gray-800">
                    <i class="fas fa-save mr-2"></i>Process Adjustment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSections(type) {
    document.querySelectorAll('.adjustment-section').forEach(el => el.classList.add('hidden'));
    const active = document.getElementById('sec-' + type);
    if (active) active.classList.remove('hidden');
}
// Init
toggleSections(document.getElementById('adjustment_type').value);
</script>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
