<?php
/**
 * PartOps v2 Setup Script
 * Run this script to create the directory structure and move files
 * Usage: php setup.php
 */

$baseDir = __DIR__;

echo "PartOps v2 Setup Script\n";
echo "========================\n\n";

$directories = [
    'public',
    'public/assets',
    'public/assets/css',
    'public/assets/js',
    'public/assets/images',
    'app',
    'app/Core',
    'app/Config',
    'app/Controllers',
    'app/Models',
    'app/Views',
    'app/Views/layouts',
    'app/Views/auth',
    'app/Views/home',
    'app/Views/parts',
    'app/Views/receiving',
    'app/Views/checkout',
    'app/Views/returns',
    'app/Views/locations',
    'app/Views/suppliers',
    'app/Views/technicians',
    'app/Views/work-orders',
    'app/Views/reports',
    'app/Services',
    'database',
    'database/migrations',
    'database/seeds',
    'storage',
    'storage/logs',
    'storage/cache',
    'storage/tmp',
    'scripts',
    'tests',
];

echo "Creating PartOps v2 directory structure...\n\n";

foreach ($directories as $dir) {
    $path = $baseDir . '/' . $dir;
    if (!is_dir($path)) {
        if (mkdir($path, 0775, true)) {
            echo "Created: $dir\n";
        } else {
            echo "Failed to create: $dir\n";
        }
    } else {
        echo "Exists: $dir\n";
    }
}

// Create .gitkeep files in empty directories
$gitkeepDirs = [
    'storage/logs',
    'storage/cache',
    'storage/tmp',
    'tests',
];

foreach ($gitkeepDirs as $dir) {
    $path = $baseDir . '/' . $dir . '/.gitkeep';
    if (!file_exists($path)) {
        file_put_contents($path, '');
        echo "Created .gitkeep in: $dir\n";
    }
}

echo "\nDirectory structure created successfully!\n";

// Move files to their correct locations
echo "\nMoving files to correct locations...\n";

$fileMoves = [
    // Core files
    'app_bootstrap.php' => 'app/bootstrap.php',
    'app_core_Application.php' => 'app/Core/Application.php',
    'app_core_Router.php' => 'app/Core/Router.php',
    'app_core_Request.php' => 'app/Core/Request.php',
    'app_core_Response.php' => 'app/Core/Response.php',
    'app_core_Controller.php' => 'app/Core/Controller.php',
    'app_core_Model.php' => 'app/Core/Model.php',
    
    // Config
    'app_config_Database.php' => 'app/Config/Database.php',
    
    // Models
    'app_models_Part.php' => 'app/Models/Part.php',
    'app_models_PartNumber.php' => 'app/Models/PartNumber.php',
    'app_models_Supplier.php' => 'app/Models/Supplier.php',
    'app_models_Location.php' => 'app/Models/Location.php',
    'app_models_Technician.php' => 'app/Models/Technician.php',
    'app_models_WorkOrder.php' => 'app/Models/WorkOrder.php',
    'app_models_InventoryLevel.php' => 'app/Models/InventoryLevel.php',
    'app_models_InventoryMove.php' => 'app/Models/InventoryMove.php',
    'app_models_CoreLiability.php' => 'app/Models/CoreLiability.php',
    'app_models_User.php' => 'app/Models/User.php',
    'app_models_AuditLog.php' => 'app/Models/AuditLog.php',
    
    // Controllers
    'app_controllers_HomeController.php' => 'app/Controllers/HomeController.php',
    'app_controllers_AuthController.php' => 'app/Controllers/AuthController.php',
    'app_controllers_ReceivingController.php' => 'app/Controllers/ReceivingController.php',
    'app_controllers_CheckoutController.php' => 'app/Controllers/CheckoutController.php',
    'app_controllers_ReturnController.php' => 'app/Controllers/ReturnController.php',
    
    // Views
    'views_layouts_main.php' => 'app/Views/layouts/main.php',
    'views_auth_login.php' => 'app/Views/auth/login.php',
    'views_home_index.php' => 'app/Views/home/index.php',
    'views_parts_index.php' => 'app/Views/parts/index.php',
    'views_receiving_new.php' => 'app/Views/receiving/new.php',
    'views_receiving_wo_return.php' => 'app/Views/receiving/wo_return.php',
    'views_checkout_create.php' => 'app/Views/checkout/create.php',
    'views_returns_standard.php' => 'app/Views/returns/standard.php',
    'views_returns_core.php' => 'app/Views/returns/core.php',
    
    // Assets
    'assets_css_style.css' => 'public/assets/css/style.css',
    
    // Scripts
    'scripts_migrate.php' => 'scripts/migrate.php',
    'scripts_seed.php' => 'scripts/seed.php',
    'scripts_reset.php' => 'scripts/reset.php',
    
    // Database
    'database_schema.sql' => 'database/migrations/001_initial_schema.sql',
];

foreach ($fileMoves as $source => $dest) {
    $sourcePath = $baseDir . '/' . $source;
    $destPath = $baseDir . '/' . $dest;
    
    if (file_exists($sourcePath)) {
        if (rename($sourcePath, $destPath)) {
            echo "Moved: $source -> $dest\n";
        } else {
            echo "Failed to move: $source\n";
        }
    }
}

// Create public/index.php
$indexPhp = <<<'PHP'
<?php
declare(strict_types=1);

/**
 * PartOps v2 - Front Controller
 * All requests are routed through this file
 */

define('BASE_PATH', dirname(__DIR__));

// Autoload
require BASE_PATH . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

// Bootstrap the application
require BASE_PATH . '/app/bootstrap.php';

// Initialize and run the application
$app = new App\Core\Application();
$app->run();
PHP;

file_put_contents($baseDir . '/public/index.php', $indexPhp);
echo "Created: public/index.php\n";

// Create public/.htaccess
$htaccess = <<<'HTACCESS'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
HTACCESS;

file_put_contents($baseDir . '/public/.htaccess', $htaccess);
echo "Created: public/.htaccess\n";

// Create public/web.config for IIS
$webConfig = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="PartOps" stopProcessing="true">
                    <match url="^(.*)$" ignoreCase="false" />
                    <conditions logicalGrouping="MatchAll">
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" ignoreCase="false" negate="true" />
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" ignoreCase="false" negate="true" />
                    </conditions>
                    <action type="Rewrite" url="index.php?url={R:1}" appendQueryString="true" />
                </rule>
            </rules>
        </rewrite>
        <httpProtocol>
            <customHeaders>
                <add name="X-Content-Type-Options" value="nosniff" />
                <add name="X-Frame-Options" value="SAMEORIGIN" />
                <add name="X-XSS-Protection" value="1; mode=block" />
            </customHeaders>
        </httpProtocol>
    </system.webServer>
</configuration>
XML;

file_put_contents($baseDir . '/public/web.config', $webConfig);
echo "Created: public/web.config\n";

// Create .gitignore
$gitignore = <<<'GITIGNORE'
# Dependencies
/vendor/

# Environment
.env
*.env.local

# Storage
/storage/logs/*
/storage/cache/*
/storage/tmp/*
!storage/logs/.gitkeep
!storage/cache/.gitkeep
!storage/tmp/.gitkeep

# IDE
.idea/
.vscode/
*.swp
*.swo

# OS
.DS_Store
Thumbs.db

# Composer
composer.phar
GITIGNORE;

file_put_contents($baseDir . '/.gitignore', $gitignore);
echo "Created: .gitignore\n";

echo "\n========================\n";
echo "Setup complete!\n\n";
echo "Next steps:\n";
echo "1. Run: composer install\n";
echo "2. Copy .env.example to .env and configure database\n";
echo "3. Run: php scripts/migrate.php\n";
echo "4. Run: php scripts/seed.php (optional)\n";
echo "5. Start server: php -S localhost:8000 -t public\n";
