<?php
// app/Views/returns/work-order.php
// Work order return form
?>
<div class="mb-6">
    <h1 class="text-3xl font-bold text-dark mb-2">Work Order Return</h1>
    <p class="text-gray-600">Return parts from a work order back to inventory</p>
</div>

<div class="bg-white rounded-lg shadow p-6">
<form method="POST" action="<?= url('/returns/work-order') ?>" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Work Order Selection -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Work Order <span class="text-danger">*</span>
                </label>
                <select name="work_order_id" id="work_order_id" required onchange="loadWOParts()"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Select Work Order</option>
                    <?php foreach (($workOrders ?? []) as $wo): ?>
                        <option value="<?= (int)$wo['id'] ?>"
                                <?= isset($_GET['wo_id']) && (int)$_GET['wo_id']===(int)$wo['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($wo['wo_number']) ?> - <?= htmlspecialchars($wo['technician_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Part Selection (populated based on WO) -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Part <span class="text-danger">*</span>
                </label>
                <select name="part_id" id="part_id" required onchange="updatePartInfo()"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Select part from work order</option>
                    <?php if (isset($_GET['part_id']) && !empty($woParts)): ?>
                        <?php foreach ($woParts as $p): ?>
                            <option value="<?= (int)$p['part_id'] ?>" 
                                    data-checked-out="<?= (int)$p['quantity'] ?>"
                                    <?= (int)$_GET['part_id']===(int)$p['part_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['fowler_part_number']) ?> - <?= htmlspecialchars($p['part_name']) ?>
                                (Checked out: <?= (int)$p['quantity'] ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <div id="part-info" class="mt-2 text-sm text-gray-600 hidden">
                    <span class="font-semibold">Checked out quantity:</span> <span id="checked-out-qty">0</span>
                </div>
            </div>

            <!-- Return Quantity -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Return Quantity <span class="text-danger">*</span>
                </label>
                <input type="number" name="return_quantity" id="return_quantity" min="1" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <!-- Required Quantity (for partial returns) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Required Quantity
                    <span class="text-xs text-gray-500">(if keeping some)</span>
                </label>
                <input type="number" name="required_quantity" min="0"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <!-- Notes -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Reason for Return
                </label>
                <textarea name="notes" rows="3"
                          placeholder="e.g., Job cancelled, Wrong part ordered, etc."
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
function loadWOParts() {
    const woSelect = document.getElementById('work_order_id');
    const partSelect = document.getElementById('part_id');
    const woId = woSelect.value;
    
    if (!woId) {
        partSelect.innerHTML = '<option value="">Select part from work order</option>';
        return;
    }
    
    // Fetch parts for this work order via API
    fetch(`/api/work-orders/${woId}/parts`)
        .then(response => response.json())
        .then(data => {
            partSelect.innerHTML = '<option value="">Select part</option>';
            data.forEach(part => {
                const option = document.createElement('option');
                option.value = part.part_id;
                option.setAttribute('data-checked-out', part.quantity);
                option.textContent = `${part.fowler_part_number} - ${part.part_name} (Checked out: ${part.quantity})`;
                partSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading parts:', error);
            alert('Failed to load parts for this work order');
        });
}

function updatePartInfo() {
    const select = document.getElementById('part_id');
    const option = select.options[select.selectedIndex];
    if (option.value) {
        const checkedOut = option.getAttribute('data-checked-out');
        document.getElementById('checked-out-qty').textContent = checkedOut;
        document.getElementById('part-info').classList.remove('hidden');
        document.getElementById('return_quantity').max = checkedOut;
    } else {
        document.getElementById('part-info').classList.add('hidden');
    }
}

// Initialize if pre-selected
document.addEventListener('DOMContentLoaded', function() {
    const woSelect = document.getElementById('work_order_id');
    if (woSelect.value) {
        loadWOParts();
    }
});
</script>
