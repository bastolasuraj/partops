# PartOps - Parts Inventory Management System

## Overview
A comprehensive PHP MVC Parts Inventory Management System for parts departments featuring multi-supplier tracking with core charge accounting, LDAP authentication for users (local DB for admins), QR code scanning for fast lookup, location-based inventory (aisle/shelf/bay), work order integration, and full audit logging.

## Current State
- **Status**: MVP Complete
- **Last Updated**: December 2025
- **Stack**: PHP 8.2 MVC, MySQL 8, PSR-4 Autoloading, Tailwind-inspired CSS

## Architecture

### Directory Structure
```
/
├── public/                 # Web root
│   ├── index.php          # Front controller
│   ├── assets/            # Static assets (CSS, JS)
│   ├── .htaccess          # Apache URL rewriting
│   └── web.config         # IIS URL rewriting
├── src/
│   ├── Config/            # Routes and configuration
│   ├── Controllers/       # MVC Controllers (12 controllers)
│   ├── Middleware/        # Auth, CSRF, RateLimit middleware
│   ├── Models/            # Database models (10 models)
│   ├── Services/          # Core services (Router, Auth, DB, etc.)
│   └── Views/             # PHP view templates with layouts
├── database/
│   ├── migrations/        # SQL schema migrations
│   └── seeds/             # Sample data
├── storage/
│   ├── logs/              # Application logs
│   └── cache/             # Cache files
└── vendor/                # Composer dependencies
```

### Key Features
1. **Multi-Supplier Tracking**: Link multiple suppliers to each part with individual pricing
2. **Core Charge Accounting**: Track core charges, expected rebates, and actual rebates received
3. **Anchor Slugs**: Unique identifiers that link OEM, aftermarket, and historical part numbers
4. **Location-Based Inventory**: Aisle/Shelf/Bay/Bin tracking
5. **Work Order Integration**: Link part checkouts to work orders and technicians
6. **QR Code Scanning**: Signed payloads for secure, fast part lookup
7. **Full Audit Logging**: Track all system activities with correlation IDs

### Security Features
- **CSRF Protection**: Token-based protection on all forms
- **Rate Limiting**: Prevents abuse on stock-changing endpoints
- **Idempotency Keys**: Prevents duplicate inventory transactions
- **SameSite Strict Cookies**: Session security
- **Password Hashing**: bcrypt for local admin accounts

### Authentication
- **Regular Users (role: user)**: LDAP authentication
- **Admins (role: admin)**: Local database accounts with hashed passwords
- **Default Admin**: username: `admin`, password: `password`

## Database Schema
- **users**: User accounts (LDAP or local)
- **parts**: Part catalog with anchor slugs
- **part_numbers**: OEM/aftermarket/historical part numbers
- **suppliers**: Supplier information
- **part_suppliers**: Part-supplier pricing relationships
- **locations**: Warehouse locations (aisle/shelf/bay/bin)
- **technicians**: Technician records
- **work_orders**: Work order tracking
- **inventory_levels**: Current stock per location
- **inventory_moves**: Movement history with snapshots
- **audit_log**: System audit trail

## Configuration

### Environment Variables (set in Secrets)
- `DATABASE_URL`: MySQL connection string (e.g., `mysql:host=localhost;port=3306;dbname=partsdb;charset=utf8mb4`)
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`: MySQL connection parts (used if `DATABASE_URL` is not set)
- `LDAP_ENABLED`: Enable LDAP authentication (true/false)
- `LDAP_HOST`: LDAP server hostname
- `LDAP_PORT`: LDAP port (default: 389)
- `LDAP_BASE_DN`: LDAP base DN for user lookup
- `SESSION_SECRET`: Session encryption key (auto-provided)

### Web Server Support
- **Apache**: Uses `.htaccess` for URL rewriting
- **IIS**: Uses `web.config` for URL rewriting
- **Development**: PHP built-in server via workflow

## Development

### Running Locally
The workflow runs: `php -S 0.0.0.0:5000 -t public`

### Adding New Features
1. Create model in `src/Models/`
2. Create controller in `src/Controllers/`
3. Add routes in `src/Config/routes.php`
4. Create views in `src/Views/`
5. Run database migrations

### Coding Standards
- PSR-4 autoloading with `PartOps\` namespace
- Strict types enabled
- All user input validated and escaped
- No raw SQL in controllers (use models)

## User Preferences
- Dark theme with warm accent colors (rust red, gold)
- Clean, card-based UI design
- Mobile-responsive layout

## Recent Changes
- December 2025: Initial MVP implementation
  - Complete MVC structure
  - All core CRUD operations
  - Receiving, checkout, returns workflows
  - Reports and audit logging
  - QR code scanning support
