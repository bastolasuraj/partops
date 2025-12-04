<?php
// app/Views/checkins/create.php
// Check-in form with core charge toggle
?>
<div class="mb-6">
    <h1 class="text-3xl font-bold text-dark mb-2">Check-in Parts</h1>
    <p class="text-gray-600">Receive parts from supplier</p>
</div>

<div class="bg-white rounded-lg shadow p-6">
<form method="POST" action="<?= url('/checkins') ?>" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Part Selection -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Part <span class="text-danger">*</span>
                </label>
                <select name="part_id" id="part_id" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Select Part</option>
                    <?php foreach (($parts ?? []) as $p): ?>
                        <option value="<?= (int)$p['id'] ?>">
                            <?= htmlspecialchars($p['fowler_part_number']) ?> - <?= htmlspecialchars($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Supplier (auto-filled from part) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Supplier <span class="text-danger">*</span>
                </label>
                <select name="supplier_id" id="supplier_id" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Select Supplier</option>
                    <?php foreach (($suppliers ?? []) as $s): ?>
                        <option value="<?= (int)$s['id'] ?>">
                            <?= htmlspecialchars($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Supplier Part Number -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Supplier Part Number
                </label>
                <input type="text" name="supplier_part_number"
                       placeholder="Supplier's SKU"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <!-- Location: Aisle -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Aisle
                </label>
                <input type="text" name="location_aisle"
                       placeholder="e.g., A"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <!-- Location: Shelf -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Shelf
                </label>
                <input type="text" name="location_shelf"
                       placeholder="e.g., 3"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus-border-transparent">
            </div>

            <!-- Location: Bay -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Bay
                </label>
                <input type="text" name="location_bay"
                       placeholder="e.g., 12"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus-ring-primary focus:border-transparent">
            </div>

            <!-- Quantity -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Quantity <span class="text-danger">*</span>
                </label>
                <input type="number" name="quantity" min="1" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <!-- Price -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Price per Unit
                </label>
                <input type="number" name="price" step="0.01" min="0"
                       placeholder="0.00"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <!-- Core Charge Toggle -->
            <div class="md:col-span-2">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="has_core_charge" id="has_core_charge" 
                           onchange="toggleCoreCharge()"
                           class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-2 focus:ring-primary">
                    <span class="text-sm font-medium text-gray-700">This part has a core charge</span>
                </label>
            </div>

            <!-- Expected Rebate (shown when core charge is checked) -->
            <div id="rebate-field" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Expected Core Rebate
                </label>
                <input type="number" name="expected_rebate" step="0.01" min="0"
                       placeholder="0.00"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <!-- Notes -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Notes
                </label>
                <textarea name="notes" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
            </div>
        </div>

        <div class="flex gap-4 pt-4">
            <button type="submit" class="bg-primary text-dark px-6 py-2 rounded-lg hover:bg-secondary transition">
                Check-in Parts
            </button>
            <a href="<?= url('/checkins') ?>" class="bg-gray-200 text-dark px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
function toggleCoreCharge() {
    const checkbox = document.getElementById('has_core_charge');
    const rebateField = document.getElementById('rebate-field');
    if (checkbox.checked) {
        rebateField.classList.remove('hidden');
    } else {
        rebateField.classList.add('hidden');
    }
}

</script>
