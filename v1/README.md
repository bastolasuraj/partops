# PartOps - Parts Inventory Tracker

A PHP-based inventory management system for parts departments with QR scanning, core charge tracking, and comprehensive audit trails.

## Project Status

**Phase 1: Foundation** - In Development

- ✓ MVC architecture with PSR-4 autoloading
- ✓ Database schema (MySQL)
- ✓ Configuration management (.env)
- ✓ Authentication system (LDAP + local)
- ✓ Base controllers and models
- ✓ Routing system
- ⧗ CRUD operations (in progress)
- ⧗ Search functionality (pending)

## Requirements

- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Apache with mod_rewrite OR IIS with URL Rewrite module

## Quick Start

```bash
# 1. Install dependencies
composer install

# 2. Configure environment
cp .env.example .env
# Edit .env with your database credentials

# 3. Run setup (migrate + seed)
make setup

# 4. Start development server
make run
```

Visit http://localhost:8000

## Default Credentials

After seeding, login with:
- **Username:** admin
- **Password:** admin123

**Important:** Change this password immediately in production!

## Available Commands

```bash
make help      # Show available commands
make setup     # Complete setup (install + migrate + seed)
make install   # Install dependencies
make migrate   # Run database migrations
make seed      # Seed sample data
make test      # Run tests
make run       # Start development server
make clean     # Clean cache and logs
```

## Project Structure

```
v1/
├── public/              # Web root
│   ├── index.php       # Front controller
│   ├── .htaccess       # Apache config
│   └── web.config      # IIS config
├── app/
│   ├── Config/         # Configuration classes
│   ├── Controllers/    # Request handlers
│   ├── Core/           # Core framework classes
│   ├── Models/         # Data models
│   ├── Views/          # View templates
│   ├── bootstrap.php   # Application bootstrap
│   └── routes.php      # Route definitions
├── database/
│   ├── migrations/     # Database migrations
│   └── seeds/          # Sample data
├── scripts/            # Helper scripts
├── storage/            # Logs, cache, temp files
├── tests/              # Unit and integration tests
├── .env.example        # Environment template
├── composer.json       # PHP dependencies
└── Makefile           # Development commands
```

## Phase Roadmap

### Phase 1: Foundation (Current)
- MVC skeleton
- Database schema
- Basic CRUD operations
- Authentication
- Search functionality

### Phase 2: Workflows
- Receiving with transactions
- Checkout to technicians
- Returns and core returns
- Stock adjustments with idempotency

### Phase 3: QR/Scanner
- QR code generation
- Camera scanning
- Quick stock actions
- Mobile-optimized interface

### Phase 4: Reporting & Alerts
- Stock reports
- Core liability tracking
- Usage analytics
- Low stock alerts

### Phase 5: Polishing
- PDF exports
- Performance optimization
- UI/UX improvements
- Security hardening

## Documentation

See the `docs/` directory in the project root for detailed documentation:

- `ProjectRequirement.md` - Full requirements specification
- `ProjectDesign.md` - Architecture and design decisions
- `ProjectDetailsRefined.md` - Detailed feature descriptions

## Security

- Never commit `.env` file
- Use prepared statements for all database queries
- Validate CSRF tokens on POST requests
- Implement rate limiting on sensitive endpoints
- Use HTTPS in production
- Keep dependencies updated

## License

Proprietary - All rights reserved
