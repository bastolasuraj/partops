<?php ob_start(); ?>

<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <a href="<?= url('/inventory') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Inventory
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form Column (2/3 width) -->
        <div class="lg:col-span-2 bg-white p-8 rounded-xl shadow-md border border-gray-100">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                <i class="fas fa-dolly text-blue-600 mr-3"></i>Receive Parts
            </h1>
        </div>
        
        <form action="<?= url('/inventory/receive') ?>" method="POST" class="space-y-6">
            <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="idempotency_key" value="<?= $idempotency_key ?>">
            
            <!-- Receipt Type Toggle -->
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 p-4 rounded-xl border-2 border-indigo-200">
                <label class="block mb-3 text-sm font-bold text-gray-800">Receipt Type</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative flex items-center p-4 bg-white rounded-xl border-2 border-gray-200 cursor-pointer hover:border-indigo-400 transition-all">
                        <input type="radio" name="receipt_type" value="new_stock" class="mr-3" checked onchange="toggleReceiptMode()">
                        <div>
                            <div class="font-semibold text-gray-900">New Stock</div>
                            <div class="text-xs text-gray-500">From supplier/vendor</div>
                        </div>
                    </label>
                    <label class="relative flex items-center p-4 bg-white rounded-xl border-2 border-gray-200 cursor-pointer hover:border-indigo-400 transition-all">
                        <input type="radio" name="receipt_type" value="tech_return" class="mr-3" onchange="toggleReceiptMode()">
                        <div>
                            <div class="font-semibold text-gray-900">WO Return</div>
                            <div class="text-xs text-gray-500">Unused parts returned</div>
                        </div>
                    </label>
                </div>
            </div>
            
            <!-- Main Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div id="part_field">
                    <label for="part_search">Part</label>
                    <input 
                        type="text" 
                        id="part_search" 
                        placeholder="Search parts..." 
                        autocomplete="off"
                        class="font-mono"
                        onfocus="showSelectColumn('parts')"
                        oninput="searchItems('parts', this.value)"
                    >
                    <input type="hidden" name="part_id" id="part_id" required>
                    <div id="part_selected" class="hidden mt-2 p-2 bg-indigo-50 rounded-lg text-sm">
                        <span class="font-semibold text-indigo-700"></span>
                    </div>
                </div>

                <div id="supplier_field">
                    <label for="supplier_search">Supplier</label>
                    <input 
                        type="text" 
                        id="supplier_search" 
                        placeholder="Search suppliers..." 
                        autocomplete="off"
                        onfocus="showSelectColumn('suppliers')"
                        oninput="searchItems('suppliers', this.value)"
                    >
                    <input type="hidden" name="supplier_id" id="supplier_id">
                    <div id="supplier_selected" class="hidden mt-2 p-2 bg-indigo-50 rounded-lg text-sm">
                        <span class="font-semibold text-indigo-700"></span>
                    </div>
                </div>

                <div id="supplier_sku_field">
                    <label for="supplier_sku">Manufacturer / Supplier Part #</label>
                    <input type="text" name="supplier_sku" id="supplier_sku" placeholder="e.g. ABC-123">
                    <p class="text-xs text-gray-500 mt-1">Required when a supplier is selected.</p>
                </div>

                <!-- Work Order Field (Hidden by default, shown inline when WO Return is selected) -->
                <div id="work_order_field" class="hidden">
                    <label for="work_order_search">Work Order #</label>
                    <input 
                        type="text" 
                        id="work_order_search" 
                        placeholder="Search work orders..." 
                        autocomplete="off"
                        onfocus="showSelectColumn('workorders')"
                        oninput="searchItems('workorders', this.value)"
                    >
                    <input type="hidden" id="work_order_selected_ref">
                    <div id="workorder_selected" class="hidden mt-2 p-2 bg-indigo-50 rounded-lg text-sm">
                        <span class="font-semibold text-indigo-700"></span>
                    </div>
                </div>
            </div>

            <!-- Work Order Parts List (Hidden by default) -->
            <div id="wo_parts_container" class="hidden">
                <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-box-open text-purple-600 mr-2"></i>
                        Parts Checked Out
                    </h3>
                    <div id="wo_parts_list" class="space-y-3">
                        <!-- Parts will be loaded here via JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Financials (Only for New Stock) -->
            <div id="financials_section" class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                <div>
                    <label for="price">Price</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">$</div>
                        <input type="number" name="price" id="price" step="0.01" min="0" placeholder="0.00" class="pl-7">
                    </div>
                </div>
                <div>
                    <label for="core_charge">Core Charge</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">$</div>
                        <input type="number" name="core_charge" id="core_charge" step="0.01" min="0" placeholder="0.00" class="pl-7">
                    </div>
                </div>
                <div>
                    <label for="expected_rebate">Expected Rebate</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">$</div>
                        <input type="number" name="expected_rebate" id="expected_rebate" step="0.01" min="0" placeholder="0.00" class="pl-7 text-green-700 font-semibold">
                    </div>
                </div>
            </div>

            <!-- Location & Qty -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="location_aisle">Location (Aisle / Shelf / Bay)</label>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <input type="text" name="location_aisle" id="location_aisle" placeholder="A1" required>
                        </div>
                        <div>
                            <input type="text" name="location_shelf" id="location_shelf" placeholder="S1" required>
                        </div>
                        <div>
                            <input type="text" name="location_bay" id="location_bay" placeholder="B1" required>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Example: A1 / S1 / B1</p>
                </div>

                <div>
                    <label for="qty">Quantity Received</label>
                    <input type="number" name="qty" id="qty" min="1" required placeholder="1" class="font-bold text-lg">
                </div>
            </div>

            <div>
                <label for="notes">Notes / PO Number</label>
                <textarea name="notes" id="notes" rows="3" placeholder="Optional notes..."></textarea>
            </div>

            <div class="pt-4">
                <button type="submit" class="btn w-full justify-center text-lg">
                    <i class="fas fa-check mr-2"></i>Process Receipt
                </button>
            </div>
        </form>
        </div>

        <!-- Select Items Column (1/3 width) -->
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 sticky top-24">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-list text-indigo-600 mr-2"></i>
                    Select Items
                </h3>
                
                <div id="select_items_container">
                    <div id="select_items_empty" class="text-center py-8 text-gray-400">
                        <i class="fas fa-hand-pointer text-4xl mb-3"></i>
                        <p class="text-sm">Click on a field to see available items</p>
                    </div>
                    
                    <div id="select_items_list" class="hidden space-y-2 max-h-[600px] overflow-y-auto">
                        <!-- Items will be populated here -->
                    </div>
                    
                    <div id="select_items_loading" class="hidden text-center py-8">
                        <i class="fas fa-spinner fa-spin text-3xl text-indigo-600"></i>
                        <p class="text-sm text-gray-600 mt-2">Loading...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Part locations data
    const partLocations = <?= json_encode($partLocations ?? []) ?>;
    const allParts = <?= json_encode($parts ?? []) ?>;
    const allSuppliers = <?= json_encode($suppliers ?? []) ?>;
    
    let currentSelectType = null;
    let searchTimeout = null;

    // Fill location when part is selected
    function fillLocation(partId) {
        const loc = partLocations[partId];
        const aisle = document.getElementById('location_aisle');
        const shelf = document.getElementById('location_shelf');
        const bay = document.getElementById('location_bay');
        
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

    // Show select column for specific type
    function showSelectColumn(type) {
        currentSelectType = type;
        const empty = document.getElementById('select_items_empty');
        const list = document.getElementById('select_items_list');
        const loading = document.getElementById('select_items_loading');
        
        empty.classList.add('hidden');
        loading.classList.remove('hidden');
        list.classList.add('hidden');
        
        // Trigger initial search
        const searchInput = type === 'parts' ? document.getElementById('part_search') : document.getElementById('supplier_search');
        searchItems(type, searchInput.value);
    }

    // Search items with debounce
    function searchItems(type, query) {
        clearTimeout(searchTimeout);
        
        searchTimeout = setTimeout(() => {
            if (type === 'parts') {
                displayParts(query);
            } else if (type === 'suppliers') {
                displaySuppliers(query);
            } else if (type === 'workorders') {
                displayWorkOrders(query);
            }
        }, 300);
    }

    // Display filtered parts
    function displayParts(query) {
        const list = document.getElementById('select_items_list');
        const loading = document.getElementById('select_items_loading');
        
        const filtered = allParts.filter(part => {
            const searchStr = query.toLowerCase();
            return part.anchor_slug.toLowerCase().includes(searchStr) ||
                   (part.name && part.name.toLowerCase().includes(searchStr));
        });
        
        list.innerHTML = '';
        
        if (filtered.length === 0) {
            list.innerHTML = '<div class="text-center py-4 text-gray-500 text-sm">No parts found</div>';
        } else {
            filtered.forEach(part => {
                const item = document.createElement('div');
                item.className = 'p-3 bg-gray-50 hover:bg-indigo-50 rounded-lg cursor-pointer border border-gray-200 hover:border-indigo-400 transition-all';
                item.innerHTML = `
                    <div class="font-semibold text-gray-800">${escapeHtml(part.anchor_slug)}</div>
                    ${part.name ? `<div class="text-xs text-gray-500 mt-1">${escapeHtml(part.name)}</div>` : ''}
                `;
                item.onclick = () => selectPart(part);
                list.appendChild(item);
            });
        }
        
        loading.classList.add('hidden');
        list.classList.remove('hidden');
    }

    // Display filtered suppliers
    function displaySuppliers(query) {
        const list = document.getElementById('select_items_list');
        const loading = document.getElementById('select_items_loading');
        
        const filtered = allSuppliers.filter(supplier => {
            const searchStr = query.toLowerCase();
            return supplier.name.toLowerCase().includes(searchStr);
        });
        
        list.innerHTML = '';
        
        if (filtered.length === 0) {
            list.innerHTML = '<div class="text-center py-4 text-gray-500 text-sm">No suppliers found</div>';
        } else {
            filtered.forEach(supplier => {
                const item = document.createElement('div');
                item.className = 'p-3 bg-gray-50 hover:bg-indigo-50 rounded-lg cursor-pointer border border-gray-200 hover:border-indigo-400 transition-all';
                item.innerHTML = `
                    <div class="font-semibold text-gray-800">${escapeHtml(supplier.name)}</div>
                    ${supplier.contact_email ? `<div class="text-xs text-gray-500 mt-1">${escapeHtml(supplier.contact_email)}</div>` : ''}
                `;
                item.onclick = () => selectSupplier(supplier);
                list.appendChild(item);
            });
        }
        
        loading.classList.add('hidden');
        list.classList.remove('hidden');
    }

    // Select a part
    function selectPart(part) {
        document.getElementById('part_id').value = part.id;
        document.getElementById('part_search').value = part.anchor_slug;
        
        const selectedDiv = document.getElementById('part_selected');
        selectedDiv.querySelector('span').textContent = part.anchor_slug;
        selectedDiv.classList.remove('hidden');
        
        // Fill location
        fillLocation(part.id);
        
        // Hide select column
        document.getElementById('select_items_list').classList.add('hidden');
        document.getElementById('select_items_empty').classList.remove('hidden');
    }

    // Select a supplier
    function selectSupplier(supplier) {
        document.getElementById('supplier_id').value = supplier.id;
        document.getElementById('supplier_search').value = supplier.name;
        
        const selectedDiv = document.getElementById('supplier_selected');
        selectedDiv.querySelector('span').textContent = supplier.name;
        selectedDiv.classList.remove('hidden');
        
        // Hide select column
        document.getElementById('select_items_list').classList.add('hidden');
        document.getElementById('select_items_empty').classList.remove('hidden');
    }

    // Display filtered work orders
    async function displayWorkOrders(query) {
        const list = document.getElementById('select_items_list');
        const loading = document.getElementById('select_items_loading');
        
        if (!query || query.length < 2) {
            list.innerHTML = '<div class="text-center py-4 text-gray-500 text-sm">Type at least 2 characters to search</div>';
            loading.classList.add('hidden');
            list.classList.remove('hidden');
            return;
        }
        
        try {
            const response = await fetch('<?= url('/inventory/search-work-orders') ?>?q=' + encodeURIComponent(query));
            const data = await response.json();
            
            list.innerHTML = '';
            
            if (data.error) {
                list.innerHTML = `<div class="text-center py-4 text-red-500 text-sm">${escapeHtml(data.error)}</div>`;
            } else if (!data.workorders || data.workorders.length === 0) {
                list.innerHTML = '<div class="text-center py-4 text-gray-500 text-sm">No work orders found</div>';
            } else {
                data.workorders.forEach(wo => {
                    const item = document.createElement('div');
                    item.className = 'p-3 bg-gray-50 hover:bg-indigo-50 rounded-lg cursor-pointer border border-gray-200 hover:border-indigo-400 transition-all';
                    item.innerHTML = `
                        <div class="font-semibold text-gray-800">${escapeHtml(wo.work_order_ref)}</div>
                        <div class="text-xs text-gray-500 mt-1">
                            ${wo.parts_count} part(s) checked out
                            ${wo.unit_number ? ` • Unit: ${escapeHtml(wo.unit_number)}` : ''}
                        </div>
                    `;
                    item.onclick = () => selectWorkOrder(wo);
                    list.appendChild(item);
                });
            }
            
            loading.classList.add('hidden');
            list.classList.remove('hidden');
        } catch (error) {
            console.error('Error:', error);
            list.innerHTML = '<div class="text-center py-4 text-red-500 text-sm">Failed to search work orders</div>';
            loading.classList.add('hidden');
            list.classList.remove('hidden');
        }
    }

    // Select a work order and load its parts
    async function selectWorkOrder(wo) {
        document.getElementById('work_order_selected_ref').value = wo.work_order_ref;
        document.getElementById('work_order_search').value = wo.work_order_ref;
        
        const selectedDiv = document.getElementById('workorder_selected');
        selectedDiv.querySelector('span').textContent = `${wo.work_order_ref} (${wo.parts_count} part${wo.parts_count !== 1 ? 's' : ''})`;
        selectedDiv.classList.remove('hidden');
        
        // Hide select column
        document.getElementById('select_items_list').classList.add('hidden');
        document.getElementById('select_items_empty').classList.remove('hidden');
        
        // Load work order parts
        await lookupWorkOrderByRef(wo.work_order_ref);
    }

    // Toggle between New Stock and Tech Return modes
    function toggleReceiptMode() {
        const receiptType = document.querySelector('input[name="receipt_type"]:checked').value;
        const workOrderField = document.getElementById('work_order_field');
        const financialsSection = document.getElementById('financials_section');
        const partField = document.getElementById('part_field');
        const supplierField = document.getElementById('supplier_field');
        const supplierSkuField = document.getElementById('supplier_sku_field');
        const partSelect = document.getElementById('part_id');
        const qtyInput = document.getElementById('qty');
        const locationAisle = document.getElementById('location_aisle');
        const locationShelf = document.getElementById('location_shelf');
        const locationBay = document.getElementById('location_bay');
        
        if (receiptType === 'tech_return') {
            // Show work order field inline
            workOrderField.classList.remove('hidden');
            // Hide part, supplier, financial, and manual entry fields
            partField.classList.add('hidden');
            financialsSection.classList.add('hidden');
            supplierField.classList.add('hidden');
            supplierSkuField.classList.add('hidden');
            // Disable manual fields (will be auto-filled from WO)
            partSelect.disabled = true;
            qtyInput.disabled = true;
            locationAisle.disabled = true;
            locationShelf.disabled = true;
            locationBay.disabled = true;
        } else {
            // Hide work order fields
            workOrderField.classList.add('hidden');
            document.getElementById('wo_parts_container').classList.add('hidden');
            // Show part, supplier and financial fields
            partField.classList.remove('hidden');
            financialsSection.classList.remove('hidden');
            supplierField.classList.remove('hidden');
            supplierSkuField.classList.remove('hidden');
            // Enable manual fields
            partSelect.disabled = false;
            qtyInput.disabled = false;
            locationAisle.disabled = false;
            locationShelf.disabled = false;
            locationBay.disabled = false;
        }
    }
    
    // Lookup work order by reference
    async function lookupWorkOrderByRef(woNumber) {
        if (!woNumber) {
            alert('Please select a work order');
            return;
        }
        
        try {
            const response = await fetch('<?= url('/inventory/work-order-parts') ?>?wo=' + encodeURIComponent(woNumber));
            const data = await response.json();
            
            if (data.error) {
                alert(data.error);
                return;
            }
            
            if (!data.parts || data.parts.length === 0) {
                alert('No parts found for this work order');
                return;
            }
            
            displayWorkOrderParts(data.parts, woNumber);
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to lookup work order');
        }
    }
    
    // Display work order parts with return quantity inputs
    function displayWorkOrderParts(parts, woNumber) {
        const container = document.getElementById('wo_parts_container');
        const partsList = document.getElementById('wo_parts_list');
        
        partsList.innerHTML = '';
        
        parts.forEach((part, index) => {
            const partRow = document.createElement('div');
            partRow.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200';
            partRow.innerHTML = `
                <div class="flex-1">
                    <div class="font-semibold text-gray-800">${escapeHtml(part.anchor_slug)}</div>
                    <div class="text-xs text-gray-500">
                        Location: ${escapeHtml(part.aisle)}/${escapeHtml(part.shelf)}/${escapeHtml(part.bay)} • 
                        Checked out: ${part.qty_checked_out}
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <label class="text-sm text-gray-600">Return Qty:</label>
                    <input 
                        type="number" 
                        id="return_qty_${index}"
                        min="0" 
                        max="${part.qty_checked_out}" 
                        value="0"
                        class="w-20 px-3 py-2 border-2 border-gray-300 rounded-lg text-center font-bold"
                        data-part-id="${part.part_id}"
                        data-location-id="${part.location_id}"
                        data-max="${part.qty_checked_out}"
                        data-wo="${escapeHtml(woNumber)}"
                        data-unit="${escapeHtml(part.unit_number || '')}"
                    >
                </div>
            `;
            partsList.appendChild(partRow);
        });
        
        container.classList.remove('hidden');
    }
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Override form submission for work order returns
    document.querySelector('form').addEventListener('submit', async function(e) {
        const receiptType = document.querySelector('input[name="receipt_type"]:checked').value;
        
        if (receiptType === 'tech_return') {
            e.preventDefault();
            
            // Collect all return quantities
            const returns = [];
            const inputs = document.querySelectorAll('[id^="return_qty_"]');
            
            inputs.forEach(input => {
                const qty = parseInt(input.value) || 0;
                if (qty > 0) {
                    returns.push({
                        part_id: input.dataset.partId,
                        location_id: input.dataset.locationId,
                        qty: qty,
                        work_order_ref: input.dataset.wo,
                        unit_number: input.dataset.unit
                    });
                }
            });
            
            if (returns.length === 0) {
                alert('Please enter at least one return quantity greater than 0');
                return;
            }
            
            // Submit via fetch
            try {
                const formData = new FormData();
                formData.append('_csrf_token', document.querySelector('[name="_csrf_token"]').value);
                formData.append('idempotency_key', document.querySelector('[name="idempotency_key"]').value);
                formData.append('receipt_type', 'tech_return');
                formData.append('returns', JSON.stringify(returns));
                formData.append('notes', document.getElementById('notes').value);
                
                const response = await fetch('<?= url('/inventory/receive') ?>', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.error) {
                    alert(result.error);
                } else {
                    window.location.href = '<?= url('/inventory') ?>';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to process returns');
            }
        }
    });
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', toggleReceiptMode);
</script>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
