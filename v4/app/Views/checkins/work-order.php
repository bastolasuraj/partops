<?php
// app/Views/checkins/work-order.php
// Return multiple parts from a work order back into inventory (treated as check-in)
?>
<div class="mb-6">
    <h1 class="text-3xl font-bold text-dark mb-2">Work Order Return</h1>
    <p class="text-gray-600">Receive unused parts from a work order back into stock.</p>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="<?= url('/checkins/work-order') ?>" class="space-y-6">
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
        </div>

        <div class="mt-6 hidden" id="parts-section">
            <h3 class="text-lg font-bold text-dark mb-3">Parts to Return</h3>
            <p class="text-sm text-gray-600 mb-4">Enter quantities for items being returned. Leave 0 or empty for items used.</p>

            <div id="parts-container" class="space-y-4">
                <!-- Dynamic rows will be inserted here -->
            </div>
            
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Return Notes</label>
                <textarea name="notes" rows="3" 
                          placeholder="Enter any notes regarding this return..."
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
            </div>

            <div class="flex gap-4 pt-6">
                <button type="submit" class="bg-primary text-dark px-6 py-2 rounded-lg hover:bg-secondary transition">
                    Record Return
                </button>
                <a href="<?= url('/checkins') ?>" class="bg-gray-200 text-dark px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</div>

<script>
function loadWOParts() {
    const woSelect = document.getElementById('work_order_id');
    const partsSection = document.getElementById('parts-section');
    const container = document.getElementById('parts-container');
    const woId = woSelect.value;

    if (!woId) {
        partsSection.classList.add('hidden');
        container.innerHTML = '';
        return;
    }

    // Visual feedback
    container.innerHTML = '<p class="text-gray-500 py-4 text-center">Loading parts...</p>';
    partsSection.classList.remove('hidden');

    fetch('<?= url('/api/work-orders/') ?>' + woId + '/parts')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(payload => {
            container.innerHTML = ''; // Clear loading
            const parts = payload.parts || [];

            if (parts.length === 0) {
                container.innerHTML = '<p class="text-gray-500 py-4 text-center">No parts found for this work order.</p>';
                return;
            }

            parts.forEach((part, index) => {
                const qty = part.total_checked_out ?? part.net_quantity ?? part.quantity ?? 0;
                if (qty <= 0) return; // Skip items with no quantity (or fully returned if logic supported it)

                const row = document.createElement('div');
                row.className = 'grid grid-cols-1 md:grid-cols-12 gap-4 p-4 bg-gray-50 rounded-lg items-center border border-gray-200';
                row.innerHTML = `
                    <div class="md:col-span-9">
                        <div class="font-bold text-dark">${part.fowler_part_number}</div>
                        <div class="text-sm text-gray-800">${part.part_name}</div>
                        <div class="text-xs text-gray-500 mt-1">
                            Checked Out: <span class="font-mono font-bold bg-white px-2 py-0.5 rounded border">${qty}</span>
                        </div>
                        <input type="hidden" name="parts[${index}][part_id]" value="${part.part_id}">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Return Qty</label>
                        <input type="number" name="parts[${index}][return_quantity]" 
                               min="0" max="${qty}" placeholder="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                `;
                container.appendChild(row);
            });
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = '<p class="text-danger py-4 text-center">Failed to load parts. Please try again.</p>';
        });
}
</script>
