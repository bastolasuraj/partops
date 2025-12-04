<?php
// app/Views/checkins/index.php
// Recent check-ins list
?>
<div class="mb-6">
    <h1 class="text-3xl font-bold text-dark mb-2">Parts Check-ins</h1>
    <p class="text-gray-600">View recent parts received from suppliers or work order returns</p>
</div>

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div class="flex gap-3">
        <a href="<?= url('/checkins/create') ?>" class="inline-block bg-primary text-dark px-6 py-2 rounded-lg hover:bg-secondary">
            New Check-in
        </a>
        <a href="<?= url('/checkins/work-order') ?>" class="inline-block bg-dark text-white px-6 py-2 rounded-lg hover:bg-gray-800">
            WO Return
        </a>
    </div>
</div>

<div class="mb-6 flex flex-col sm:flex-row gap-4 sm:items-end bg-gray-50 p-4 rounded-lg border border-gray-200">
    <div class="flex-1 grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
            <input type="text" id="searchQuery" value="<?= htmlspecialchars($q ?? '') ?>"
                   placeholder="Part Name, Fowler #, Supplier, or WO #..."
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
        <a href="<?= url('/checkins') ?>" class="bg-white border border-gray-300 text-dark px-6 py-2 rounded-lg hover:bg-gray-100 whitespace-nowrap">Reset</a>
    </div>
</div>
<div id="dateError" class="text-danger text-sm mb-4 hidden">From Date cannot be later than To Date.</div>

<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-dark">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Part</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Source</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Supplier Part #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Core Charge</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Date</th>
                </tr>
            </thead>
            <tbody id="checkin-table-body" class="bg-white divide-y divide-gray-200 transition-opacity duration-200">
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
    const tableBody = document.getElementById('checkin-table-body');
    const dateError = document.getElementById('dateError');

    // Debounce function
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

        fetch('<?= url('/checkins') ?>?' + params.toString())
            .then(response => response.text())
            .then(html => {
                tableBody.innerHTML = html;
                tableBody.classList.remove('opacity-50');
            })
            .catch(error => {
                console.error('Error fetching checkins:', error);
                tableBody.classList.remove('opacity-50');
            });
    }, 300);

    searchInput.addEventListener('input', performSearch);
    dateFromInput.addEventListener('change', performSearch);
    dateToInput.addEventListener('change', performSearch);
});
</script>
