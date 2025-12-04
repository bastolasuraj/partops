<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_ENV['APP_NAME'] ?? 'PartOps v4' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#f9ca24',
                        secondary: '#f6e58d',
                        success: '#badc58',
                        danger: '#ff7979',
                        info: '#dff9fb',
                        warning: '#ffbe76',
                        dark: '#2c3e50',
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-600 to-indigo-700 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-dark shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="<?= url('/') ?>" class="text-2xl font-bold text-primary">🔧 PartOps v4</a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-4">
                        <a href="<?= url('/dashboard') ?>" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                        <a href="<?= url('/parts') ?>" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Parts</a>
                        <a href="<?= url('/suppliers') ?>" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Suppliers</a>
                        <a href="<?= url('/technicians') ?>" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Technicians</a>
                        <a href="<?= url('/work-orders') ?>" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Work Orders</a>
                        <a href="<?= url('/checkins') ?>" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Check-In</a>
                        <a href="<?= url('/checkouts') ?>" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Checkout</a>
                        <a href="<?= url('/returns') ?>" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Returns</a>
                        <a href="<?= url('/logs') ?>" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">📋 Logs</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-success border border-green-400 text-dark px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($_SESSION['success']) ?></span>
            </div>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-danger border border-red-400 text-white px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($_SESSION['error']) ?></span>
            </div>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-xl p-6">
            <?= $content ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="mt-8 pb-8 text-center text-white text-sm">
        <p>&copy; <?= date('Y') ?> PartOps v4 - Parts Inventory Management System</p>
    </footer>
</body>
</html>
