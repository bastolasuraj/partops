<?php
// app/Views/work-orders/index.php
// List work orders with search and filters
?>
<div class="mb-6">
    <h1 class="text-3xl font-bold text-dark mb-2">Work Orders</h1>
    <p class="text-gray-600">Track work orders and associated parts</p>
</div>

<div class="mb-6 flex flex-col sm:flex-row gap-4 sm:items-end">
    <div class="flex-1 grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
            <input type="text" id="searchQuery" value="<?= htmlspecialchars($q ?? '') ?>"
                   placeholder="Search by WO #, Technician, or Unit..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
            <input type="date" id="dateFrom" value="<?= htmlspecialchars($dateFrom ?? '') ?>"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
            <input type="date" id="dateTo" value="<?= htmlspecialchars($dateTo ?? '') ?>"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
    </div>
    <div class="flex gap-3">
        <a href="<?= url('/work-orders') ?>" class="bg-gray-200 text-dark px-6 py-2 rounded-lg hover:bg-gray-300 whitespace-nowrap">Reset</a>
    </div>
</div>
<div id="dateError" class="text-danger text-sm mb-4 hidden">From Date cannot be later than To Date.</div>

<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-dark">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">WO Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Technician</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Unit Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Created</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Actions</th>
                </tr>
            </thead>
            <tbody id="wo-table-body" class="bg-white divide-y divide-gray-200 transition-opacity duration-200">
                <?php include __DIR__ . '/partials/rows.php'; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchQuery');
    const dateFromInput = document.getElementById('dateFrom');
    const dateToInput = document.getElementById('dateTo');
    const tableBody = document.getElementById('wo-table-body');
    const dateError = document.getElementById('dateError');

    // Debounce function to limit API calls
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    function validateDates() {
        const from = dateFromInput.value;
        const to = dateToInput.value;

        if (from && to && new Date(from) > new Date(to)) {
            dateError.classList.remove('hidden');
            return false;
        }
        dateError.classList.add('hidden');
        return true;
    }

    const performSearch = debounce(function() {
        if (!validateDates()) return;

        const q = searchInput.value;
        const from = dateFromInput.value;
        const to = dateToInput.value;

        // Visual feedback
        tableBody.classList.add('opacity-50');

        const params = new URLSearchParams({
            ajax: 1,
            q: q,
            date_from: from,
            date_to: to
        });

        fetch('<?= url('/work-orders') ?>?' + params.toString())
            .then(response => response.text())
            .then(html => {
                tableBody.innerHTML = html;
                tableBody.classList.remove('opacity-50');
            })
            .catch(error => {
                console.error('Error fetching work orders:', error);
                tableBody.classList.remove('opacity-50');
            });
    }, 300); // 300ms delay

    searchInput.addEventListener('input', performSearch);
    dateFromInput.addEventListener('change', performSearch);
    dateToInput.addEventListener('change', performSearch);
});
</script>
