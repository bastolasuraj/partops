# PartOps v2 - Parts Inventory Management System

A PHP MVC application for managing parts inventory, including procurement, storage, technician checkout, and returns (including core charges).

## Features

- **Parts Management**: Track parts with multiple part numbers (OEM, aftermarket, historical)
- **Supplier Management**: Multiple suppliers per part with pricing and core charges
- **Location Tracking**: Aisle/Shelf/Bay storage locations
- **Receiving**: New parts and work order returns
- **Checkout**: Assign parts to work orders and technicians
- **Returns**: Standard returns and core returns with rebate tracking
- **Audit Trail**: Complete history of all inventory movements

## Quick Start

```bash
# 1. Install dependencies
composer install

# 2. Copy environment file and configure
cp .env.example .env

# 3. Run migrations
php scripts/migrate.php

# 4. Seed sample data (optional)
php scripts/seed.php

# 5. Start development server
php -S localhost:8000 -t public
```

## Key Concepts

### Parts Receiving
- **New Parts**: Fresh inventory from suppliers
- **Work Order Returns**: Unused parts returned from work orders

### Checkout
- Parts are checked out to a work order with an assigned technician
- Multiple parts can be assigned to a single work order

### Returns
- **Standard Return**: Returning new unused parts to inventory
- **Core Return**: Returning used parts with core charge for rebate

### Locations
- Parts are stored in Aisle -> Shelf -> Bay (optional Bin)
- Each part can exist in multiple locations
