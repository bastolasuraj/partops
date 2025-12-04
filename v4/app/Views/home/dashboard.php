<?php
// app/Views/home/dashboard.php
// Dashboard with stats cards, low stock alerts, and recent activity
?>
<div class="mb-6">
    <h1 class="text-3xl font-bold text-dark mb-2">Dashboard</h1>
    <p class="text-gray-600">Overview of inventory and recent activity</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Parts -->
    <div class="bg-gradient-to-br from-primary to-secondary rounded-lg shadow-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-dark text-sm font-medium mb-1">Total Parts</p>
                <p class="text-3xl font-bold text-dark"><?= number_format($stats['total_parts'] ?? 0) ?></p>
            </div>
            <svg class="w-12 h-12 text-dark opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
    </div>

    <!-- Low Stock Items -->
    <div class="bg-gradient-to-br from-danger to-red-400 rounded-lg shadow-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-white text-sm font-medium mb-1">Low Stock Items</p>
                <p class="text-3xl font-bold text-white"><?= number_format($stats['low_stock_count'] ?? 0) ?></p>
            </div>
            <svg class="w-12 h-12 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
    </div>

    <!-- Active Work Orders -->
    <div class="bg-gradient-to-br from-info to-blue-300 rounded-lg shadow-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-dark text-sm font-medium mb-1">Active Work Orders</p>
                <p class="text-3xl font-bold text-dark"><?= number_format($stats['active_work_orders'] ?? 0) ?></p>
            </div>
            <svg class="w-12 h-12 text-dark opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
    </div>

    <!-- Outstanding Cores -->
    <div class="bg-gradient-to-br from-warning to-orange-300 rounded-lg shadow-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-dark text-sm font-medium mb-1">Outstanding Cores</p>
                <p class="text-3xl font-bold text-dark">$<?= number_format($stats['outstanding_cores'] ?? 0, 2) ?></p>
            </div>
            <svg class="w-12 h-12 text-dark opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Low Stock Alerts -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-dark">Low Stock Alerts</h2>
            <a href="<?= url('/parts?low=1') ?>" class="text-blue-700 hover:underline text-sm">View All</a>
        </div>
        <?php if (!empty($lowStockParts)): ?>
            <div class="space-y-3">
                <?php foreach (array_slice($lowStockParts, 0, 5) as $part): ?>
                    <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg hover:bg-red-100 transition">
                        <div class="flex-1">
                            <a href="<?= url('/parts/' . (int)$part['id']) ?>" class="font-semibold text-dark hover:text-blue-700">
                                <?= htmlspecialchars($part['name']) ?>
                            </a>
                            <p class="text-xs text-gray-600 font-mono"><?= htmlspecialchars($part['fowler_part_number']) ?></p>
                        </div>
                        <div class="text-right">
                            <span class="px-3 py-1 bg-danger text-white rounded font-semibold">
                                <?= (int)$part['on_hand'] ?>
                            </span>
                            <p class="text-xs text-gray-600 mt-1">Min: <?= (int)$part['low_stock_threshold'] ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-6">All parts are adequately stocked</p>
        <?php endif; ?>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-dark mb-4">Recent Activity</h2>
        <?php if (!empty($recentActivity)): ?>
            <div class="space-y-3">
                <?php foreach (array_slice($recentActivity, 0, 8) as $activity): ?>
                    <div class="flex items-start gap-3 p-3 hover:bg-gray-50 rounded-lg transition">
                        <div class="flex-shrink-0 mt-1">
                            <?php if ($activity['type'] === 'checkin'): ?>
                                <span class="inline-block w-2 h-2 bg-success rounded-full"></span>
                            <?php elseif ($activity['type'] === 'checkout'): ?>
                                <span class="inline-block w-2 h-2 bg-warning rounded-full"></span>
                            <?php else: ?>
                                <span class="inline-block w-2 h-2 bg-info rounded-full"></span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-dark">
                                <span class="font-semibold"><?= htmlspecialchars($activity['description']) ?></span>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                <?= date('M j, Y g:i A', strtotime($activity['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-6">No recent activity</p>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-bold text-dark mb-4">Quick Actions</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="<?= url('/checkins/create') ?>" 
           class="flex flex-col items-center justify-center p-6 bg-success rounded-lg hover:bg-green-400 transition text-dark">
            <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M12 4v16m8-8H4"/>
            </svg>
            <span class="font-semibold">Check-in Parts</span>
        </a>
        <a href="<?= url('/checkouts/create') ?>" 
           class="flex flex-col items-center justify-center p-6 bg-warning rounded-lg hover:bg-orange-400 transition text-dark">
            <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M20 12H4"/>
            </svg>
            <span class="font-semibold">Checkout Parts</span>
        </a>
        <a href="<?= url('/parts/create') ?>" 
           class="flex flex-col items-center justify-center p-6 bg-primary rounded-lg hover:bg-secondary transition text-dark">
            <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span class="font-semibold">Add Part</span>
        </a>
        <a href="<?= url('/returns') ?>" 
           class="flex flex-col items-center justify-center p-6 bg-info rounded-lg hover:bg-blue-200 transition text-dark">
            <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
            </svg>
            <span class="font-semibold">Process Returns</span>
        </a>
    </div>
</div>
