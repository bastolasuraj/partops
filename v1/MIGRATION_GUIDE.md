# Migration Guide: src → app

## Summary of Changes

The project directory structure has been updated to follow Laravel-style conventions:

- **Old:** `src/` directory
- **New:** `app/` directory

## What Changed

### Directory Structure

```
Before:                  After:
v1/src/                  v1/app/
├── Config/              ├── Config/
├── Controllers/         ├── Controllers/
├── Core/                ├── Core/
├── Models/              ├── Models/
├── Views/               ├── Views/
├── bootstrap.php        ├── bootstrap.php
└── routes.php           └── routes.php
```

### Configuration Files

1. **composer.json**
   - Autoload path: `"PartOps\\": "src/"` → `"PartOps\\": "app/"`

2. **public/index.php**
   - Bootstrap path: `../src/bootstrap.php` → `../app/bootstrap.php`

3. **.env.example**
   - `APP_URL`: `http://localhost:8000` → `https://php.sbastola.com/partops`
   - `DB_NAME`: `partops` → `partsdb`
   - `DB_USER`: `partops_user` → `partsuser`

## What Stayed the Same

✓ **Namespaces:** All PHP namespaces remain `PartOps\*`  
✓ **Database Schema:** No database changes  
✓ **Functionality:** All features work identically  
✓ **Routes:** All URL routes unchanged  
✓ **Views:** View paths unchanged  

## Migration Steps

### For New Installations

No action needed - just follow the normal setup:

```bash
composer install
cp .env.example .env
# Edit .env with your settings
make setup
make run
```

### For Existing Installations

If you already have the project set up:

```bash
# 1. Pull the latest changes
git pull

# 2. Regenerate autoload files
composer dump-autoload

# 3. Restart your development server
make run
```

That's it! No database migrations or code changes needed.

## Verification

Run the verification script to ensure everything is working:

```bash
php scripts/verify-setup.php
```

Expected output:
```
✓ PHP version
✓ All extensions loaded
✓ Configuration files present
✓ Composer dependencies installed
✓ Storage directories writable
✓ Database connection successful
```

## Troubleshooting

### Issue: "Class not found" errors

**Solution:** Regenerate autoload files
```bash
composer dump-autoload
```

### Issue: "View not found" errors

**Solution:** Check that the `app/Views/` directory exists
```bash
ls -la app/Views/
```

### Issue: Bootstrap file not found

**Solution:** Verify the path in `public/index.php`
```bash
grep bootstrap public/index.php
# Should show: ../app/bootstrap.php
```

## Why This Change?

1. **Industry Standard:** Laravel and many modern PHP frameworks use `app/` directory
2. **Clarity:** More intuitive name for application code
3. **Convention:** Aligns with common PHP project structures
4. **Separation:** Clear distinction between application code and other directories

## Impact

- **Breaking Changes:** None (if you regenerate autoload)
- **Database Changes:** None
- **Configuration Changes:** Only `.env.example` defaults updated
- **Code Changes:** None (namespaces unchanged)

## Questions?

Refer to:
- `README.md` - Project overview
- `DEVELOPMENT.md` - Development guide
- `CHANGELOG.md` - Detailed change log
