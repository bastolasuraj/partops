<?php
// app/Views/parts/edit.php
// Edit part form
?>
<div class="mb-6">
    <h1 class="text-3xl font-bold text-dark mb-2">Edit Part</h1>
    <p class="text-gray-600">Update part details</p>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="<?= url('/parts/' . (int)$part['id'] . '/update') ?>" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Fowler Part Number -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Fowler Part Number <span class="text-danger">*</span>
                </label>
                <input type="text" name="fowler_part_number" required
                       value="<?= htmlspecialchars($part['fowler_part_number'] ?? '') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <!-- Part Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Part Name <span class="text-danger">*</span>
                </label>
                <input type="text" name="name" required
                       value="<?= htmlspecialchars($part['name'] ?? '') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <!-- Low Stock Threshold -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Low Stock Threshold
                </label>
                <input type="number" name="low_stock_threshold" min="0"
                       value="<?= htmlspecialchars($part['low_stock_threshold'] ?? '') ?>"
                       placeholder="e.g., 5"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <!-- Notes -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Notes
                </label>
                <textarea name="notes" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus-border-transparent"><?= htmlspecialchars($part['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="flex gap-4 pt-4">
            <button type="submit" class="bg-primary text-dark px-6 py-2 rounded-lg hover:bg-secondary transition">
                Update Part
            </button>
            <a href="<?= url('/parts/' . (int)$part['id']) ?>" class="bg-gray-200 text-dark px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                Cancel
            </a>
        </div>
    </form>
</div>
