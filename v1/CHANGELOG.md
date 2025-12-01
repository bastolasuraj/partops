# Changelog

All notable changes to the PartOps v1 project.

## [Unreleased] - 2025-01-31

### Changed

- **Directory Structure:** Renamed `src/` to `app/` for better Laravel-style convention
  - Updated `composer.json` autoload path from `src/` to `app/`
  - Updated `public/index.php` bootstrap path
  - Regenerated Composer autoload files
  - Updated all documentation references

- **Configuration:** Updated `.env.example` to match production settings
  - Changed `APP_URL` from `http://localhost:8000` to `https://php.sbastola.com/partops`
  - Changed `DB_NAME` from `partops` to `partsdb`
  - Changed `DB_USER` from `partops_user` to `partsuser`

### Files Modified

1. `composer.json` - Updated PSR-4 autoload path
2. `public/index.php` - Updated bootstrap path
3. `.env.example` - Updated APP_URL and database settings
4. `README.md` - Updated directory structure documentation
5. `PROJECT_SUMMARY.md` - Updated directory references
6. `DEVELOPMENT.md` - Updated code examples and paths
7. `QUICKSTART.md` - Updated directory references

### Migration Notes

If you have an existing installation:

```bash
# Regenerate autoload files
composer dump-autoload

# No database changes required
# No code changes required (namespace paths remain the same)
```

The namespace `PartOps\` remains unchanged - only the physical directory name changed from `src` to `app`.

## [Initial] - 2025-01-31

### Added

- Complete MVC framework with PSR-4 autoloading
- Database schema with 13 tables
- Authentication system (session-based with CSRF)
- Base controllers for all major features
- Models for core entities
- View templates with responsive layout
- Migration and seed system
- Development tools (Makefile, scripts)
- Comprehensive documentation
- Apache and IIS server configuration
