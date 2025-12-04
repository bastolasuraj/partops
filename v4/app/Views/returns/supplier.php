<?php
// app/Views/returns/supplier.php
// Supplier return form with core charge toggle
?>
<div class="mb-6">
    <h1 class="text-3xl font-bold text-dark mb-2">Supplier Return</h1>
    <p class="text-gray-600">Return defective parts or cores to supplier</p>
</div>

<div class="bg-white rounded-lg shadow p-6">
<form method="POST" action="<?= url('/returns/supplier') ?>" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Supplier Selection -->
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
                    Supplier Part Number <span class="text-danger">*</span>
                </label>
                <input type="text" name="supplier_part_number" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <!-- Part (optional - for reference) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Part (Optional)
                </label>
                <select name="part_id" id="part_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Select if applicable</option>
                    <?php foreach (($parts ?? []) as $p): ?>
                        <option value="<?= (int)$p['id'] ?>">
                            <?= htmlspecialchars($p['fowler_part_number']) ?> - <?= htmlspecialchars($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Quantity -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Quantity <span class="text-danger">*</span>
                </label>
                <input type="number" name="quantity" min="1" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <!-- Is Core Charge Return -->
            <div class="md:col-span-2">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_core_charge" id="is_core_charge" 
                           onchange="toggleCoreFields()"
                           <?= isset($_GET['core_id']) ? 'checked' : '' ?>
                           class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-2 focus:ring-primary">
                    <span class="text-sm font-medium text-gray-700">This is a core charge return</span>
                </label>
            </div>

            <!-- Core Charge Amount (shown when core charge is checked) -->
            <div id="core-amount-field" class="<?= isset($_GET['core_id']) ? '' : 'hidden' ?>">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Core Charge Amount
                </label>
                <input type="number" name="core_charge_amount" step="0.01" min="0"
                       placeholder="0.00"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <!-- Expected Rebate (shown when core charge is checked) -->
            <div id="expected-rebate-field" class="<?= isset($_GET['core_id']) ? '' : 'hidden' ?>">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Expected Rebate
                </label>
                <input type="number" name="expected_rebate" step="0.01" min="0"
                       placeholder="0.00"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <!-- Rebate Received (shown when core charge is checked) -->
            <div id="rebate-received-field" class="<?= isset($_GET['core_id']) ? '' : 'hidden' ?>">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Rebate Received
                </label>
                <input type="number" name="rebate_received" step="0.01" min="0"
                       placeholder="0.00"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <!-- Notes -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Notes
                </label>
                <textarea name="notes" rows="3"
                          placeholder="Reason for return, RMA number, etc."
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
            </div>
        </div>

        <div class="flex gap-4 pt-4">
            <button type="submit" class="bg-primary text-dark px-6 py-2 rounded-lg hover:bg-secondary transition">
                Process Return
            </button>
            <a href="<?= url('/returns') ?>" class="bg-gray-200 text-dark px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
function toggleCoreFields() {
    const checkbox = document.getElementById('is_core_charge');
    const coreAmountField = document.getElementById('core-amount-field');
    const expectedRebateField = document.getElementById('expected-rebate-field');
    const rebateReceivedField = document.getElementById('rebate-received-field');
    
    if (checkbox.checked) {
        coreAmountField.classList.remove('hidden');
        expectedRebateField.classList.remove('hidden');
        rebateReceivedField.classList.remove('hidden');
    } else {
        coreAmountField.classList.add('hidden');
        expectedRebateField.classList.add('hidden');
        rebateReceivedField.classList.add('hidden');
    }
}
</script>
