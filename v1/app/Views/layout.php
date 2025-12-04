<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'PartOps') ?> - PartOps</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style type="text/tailwindcss">
        /* Modern Design System */
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
        }

        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        /* Global Styles */
        body {
            @apply bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/40 text-gray-800 antialiased;
            min-height: 100vh;
        }

        /* Typography - Modern & Clean */
        h1 { 
            @apply text-3xl md:text-4xl font-bold mb-6;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        h2 { @apply text-2xl font-bold text-gray-800 mb-4; }
        h3 { @apply text-xl font-semibold text-gray-700 mb-3; }
        p { @apply mb-4 text-gray-600 leading-relaxed; }
        a { @apply text-indigo-600 hover:text-indigo-700 transition-colors; }

        /* Tables - Sleek & Modern */
        table {
            @apply w-full text-sm text-left text-gray-700 mb-6 border-collapse bg-white/90 backdrop-blur-sm rounded-2xl overflow-hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        thead { 
            @apply text-xs text-white uppercase tracking-wider;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        }
        th { @apply px-6 py-4 font-semibold; }
        td { @apply px-6 py-4 border-b border-gray-100; }
        tbody tr { @apply transition-all duration-200; }
        tbody tr:hover { 
            @apply bg-indigo-50/50;
            transform: scale(1.001);
        }
        tr:last-child td { @apply border-b-0; }

        /* Forms - Modern & Polished */
        label { @apply block mb-2 text-sm font-semibold text-gray-700; }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            @apply bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl block w-full p-3 transition-all;
        }
        input:focus, select:focus, textarea:focus {
            @apply ring-4 ring-indigo-500/20 border-indigo-500 outline-none;
            transform: translateY(-1px);
        }
        input:hover:not(:focus), select:hover:not(:focus), textarea:hover:not(:focus) {
            @apply border-gray-300;
        }

        /* Buttons - Vibrant & Modern */
        .btn {
            @apply inline-flex items-center justify-center px-6 py-3 text-sm font-semibold text-center text-white rounded-xl transition-all shadow-lg hover:shadow-xl;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            text-decoration: none;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.3), 0 10px 10px -5px rgba(99, 102, 241, 0.2);
        }
        .btn:active {
            transform: translateY(0);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
        }
        .btn-secondary:hover {
            box-shadow: 0 20px 25px -5px rgba(71, 85, 105, 0.3), 0 10px 10px -5px rgba(71, 85, 105, 0.2);
        }
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        /* Header - Clean & Modern */
        .main-header {
            @apply bg-white/90 backdrop-blur-xl shadow-lg border-b border-gray-200/50 sticky top-0 z-50;
        }
        
        .nav-link {
            @apply flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 rounded-xl hover:bg-indigo-50 hover:text-indigo-700 transition-all duration-200;
            text-decoration: none;
        }
        .nav-link.active {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            @apply text-white shadow-md;
        }
        .nav-link:not(.active):hover {
            transform: translateX(2px);
        }
        
        /* Mobile Menu */
        .mobile-menu-btn {
            @apply md:hidden p-2.5 text-gray-700 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all;
        }
        
        .mobile-nav-overlay {
            @apply md:hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-40 transition-opacity duration-300;
            opacity: 0;
            pointer-events: none;
        }
        .mobile-nav-overlay.open {
            opacity: 1;
            pointer-events: auto;
        }
        
        .mobile-nav {
            @apply md:hidden fixed top-0 right-0 h-full bg-white shadow-2xl z-50 transition-transform duration-300 ease-in-out overflow-y-auto;
            width: 85%;
            max-width: 320px;
            transform: translateX(100%);
        }
        .mobile-nav.open {
            transform: translateX(0);
        }
        
        body.mobile-menu-open {
            overflow: hidden;
        }
        
        /* Alerts - Modern */
        .alert { 
            @apply p-4 mb-4 text-sm rounded-xl shadow-md border-l-4;
        }
        .alert-error { 
            @apply text-red-800 border-red-500 bg-red-50;
        }
        .alert-success { 
            @apply text-green-800 border-green-500 bg-green-50;
        }
        .alert-info { 
            @apply text-blue-800 border-blue-500 bg-blue-50;
        }
        .alert-warning { 
            @apply text-amber-800 border-amber-500 bg-amber-50;
        }

        /* Badges */
        .badge {
            @apply inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full;
        }
        .badge-primary {
            @apply bg-indigo-100 text-indigo-700;
        }
        .badge-success {
            @apply bg-green-100 text-green-700;
        }
        .badge-warning {
            @apply bg-amber-100 text-amber-700;
        }
        .badge-danger {
            @apply bg-red-100 text-red-700;
        }

        /* Content Card */
        .content-card {
            @apply bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-6 md:p-8;
        }

        /* Dropdown Menu */
        .dropdown-menu {
            @apply bg-white rounded-xl shadow-xl border border-gray-100 min-w-[200px] py-2;
        }
        .dropdown-item {
            @apply block px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors;
        }
    </style>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="flex flex-col min-h-screen">
    <?php if (isset($_SESSION['user_id'])): ?>
    <header class="main-header">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <!-- Brand -->
                <a href="<?= url('/') ?>" class="flex items-center space-x-3 group">
                    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 p-2.5 rounded-xl shadow-lg group-hover:shadow-xl transition-all duration-300">
                        <i class="fas fa-cogs text-white text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">PartOps</span>
                </a>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex items-center space-x-2">
                    <a href="<?= url('/') ?>" class="nav-link <?= ($_SERVER['REQUEST_URI'] == '/v1/' || $_SERVER['REQUEST_URI'] == '/v1/index.php') ? 'active' : '' ?>">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                    </a>
                    <a href="<?= url('/parts') ?>" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/parts') !== false ? 'active' : '' ?>">
                        <i class="fas fa-cubes mr-2"></i>Parts
                    </a>
                    <a href="<?= url('/suppliers') ?>" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/suppliers') !== false ? 'active' : '' ?>">
                        <i class="fas fa-truck mr-2"></i>Suppliers
                    </a>
                    <div class="relative group">
                        <a href="<?= url('/inventory') ?>" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/inventory') !== false ? 'active' : '' ?>">
                            <i class="fas fa-clipboard-list mr-2"></i>Inventory
                            <i class="fas fa-caret-down ml-1 text-xs"></i>
                        </a>
                        <div class="absolute left-0 mt-2 hidden group-hover:block">
                            <div class="dropdown-menu">
                                <a href="<?= url('/inventory/receive') ?>" class="dropdown-item"><i class="fas fa-arrow-down mr-2"></i>Receive</a>
                                <a href="<?= url('/inventory/checkout') ?>" class="dropdown-item"><i class="fas fa-arrow-up mr-2"></i>Checkout</a>
                                <a href="<?= url('/inventory/return') ?>" class="dropdown-item"><i class="fas fa-undo mr-2"></i>Returns</a>
                                <a href="<?= url('/inventory/adjust') ?>" class="dropdown-item"><i class="fas fa-sliders-h mr-2"></i>Adjustments</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="ml-4 pl-4 border-l border-gray-200 flex items-center space-x-3">
                        <div class="flex items-center space-x-2 px-3 py-2 bg-gray-50 rounded-xl">
                            <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-white text-xs"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                        </div>
                        <form action="<?= url('/logout') ?>" method="POST" class="inline">
                            <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token'] ?? '' ?>">
                            <button type="submit" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all" title="Logout">
                                <i class="fas fa-sign-out-alt"></i>
                            </button>
                        </form>
                    </div>
                </nav>

                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-btn" id="mobile-menu-toggle" aria-label="Toggle menu" aria-expanded="false">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Navigation Overlay -->
    <div class="mobile-nav-overlay" id="mobile-nav-overlay"></div>
    
    <!-- Mobile Navigation Drawer -->
    <div class="mobile-nav" id="mobile-nav">
        <div class="p-6">
            <!-- Drawer Header -->
            <div class="flex items-center justify-between mb-8 pb-6 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 p-2 rounded-xl">
                        <i class="fas fa-cogs text-white text-lg"></i>
                    </div>
                    <span class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Menu</span>
                </div>
                <button class="text-gray-500 hover:text-gray-700 p-2 hover:bg-gray-100 rounded-xl transition-all" id="mobile-nav-close">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Navigation Links -->
            <div class="space-y-2 mb-8">
                <a href="<?= url('/') ?>" class="nav-link <?= ($_SERVER['REQUEST_URI'] == '/v1/' || $_SERVER['REQUEST_URI'] == '/v1/index.php') ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                </a>
                <a href="<?= url('/parts') ?>" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/parts') !== false ? 'active' : '' ?>">
                    <i class="fas fa-cubes mr-3"></i>Parts
                </a>
                <a href="<?= url('/suppliers') ?>" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/suppliers') !== false ? 'active' : '' ?>">
                    <i class="fas fa-truck mr-3"></i>Suppliers
                </a>
                <a href="<?= url('/inventory') ?>" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/inventory') !== false ? 'active' : '' ?>">
                    <i class="fas fa-clipboard-list mr-3"></i>Inventory
                </a>
                
                <!-- Inventory Submenu -->
                <div class="ml-6 space-y-1 border-l-2 border-indigo-200 pl-4 py-2">
                    <a href="<?= url('/inventory/receive') ?>" class="block px-3 py-2 text-sm text-gray-600 hover:text-indigo-700 hover:bg-indigo-50 rounded-lg transition-colors">
                        <i class="fas fa-arrow-down mr-2"></i>Receive
                    </a>
                    <a href="<?= url('/inventory/checkout') ?>" class="block px-3 py-2 text-sm text-gray-600 hover:text-indigo-700 hover:bg-indigo-50 rounded-lg transition-colors">
                        <i class="fas fa-arrow-up mr-2"></i>Checkout
                    </a>
                    <a href="<?= url('/inventory/return') ?>" class="block px-3 py-2 text-sm text-gray-600 hover:text-indigo-700 hover:bg-indigo-50 rounded-lg transition-colors">
                        <i class="fas fa-undo mr-2"></i>Returns
                    </a>
                    <a href="<?= url('/inventory/adjust') ?>" class="block px-3 py-2 text-sm text-gray-600 hover:text-indigo-700 hover:bg-indigo-50 rounded-lg transition-colors">
                        <i class="fas fa-sliders-h mr-2"></i>Adjustments
                    </a>
                </div>
            </div>
            
            <!-- User Info & Logout -->
            <div class="pt-6 border-t border-gray-200">
                <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl p-4 mb-4">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-white"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></p>
                            <p class="text-xs text-gray-500">Logged In</p>
                        </div>
                    </div>
                    <form action="<?= url('/logout') ?>" method="POST" class="w-full">
                        <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token'] ?? '' ?>">
                        <button type="submit" class="w-full flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl transition-all text-sm font-semibold shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <main class="container mx-auto px-4 py-8 flex-grow">
        <div class="content-card min-h-[500px]">
            <?php echo $content ?? ''; ?>
        </div>
    </main>

    <footer class="bg-white/80 backdrop-blur-sm border-t border-gray-200 py-6 mt-auto">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-600 text-sm">
                &copy; <?= date('Y') ?> PartOps Inventory System 
                <span class="mx-2 text-gray-400">•</span> 
                <span class="font-semibold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">v1.0.0</span>
            </p>
        </div>
    </footer>
    
    <!-- Mobile Menu Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('mobile-menu-toggle');
            const closeBtn = document.getElementById('mobile-nav-close');
            const mobileNav = document.getElementById('mobile-nav');
            const overlay = document.getElementById('mobile-nav-overlay');
            
            function openMenu() {
                mobileNav.classList.add('open');
                overlay.classList.add('open');
                document.body.classList.add('mobile-menu-open');
                toggleBtn.setAttribute('aria-expanded', 'true');
            }
            
            function closeMenu() {
                mobileNav.classList.remove('open');
                overlay.classList.remove('open');
                document.body.classList.remove('mobile-menu-open');
                toggleBtn.setAttribute('aria-expanded', 'false');
            }
            
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    if (mobileNav.classList.contains('open')) {
                        closeMenu();
                    } else {
                        openMenu();
                    }
                });
            }
            
            if (closeBtn) {
                closeBtn.addEventListener('click', closeMenu);
            }
            
            if (overlay) {
                overlay.addEventListener('click', closeMenu);
            }
            
            if (mobileNav) {
                const navLinks = mobileNav.querySelectorAll('a');
                navLinks.forEach(link => {
                    link.addEventListener('click', closeMenu);
                });
            }
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && mobileNav.classList.contains('open')) {
                    closeMenu();
                }
            });
        });
    </script>
</body>
</html>
