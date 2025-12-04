#!/usr/bin/env python3
"""
PartOps v2 - File Organizer Script
Sorts all flat files into their respective directories based on naming convention.

Usage: python3 organize.py
"""

import os
import shutil
from pathlib import Path

# Base directory
BASE_DIR = Path(__file__).parent

# Directory structure to create
DIRECTORIES = [
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
    'app/Views/errors',
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
]

# File mapping: source filename -> destination path
FILE_MAPPINGS = {
    # Core framework files
    'app_bootstrap.php': 'app/bootstrap.php',
    'app_core_Application.php': 'app/Core/Application.php',
    'app_core_Router.php': 'app/Core/Router.php',
    'app_core_Request.php': 'app/Core/Request.php',
    'app_core_Response.php': 'app/Core/Response.php',
    'app_core_Controller.php': 'app/Core/Controller.php',
    'app_core_Model.php': 'app/Core/Model.php',
    
    # Config files
    'app_config_Database.php': 'app/Config/Database.php',
    
    # Model files
    'app_models_Part.php': 'app/Models/Part.php',
    'app_models_PartNumber.php': 'app/Models/PartNumber.php',
    'app_models_Supplier.php': 'app/Models/Supplier.php',
    'app_models_Location.php': 'app/Models/Location.php',
    'app_models_Technician.php': 'app/Models/Technician.php',
    'app_models_WorkOrder.php': 'app/Models/WorkOrder.php',
    'app_models_InventoryLevel.php': 'app/Models/InventoryLevel.php',
    'app_models_InventoryMove.php': 'app/Models/InventoryMove.php',
    'app_models_CoreLiability.php': 'app/Models/CoreLiability.php',
    'app_models_User.php': 'app/Models/User.php',
    'app_models_AuditLog.php': 'app/Models/AuditLog.php',
    
    # Controller files
    'app_controllers_HomeController.php': 'app/Controllers/HomeController.php',
    'app_controllers_AuthController.php': 'app/Controllers/AuthController.php',
    'app_controllers_ReceivingController.php': 'app/Controllers/ReceivingController.php',
    'app_controllers_CheckoutController.php': 'app/Controllers/CheckoutController.php',
    'app_controllers_ReturnController.php': 'app/Controllers/ReturnController.php',
    
    # View files
    'views_layouts_main.php': 'app/Views/layouts/main.php',
    'views_auth_login.php': 'app/Views/auth/login.php',
    'views_home_index.php': 'app/Views/home/index.php',
    'views_parts_index.php': 'app/Views/parts/index.php',
    'views_receiving_new.php': 'app/Views/receiving/new.php',
    'views_receiving_wo_return.php': 'app/Views/receiving/wo_return.php',
    'views_checkout_create.php': 'app/Views/checkout/create.php',
    'views_returns_standard.php': 'app/Views/returns/standard.php',
    'views_returns_core.php': 'app/Views/returns/core.php',
    
    # Asset files
    'assets_css_style.css': 'public/assets/css/style.css',
    
    # Script files
    'scripts_migrate.php': 'scripts/migrate.php',
    'scripts_seed.php': 'scripts/seed.php',
    'scripts_reset.php': 'scripts/reset.php',
    
    # Database files
    'database_schema.sql': 'database/migrations/001_initial_schema.sql',
}

# Files to create from scratch
FILES_TO_CREATE = {
    'public/index.php': '''<?php
declare(strict_types=1);

/**
 * PartOps v2 - Front Controller
 * All requests are routed through this file
 */

define('BASE_PATH', dirname(__DIR__));

// Autoload
require BASE_PATH . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\\Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

// Bootstrap the application
require BASE_PATH . '/app/bootstrap.php';

// Initialize and run the application
$app = new App\\Core\\Application();
$app->run();
''',

    'public/.htaccess': '''RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
''',

    'public/web.config': '''<?xml version="1.0" encoding="UTF-8"?>
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
''',

    '.gitignore': '''# Dependencies
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

# Python
__pycache__/
*.pyc
''',

    'app/Views/errors/404.php': '''<?php
/**
 * 404 Error Page
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div style="display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;">
        <div>
            <h1 style="font-size:4rem;color:var(--accent-2);margin-bottom:1rem;">404</h1>
            <p style="color:var(--muted);margin-bottom:2rem;">Page not found</p>
            <a href="/" class="btn-primary">Go Home</a>
        </div>
    </div>
</body>
</html>
''',
}

# Gitkeep directories
GITKEEP_DIRS = [
    'storage/logs',
    'storage/cache',
    'storage/tmp',
    'tests',
    'public/assets/js',
    'public/assets/images',
]


def create_directories():
    """Create all required directories."""
    print("Creating directories...")
    for dir_path in DIRECTORIES:
        full_path = BASE_DIR / dir_path
        if not full_path.exists():
            full_path.mkdir(parents=True, exist_ok=True)
            print(f"  ✓ Created: {dir_path}")
        else:
            print(f"  - Exists: {dir_path}")


def move_files():
    """Move files from flat structure to proper directories."""
    print("\nMoving files...")
    moved = 0
    skipped = 0
    
    for source, dest in FILE_MAPPINGS.items():
        source_path = BASE_DIR / source
        dest_path = BASE_DIR / dest
        
        if source_path.exists():
            # Ensure destination directory exists
            dest_path.parent.mkdir(parents=True, exist_ok=True)
            
            # Move the file
            shutil.move(str(source_path), str(dest_path))
            print(f"  ✓ Moved: {source} -> {dest}")
            moved += 1
        else:
            # Check if already in destination
            if dest_path.exists():
                print(f"  - Already exists: {dest}")
            else:
                print(f"  ✗ Not found: {source}")
            skipped += 1
    
    print(f"\n  Moved: {moved}, Skipped: {skipped}")


def create_files():
    """Create additional required files."""
    print("\nCreating additional files...")
    
    for file_path, content in FILES_TO_CREATE.items():
        full_path = BASE_DIR / file_path
        
        if not full_path.exists():
            full_path.parent.mkdir(parents=True, exist_ok=True)
            full_path.write_text(content)
            print(f"  ✓ Created: {file_path}")
        else:
            print(f"  - Exists: {file_path}")


def create_gitkeep_files():
    """Create .gitkeep files in empty directories."""
    print("\nCreating .gitkeep files...")
    
    for dir_path in GITKEEP_DIRS:
        gitkeep_path = BASE_DIR / dir_path / '.gitkeep'
        
        if not gitkeep_path.exists():
            gitkeep_path.parent.mkdir(parents=True, exist_ok=True)
            gitkeep_path.touch()
            print(f"  ✓ Created: {dir_path}/.gitkeep")
        else:
            print(f"  - Exists: {dir_path}/.gitkeep")


def cleanup_setup_files():
    """Remove setup files after organization."""
    print("\nCleaning up...")
    
    setup_files = ['setup.php', 'organize.py']
    
    for filename in setup_files:
        file_path = BASE_DIR / filename
        if file_path.exists():
            # Don't delete, just note it
            print(f"  - Keep: {filename} (delete manually if not needed)")


def print_summary():
    """Print final summary and next steps."""
    print("\n" + "=" * 50)
    print("Organization Complete!")
    print("=" * 50)
    print("\nDirectory structure:")
    print("""
v2/
├── public/              # Web root
│   ├── index.php        # Front controller
│   ├── .htaccess        # Apache rewrite rules
│   ├── web.config       # IIS rewrite rules
│   └── assets/          # CSS, JS, images
├── app/
│   ├── bootstrap.php    # Application bootstrap
│   ├── Core/            # Framework classes
│   ├── Config/          # Configuration
│   ├── Controllers/     # MVC Controllers
│   ├── Models/          # MVC Models
│   ├── Views/           # PHP templates
│   └── Services/        # Business logic
├── database/
│   ├── migrations/      # SQL migrations
│   └── seeds/           # Sample data
├── storage/             # Logs, cache, tmp
├── scripts/             # CLI scripts
└── tests/               # Unit tests
""")
    print("\nNext steps:")
    print("  1. cd /mnt/shared/projects/partops/v2")
    print("  2. composer install")
    print("  3. cp .env.example .env")
    print("  4. Edit .env with your database credentials")
    print("  5. php scripts/migrate.php")
    print("  6. php scripts/seed.php  (optional)")
    print("  7. php -S localhost:8000 -t public")
    print()


def main():
    """Main entry point."""
    print("=" * 50)
    print("PartOps v2 - File Organizer")
    print("=" * 50)
    print(f"\nWorking directory: {BASE_DIR}\n")
    
    # Run organization steps
    create_directories()
    move_files()
    create_files()
    create_gitkeep_files()
    cleanup_setup_files()
    print_summary()


if __name__ == '__main__':
    main()
