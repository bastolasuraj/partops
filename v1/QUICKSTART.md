# PartOps v1 - Quick Start Guide

## 5-Minute Setup

### Step 1: Install Dependencies

```bash
cd /mnt/shared/projects/partops/v1
composer install
```

### Step 2: Configure Environment

```bash
cp .env.example .env
```

Edit `.env` and set your database credentials:

```ini
DB_HOST=localhost
DB_PORT=3306
DB_NAME=partops
DB_USER=your_username
DB_PASS=your_password
```

### Step 3: Create Database

```sql
CREATE DATABASE partops CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Step 4: Run Setup

```bash
make setup
```

This will:
- Run database migrations
- Seed sample data
- Verify the setup

### Step 5: Start Server

```bash
make run
```

Visit: **http://localhost:8000**

### Step 6: Login

Use the default credentials:
- **Username:** `admin`
- **Password:** `admin123`

## What You Get

After setup, you'll have:

✓ Working authentication system  
✓ Dashboard with navigation  
✓ Parts management interface  
✓ Suppliers management  
✓ Locations management  
✓ Inventory operations interface  
✓ Work orders tracking  
✓ Complete database schema  
✓ Sample data to explore  

## Next Steps

1. **Explore the Interface**
   - Navigate through Parts, Suppliers, Locations
   - Check out the Inventory management page
   - View Work Orders

2. **Review the Code**
   - Check `src/Controllers/` for request handling
   - Look at `src/Models/` for data operations
   - Explore `src/Views/` for templates

3. **Read Documentation**
   - `README.md` - Project overview
   - `DEVELOPMENT.md` - Development guide
   - `PROJECT_SUMMARY.md` - Detailed summary
   - `/docs/` - Full specifications

4. **Start Development**
   - Implement CRUD operations (currently stubs)
   - Add validation and error handling
   - Build out inventory workflows

## Troubleshooting

### Can't connect to database?
```bash
# Check MySQL is running
sudo systemctl status mysql

# Test connection
mysql -u your_username -p
```

### Composer not found?
```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Permission errors?
```bash
# Make storage writable
chmod -R 775 storage/
```

### Still having issues?
```bash
# Run verification script
php scripts/verify-setup.php
```

## Available Commands

```bash
make help      # Show all commands
make setup     # Complete setup
make migrate   # Run migrations
make seed      # Seed data
make run       # Start server
make clean     # Clear cache/logs
make test      # Run tests (when implemented)
```

## Project Structure

```
v1/
├── public/         # Web root (index.php)
├── app/            # Application code
│   ├── Controllers/
│   ├── Models/
│   ├── Views/
│   └── Core/
├── database/       # Migrations & seeds
├── scripts/        # Helper scripts
└── storage/        # Logs, cache, temp
```

## Key Files

- `.env` - Configuration (create from .env.example)
- `src/routes.php` - URL routing
- `src/bootstrap.php` - App initialization
- `composer.json` - Dependencies
- `Makefile` - Common commands

## Default Accounts

After seeding:

| Username | Password  | Role  |
|----------|-----------|-------|
| admin    | admin123  | admin |

**⚠️ Change the password immediately in production!**

## Support

- Documentation: `/docs` directory
- Development guide: `DEVELOPMENT.md`
- Project summary: `PROJECT_SUMMARY.md`

---

**Ready to build!** 🚀

The foundation is complete. Now you can implement the full CRUD operations, inventory workflows, and advanced features as outlined in the project documentation.
