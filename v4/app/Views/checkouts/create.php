<?php
// app/Views/checkouts/create.php
// Checkout form with free-text WO/technician and supplier-part dropdowns per part
?>
<div class="mb-6">
    <h1 class="text-3xl font-bold text-dark mb-2">Checkout Parts</h1>
    <p class="text-gray-600">Issue parts to a work order</p>
</div>

<div class="bg-white rounded-lg shadow p-6">
<form method="POST" action="<?= url('/checkouts') ?>" class="space-y-6">
        <!-- Work Order / Technician / Unit -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-6 border-b border-gray-200">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Work Order Number <span class="text-danger">*</span>
                </label>
                <input type="text" name="work_order_number" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       style="text-transform: uppercase;"
                       oninput="this.value = this.value.toUpperCase();"
                       placeholder="WO-12345">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Technician Name
                </label>
                <select name="technician_name"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Select technician</option>
                    <?php foreach (($technicians ?? []) as $tech): ?>
                        <option value="<?= htmlspecialchars($tech['name']) ?>">
                            <?= htmlspecialchars($tech['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-500 mt-1">Loaded from technician table</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Unit Number <span class="text-danger">*</span>
                </label>
                <input type="text" name="unit_number" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus-border-transparent"
                       placeholder="Unit number">
            </div>
        </div>

        <!-- Parts Section -->
        <div>
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-dark">Parts to Checkout</h3>
                <button type="button" onclick="addPartRow()" 
                        class="bg-success text-dark px-4 py-2 rounded-lg hover:bg-green-400 transition">
                    + Add Part
                </button>
            </div>

            <div id="parts-container" class="space-y-4">
                <!-- Initial part row -->
                <div class="part-row grid grid-cols-1 md:grid-cols-12 gap-4 p-4 bg-gray-50 rounded-lg">
                    <div class="md:col-span-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Part <span class="text-danger">*</span>
                        </label>
                        <select name="parts[0][part_id]" required onchange="updatePartSelection(this, 0)"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus-border-transparent">
                            <option value="">Select Part</option>
                            <?php foreach (($parts ?? []) as $p): ?>
                                <?php 
                                    $pid = isset($p['id']) ? (int)$p['id'] : (int)$p['part_id'];
                                    $pname = $p['name'] ?? ($p['part_name'] ?? '');
                                    $onHand = (int)($p['on_hand_quantity'] ?? ($p['on_hand'] ?? 0));
                                ?>
                                <option value="<?= $pid ?>" data-stock="<?= $onHand ?>">
                                    <?= htmlspecialchars($p['fowler_part_number']) ?> - <?= htmlspecialchars($pname) ?> 
                                    (Stock: <?= $onHand ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="stock-info-0 mt-1 text-sm text-gray-600 hidden">
                            Available: <span class="font-semibold stock-value-0">0</span>
                        </div>
                    </div>
                    <div class="md:col-span-4 hidden" id="supplier-container-0">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Supplier Part (required when multiple suppliers)
                        </label>
                        <select name="parts[0][supplier_part_number]" id="supplier-select-0"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus-border-transparent">
                            <option value="">Select supplier part</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Quantity <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="parts[0][quantity]" min="1" required oninput="validateQuantity(this)"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus-border-transparent quantity-input">
                        <p class="text-xs text-danger mt-1 hidden quantity-error"></p>
                    </div>
                    <div class="md:col-span-1 flex items-end">
                        <button type="button" onclick="removePartRow(this)" 
                                class="w-full bg-danger text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-4 pt-4">
            <button type="submit" class="bg-primary text-dark px-6 py-2 rounded-lg hover:bg-secondary transition">
                Checkout Parts
            </button>
            <a href="<?= url('/checkouts') ?>" class="bg-gray-200 text-dark px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
let partRowIndex = 1;
const supplierMap = <?= json_encode($supplierMap ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

function validateQuantity(input) {
    const row = input.closest('.part-row');
    const quantity = parseInt(input.value) || 0;
    
    // Find the displayed available stock (this is updated by updatePartSelection)
    // The index logic is a bit brittle if relying on classes like stock-value-0. 
    // Better to find the span within the row.
    // However, the current HTML structure has indexed classes: stock-value-${index}
    // We can extract the index from the input name "parts[index][quantity]"
    const nameMatch = input.name.match(/parts\[(\d+)\]/);
    if (!nameMatch) return;
    const index = nameMatch[1];
    
    const stockSpan = row.querySelector(`.stock-value-${index}`);
    const maxStock = parseInt(stockSpan.textContent) || 0;
    const errorMsg = row.querySelector('.quantity-error');
    const submitBtn = document.querySelector('button[type="submit"]');

    if (quantity > maxStock) {
        input.classList.add('border-danger', 'focus:ring-danger');
        input.classList.remove('focus:ring-primary');
        errorMsg.textContent = `Cannot checkout ${quantity}. Only ${maxStock} in stock.`;
        errorMsg.classList.remove('hidden');
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        input.classList.remove('border-danger', 'focus:ring-danger');
        input.classList.add('focus:ring-primary');
        errorMsg.classList.add('hidden');
        
        // Only enable submit if ALL quantity inputs are valid
        const allInvalid = document.querySelectorAll('.quantity-input.border-danger');
        if (allInvalid.length === 0) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
}

function updatePartSelection(select, index) {
    const option = select.options[select.selectedIndex];
    const stock = option.getAttribute('data-stock') || 0;
    const stockInfo = document.querySelector('.stock-info-' + index);
    const stockValue = document.querySelector('.stock-value-' + index);
    const supplierSelect = document.getElementById('supplier-select-' + index);
    const supplierContainer = document.getElementById('supplier-container-' + index);
    const partId = select.value;

    // Helper to update stock display
    const setStockDisplay = (val) => {
        if (stockValue) stockValue.textContent = val;
        // Trigger validation on the associated quantity input
        const qtyInput = document.querySelector(`input[name="parts[${index}][quantity]"]`);
        if (qtyInput && qtyInput.value) {
            validateQuantity(qtyInput);
        }
    };

    if (option.value && stockInfo && stockValue) {
        setStockDisplay(stock);
        stockInfo.classList.remove('hidden');
    } else if (stockInfo) {
        stockInfo.classList.add('hidden');
    }

    if (supplierSelect && supplierContainer) {
        supplierSelect.innerHTML = '<option value="">Select supplier part</option>';
        const suppliers = partId && supplierMap[partId] ? supplierMap[partId] : [];
        
        // Remove old event listeners to prevent duplicates (though simpler to just overwrite onchange prop)
        supplierSelect.onchange = function() {
            const selectedSupplierOption = this.options[this.selectedIndex];
            if (selectedSupplierOption && selectedSupplierOption.getAttribute('data-stock')) {
                setStockDisplay(selectedSupplierOption.getAttribute('data-stock'));
            } else {
                // Revert to total stock if no supplier selected (or custom logic)
                // For now, let's keep the total stock of the main part if "Select supplier part" is chosen
                setStockDisplay(stock);
            }
        };

        if (suppliers.length > 0) {
            suppliers.forEach(function(sn) {
                const opt = document.createElement('option');
                opt.value = sn.supplier_part_number;
                // Display stock in the dropdown for clarity
                const itemStock = sn.stock !== undefined ? sn.stock : 0;
                opt.textContent = sn.supplier_part_number + ' — ' + sn.supplier_name + ' (Stock: ' + itemStock + ')';
                opt.setAttribute('data-stock', itemStock);
                supplierSelect.appendChild(opt);
            });
            
            supplierContainer.classList.remove('hidden');
            supplierSelect.disabled = false;
            supplierSelect.required = true;

            // If only one supplier, auto-select it for convenience
            if (suppliers.length === 1) {
                supplierSelect.value = suppliers[0].supplier_part_number;
                // Trigger the manual update since onchange doesn't fire on programmatic change
                setStockDisplay(suppliers[0].stock);
            }
        } else {
            supplierContainer.classList.add('hidden');
            supplierSelect.required = false;
            supplierSelect.disabled = true;
            supplierSelect.value = '';
            // If returning to no-supplier state (e.g. part change), revert stock display
            setStockDisplay(stock);
        }
    }
}

function addPartRow() {
    const container = document.getElementById('parts-container');
    const newRow = document.createElement('div');
    newRow.className = 'part-row grid grid-cols-1 md:grid-cols-12 gap-4 p-4 bg-gray-50 rounded-lg';
    newRow.innerHTML = `
        <div class="md:col-span-5">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Part <span class="text-danger">*</span>
            </label>
            <select name="parts[${partRowIndex}][part_id]" required onchange="updatePartSelection(this, ${partRowIndex})"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus-border-transparent">
                <option value="">Select Part</option>
                <?php foreach (($parts ?? []) as $p): ?>
                    <?php 
                        $pid = isset($p['id']) ? (int)$p['id'] : (int)$p['part_id'];
                        $pname = $p['name'] ?? ($p['part_name'] ?? '');
                        $onHand = (int)($p['on_hand_quantity'] ?? ($p['on_hand'] ?? 0));
                    ?>
                    <option value="<?= $pid ?>" data-stock="<?= $onHand ?>">
                        <?= htmlspecialchars($p['fowler_part_number']) ?> - <?= htmlspecialchars($pname) ?> 
                        (Stock: <?= $onHand ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="stock-info-${partRowIndex} mt-1 text-sm text-gray-600 hidden">
                Available: <span class="font-semibold stock-value-${partRowIndex}">0</span>
            </div>
        </div>
        <div class="md:col-span-4 hidden" id="supplier-container-${partRowIndex}">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Supplier Part (required when multiple suppliers)
            </label>
            <select name="parts[${partRowIndex}][supplier_part_number]" id="supplier-select-${partRowIndex}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus-border-transparent">
                <option value="">Select supplier part</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Quantity <span class="text-danger">*</span>
            </label>
            <input type="number" name="parts[${partRowIndex}][quantity]" min="1" required oninput="validateQuantity(this)"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus-border-transparent quantity-input">
            <p class="text-xs text-danger mt-1 hidden quantity-error"></p>
        </div>
        <div class="md:col-span-1 flex items-end">
            <button type="button" onclick="removePartRow(this)" 
                    class="w-full bg-danger text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                Remove
            </button>
        </div>
    `;
    container.appendChild(newRow);
    partRowIndex++;
}

function removePartRow(button) {
    const container = document.getElementById('parts-container');
    if (container.children.length > 1) {
        button.closest('.part-row').remove();
    } else {
        alert('At least one part is required');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const initialPartSelect = document.querySelector('select[name="parts[0][part_id]"]');
    if (initialPartSelect) {
        updatePartSelection(initialPartSelect, 0);
    }
});
</script>
