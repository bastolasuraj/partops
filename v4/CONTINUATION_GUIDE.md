# PartOps v4 - Continuation Guide

**Project Location:** `/mnt/shared/projects/partops/v4`  
**Live URL:** https://php.sbastola.com/partops/v4  
**Database:** `partsdb` (shared with v2)  
**Database User:** `partsuser` (configure credentials in `.env`)

---

## 📋 PROJECT OVERVIEW

PartOps v4 is a PHP MVC application for parts inventory management. It tracks:
- Parts with QR codes
- Suppliers and technicians
- Work orders
- Parts check-in/checkout
- Returns (work order and supplier)
- Core charge tracking

**Tech Stack:**
- PHP 8.1+ with strict typing
- MySQL 5.7+ (using existing `partsdb` database)
- Tailwind CSS (CDN)
- PSR-4 autoloading via Composer
- No framework dependencies (custom MVC)

---

## ✅ COMPLETED COMPONENTS

### 1. Core Framework (`app/Core/`)
- ✅ `Database.php` - PDO singleton with query helpers
- ✅ `Router.php` - Dynamic routing with parameters
- ✅ `View.php` - Template rendering with layout support
- ✅ `Model.php` - Base model with CRUD operations

### 2. Models (`app/Models/`)
- ✅ `Part.php` - Parts with inventory tracking, search, low stock alerts
- ✅ `Supplier.php` - Supplier management
- ✅ `Technician.php` - Technician management
- ✅ `WorkOrder.php` - Work order tracking with parts

### 3. Controllers (`app/Controllers/`)
- ✅ `HomeController.php` - index() and dashboard()
- ✅ `PartController.php` - Full CRUD + search API
- ✅ `SupplierController.php` - Full CRUD
- ✅ `TechnicianController.php` - Full CRUD
- ✅ `WorkOrderController.php` - List, show, getParts API
- ✅ `CheckinController.php` - Parts receiving workflow
- ✅ `CheckoutController.php` - Parts issuing to work orders
- ✅ `ReturnController.php` - WO returns and supplier returns

### 4. Views (`app/Views/`)
- ✅ `layout.php` - Main layout with Tailwind CSS, navigation, flash messages
- ✅ `home/index.php` - Landing page with feature cards
- ✅ `errors/404.php` - Not found page
- ✅ `logs/index.php` - Error log viewer (admin)

### 5. Configuration & Scripts
- ✅ `composer.json` - PSR-4 autoloading, phpdotenv
- ✅ `.env` - Configured with v2 credentials
- ✅ `.env.example` - Template for new setups
- ✅ `routes/web.php` - All routes defined
- ✅ `public/index.php` - Entry point with Logger initialization
- ✅ `public/.htaccess` - Apache rewrite rules
- ✅ `scripts/migrate.php` - Database migration script
- ✅ `scripts/seed.php` - Sample data seeder
- ✅ `scripts/test-logger.php` - Logger testing script
- ✅ `Makefile` - Build commands
- ✅ `schema.sql` - Complete database schema (8 tables + 3 views)

### 6. Error Logging System
- ✅ `app/Core/Logger.php` - Comprehensive error logging class
- ✅ `app/Controllers/LogController.php` - Log viewer controller
- ✅ `storage/logs/err.log` - Centralized error log file
- ✅ Error handlers for PHP, MySQL, and exceptions
- ✅ Log viewer UI at `/logs` with filters and stats
- ✅ Auto-refresh and clear logs functionality

### 7. Database Schema
**Tables (8):**
- `suppliers` - name, phone, email, address, url
- `technicians` - name, phone, email
- `parts` - fowler_part_number (unique), name, supplier_id, supplier_part_number, location (aisle/shelf/bay), low_stock_threshold, **url**, notes
- `work_orders` - wo_number (unique), technician_id, unit_number
- `part_checkins` - part_id, supplier_id, price, has_core_charge, expected_rebate, quantity, notes
- `part_checkouts` - work_order_id, technician_id, unit_number, part_id, quantity
- `work_order_returns` - work_order_id, part_id, return_quantity, required_quantity
- `returns` - supplier_id, supplier_part_number, part_id, quantity, is_core_charge, core_charge_amount, expected_rebate, rebate_received, notes

**Views (3):**
- `v_inventory_levels` - Real-time on-hand quantities
- `v_outstanding_cores` - Core charges awaiting rebates
- `v_work_order_parts` - Parts per work order summary

---

## ✅ COMPLETED VIEWS (December 2024)

### Priority 1: Essential Views (Required for Basic Functionality)

#### Parts Module
- ✅ `app/Views/parts/index.php` - List all parts with inventory levels, search, filters, QR codes
- ✅ `app/Views/parts/create.php` - Form to create new part (all fields including URL)
- ✅ `app/Views/parts/show.php` - Part detail page with inventory, transactions, QR code
- ✅ `app/Views/parts/edit.php` - Edit part form

#### Suppliers Module
- ✅ `app/Views/suppliers/index.php` - List with inline add/edit form, table view

#### Technicians Module
- ✅ `app/Views/technicians/index.php` - List with inline add/edit form, call buttons

#### Work Orders Module
- ✅ `app/Views/work-orders/index.php` - List with search, filters
- ✅ `app/Views/work-orders/show.php` - WO details with parts breakdown

#### Check-in Module
- ✅ `app/Views/checkins/index.php` - Recent check-ins list
- ✅ `app/Views/checkins/create.php` - Check-in form with core charge toggle

#### Checkout Module
- ✅ `app/Views/checkouts/index.php` - Recent checkouts list
- ✅ `app/Views/checkouts/create.php` - Checkout form with repeatable part rows

#### Returns Module
- ✅ `app/Views/returns/index.php` - Returns dashboard (links to WO and supplier returns)
- ✅ `app/Views/checkins/work-order.php` - WO return form
- ✅ `app/Views/returns/supplier.php` - Supplier return form with core charge toggle

#### Dashboard & Errors
- ✅ `app/Views/home/dashboard.php` - Stats cards, low stock alerts, recent activity
- ✅ `app/Views/errors/404.php` - Not found page

## 🚧 REMAINING WORK

### Priority 2: Enhanced Features
- [ ] Pagination helper and advanced filters
- [ ] Export functionality (CSV/PDF) and print-friendly views
- [ ] Loading states for AJAX and dynamic form interactions
- [ ] Authentication and authorization layer

### Priority 3: Polish
- [ ] Form validation (client and server-side)
- [ ] CSRF token implementation
- [ ] Session flash message improvements
- [ ] Mobile responsive testing
- [ ] Performance tuning and caching

---

## 🎨 DESIGN SYSTEM

**Tailwind CSS Configuration (already in layout.php):**
```javascript
colors: {
    primary: '#f9ca24',    // Yellow
    secondary: '#f6e58d',  // Light yellow
    success: '#badc58',    // Green
    danger: '#ff7979',     // Red
    info: '#dff9fb',       // Light blue
    warning: '#ffbe76',    // Orange
    dark: '#2c3e50',       // Dark blue
}
```

**UI Patterns to Follow:**
- Cards with `rounded-lg shadow-xl`
- Buttons with hover effects and transitions
- Tables with hover states
- Form inputs with focus states
- Flash messages at top of page
- Gradient backgrounds for feature cards

---

## 🔗 ROUTING STRUCTURE

All routes defined in `routes/web.php`:

```
GET  /                          → HomeController::index
GET  /dashboard                 → HomeController::dashboard
GET  /parts                     → PartController::index
GET  /parts/create              → PartController::create
POST /parts                     → PartController::store
GET  /parts/{id}                → PartController::show
GET  /parts/{id}/edit           → PartController::edit
POST /parts/{id}/update         → PartController::update
POST /parts/{id}/delete         → PartController::delete
GET  /api/parts/search          → PartController::search (JSON)

GET  /suppliers                 → SupplierController::index
POST /suppliers                 → SupplierController::store
POST /suppliers/{id}/update     → SupplierController::update
POST /suppliers/{id}/delete     → SupplierController::delete

GET  /technicians               → TechnicianController::index
POST /technicians               → TechnicianController::store
POST /technicians/{id}/update   → TechnicianController::update
POST /technicians/{id}/delete   → TechnicianController::delete

GET  /work-orders               → WorkOrderController::index
POST /work-orders               → WorkOrderController::store
GET  /work-orders/{id}          → WorkOrderController::show
GET  /api/work-orders/{id}/parts → WorkOrderController::getParts (JSON)

GET  /checkins                  → CheckinController::index
GET  /checkins/create           → CheckinController::create
POST /checkins                  → CheckinController::store

GET  /checkouts                 → CheckoutController::index
GET  /checkouts/create          → CheckoutController::create
POST /checkouts                 → CheckoutController::store

GET  /returns                   → ReturnController::index
GET  /checkins/work-order       → CheckinController::workOrderReturn
POST /checkins/work-order       → CheckinController::storeWorkOrderReturn
GET  /returns/supplier          → ReturnController::supplierReturn
POST /returns/supplier          → ReturnController::storeSupplierReturn
```

---

## 📝 VIEW TEMPLATE STRUCTURE

**Standard View Pattern:**
```php
<!-- app/Views/module/action.php -->
<div class="mb-6">
    <h1 class="text-3xl font-bold text-dark mb-2">Page Title</h1>
    <p class="text-gray-600">Description</p>
</div>

<!-- Action buttons -->
<div class="mb-6 flex gap-4">
    <a href="/path" class="bg-primary text-dark px-4 py-2 rounded-lg hover:bg-secondary">
        Button Text
    </a>
</div>

<!-- Content (table, form, cards, etc.) -->
<div class="bg-white rounded-lg shadow">
    <!-- Content here -->
</div>
```

**Form Pattern:**
```php
<form method="POST" action="/endpoint" class="space-y-6">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Field Label <span class="text-danger">*</span>
        </label>
        <input type="text" name="field_name" required
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
    </div>
    
    <div class="flex gap-4">
        <button type="submit" class="bg-primary text-dark px-6 py-2 rounded-lg hover:bg-secondary">
            Save
        </button>
        <a href="/back" class="bg-gray-200 text-dark px-6 py-2 rounded-lg hover:bg-gray-300">
            Cancel
        </a>
    </div>
</form>
```

**Table Pattern:**
```php
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-dark">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Column</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($items as $item): ?>
            <tr class="hover:bg-info">
                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($item['field']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

---

## 🔧 DEVELOPMENT COMMANDS

```bash
cd /mnt/shared/projects/partops/v4

# Install dependencies
make install

# Setup (creates .env, directories)
make setup

# Run migrations
make migrate
# OR: php scripts/migrate.php

# Seed database
make seed
# OR: php scripts/seed.php

# Start development server
make run
# OR: php -S localhost:8000 -t public

# Clean cache/logs
make clean
```

---

## 🗄️ DATABASE ACCESS

**Connection Details:**
- Host: localhost
- Port: 3306
- Database: `partsdb`
- User: `partsuser`
- Password: `eu9MB6!fh2@PO`

**Using Database in Code:**
```php
use App\Core\Database;

// Query
$results = Database::fetchAll("SELECT * FROM parts WHERE id = ?", [$id]);

// Single row
$part = Database::fetch("SELECT * FROM parts WHERE id = ? LIMIT 1", [$id]);

// Insert/Update/Delete
Database::query("INSERT INTO parts (name) VALUES (?)", [$name]);
```

---

## 📚 KEY REFERENCES

**Prototype Files (for UI reference):**
- `_prototype-index.html` - Full prototype with all forms
- `_prototype-qr-scanner.html` - Mobile QR scanner
- `_prototype-part-detail.html` - Desktop part detail

**Schema:**
- `schema.sql` - Complete database schema

**Documentation:**
- `partops.md` - Original specification
- `COMPLETION_SUMMARY.md` - Milestone recap
- `VIEWS_COMPLETED.md` - Details on the 17 finished templates
- `TESTING_GUIDE.md` - Manual and future automated test notes
- `ERROR_LOGGING.md` - Error logging pipeline and viewer

---

## 🎯 NEXT IMMEDIATE STEPS

1. Run through `TESTING_GUIDE.md` to validate every workflow and JS interaction
2. Add CSRF tokens and stronger validation across all forms
3. Implement pagination and export (CSV/PDF) for large lists
4. Plan authentication/authorization before production use

---

## 💡 IMPORTANT NOTES

1. **All views use the layout** - Content is injected into `$content` variable
2. **Flash messages** - Use `$_SESSION['success']` or `$_SESSION['error']`
3. **HTML escaping** - Always use `htmlspecialchars()` for output
4. **Forms use POST** - Even for updates/deletes (no PUT/DELETE verbs)
5. **QR codes** - Currently using `api.qrserver.com` API in prototype
6. **No JavaScript framework** - Keep it simple with vanilla JS or Alpine.js
7. **Mobile-first** - Tailwind responsive classes (sm:, md:, lg:)
8. **Work orders are reference containers** - NOT job management (no status tracking)

---

## 🚀 TO CONTINUE DEVELOPMENT

**Say:** "Proceed with testing and hardening for PartOps v4 per CONTINUATION_GUIDE.md next steps."

The AI will continue with QA, security, and polish work.

---

**Last Updated:** December 2024  
**Status:** Core framework and all essential views complete  
**Progress:** ~75% complete (views done, testing and enhancements needed)
