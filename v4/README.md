# PartOps v4 - Parts Inventory Management System

A modern PHP MVC application for managing parts inventory, suppliers, technicians, and work orders.

## Features

- ✅ Parts management with QR code support
- ✅ Supplier and technician tracking
- ✅ Work order management
- ✅ Parts check-in/checkout system
- ✅ Work order and supplier returns
- ✅ Core charge tracking
- ✅ Real-time inventory levels
- ✅ Transaction history
- ✅ Comprehensive error logging system
- ✅ Responsive design with Tailwind CSS

## Requirements

- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Apache/Nginx with mod_rewrite enabled

## Installation

### 1. Clone and Install Dependencies

```bash
cd /mnt/shared/projects/partops/v4
make install
```

### 2. Setup Environment

```bash
make setup
```

Edit `.env` file with your database credentials:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=partops_v4
DB_USER=root
DB_PASS=your_password
```

### 3. Run Migrations

```bash
make migrate
```

### 4. Seed Database (Optional)

```bash
make seed
```

### 5. Start Development Server

```bash
make run
```

Visit: http://localhost:8000

## Project Structure

```
v4/
├── app/
│   ├── Controllers/     # Application controllers
│   ├── Models/          # Database models
│   ├── Views/           # View templates
│   └── Core/            # Core framework classes
├── public/              # Public web root
│   ├── assets/          # CSS, JS, images
│   └── index.php        # Entry point
├── routes/              # Route definitions
├── scripts/             # CLI scripts
├── storage/             # Cache, logs, uploads
├── .env.example         # Environment template
├── composer.json        # PHP dependencies
├── Makefile             # Build commands
└── schema.sql           # Database schema
```

## Available Commands

```bash
make help       # Show all available commands
make install    # Install PHP dependencies
make setup      # Setup application
make migrate    # Run database migrations
make seed       # Seed database with sample data
make run        # Start development server
make test       # Run tests
make clean      # Clean cache and logs
make reset      # Reset application
```

## Error Logging

PartOps v4 includes a comprehensive error logging system that captures:
- PHP errors (warnings, notices, fatal errors)
- MySQL/database errors
- Uncaught exceptions
- Application-level errors

### View Logs
Visit: http://localhost:8000/logs

### Test Logging
```bash
php scripts/test-logger.php
```

### Log in Code
```php
use App\Core\Logger;

Logger::info('Operation completed', ['user_id' => 123]);
Logger::warning('Low stock detected', ['part_id' => 456]);
Logger::logApp('Failed to process', 'ERROR', ['data' => $data]);
```

**Documentation:** See `ERROR_LOGGING.md` for complete guide.

## Database Schema

The application uses 8 core tables:

- `suppliers` - Parts suppliers/manufacturers
- `technicians` - People checking parts in/out
- `parts` - Master parts list
- `work_orders` - Work order tracking
- `part_checkins` - Incoming stock records
- `part_checkouts` - Parts issued to work orders
- `work_order_returns` - Parts returned from work orders
- `returns` - Supplier returns (standard and core charge)

## Development

### Adding a New Route

Edit `routes/web.php`:

```php
$router->get('/your-route', [YourController::class, 'method']);
```

### Creating a Controller

```php
<?php
namespace App\Controllers;
use App\Core\View;

class YourController
{
    public function index(): void
    {
        View::render('your-view', ['data' => 'value']);
    }
}
```

### Creating a Model

```php
<?php
namespace App\Models;
use App\Core\Model;

class YourModel extends Model
{
    protected string $table = 'your_table';
}
```

## Security

- CSRF protection enabled
- SQL injection prevention via PDO prepared statements
- XSS protection via output escaping
- Secure session handling
- Environment-based configuration

## License

MIT License

## Support

For issues and questions, see the root markdown guides (`CONTINUATION_GUIDE.md`, `TESTING_GUIDE.md`,
`COMPLETION_SUMMARY.md`, `ERROR_LOGGING.md`, `VIEWS_COMPLETED.md`) or open an issue with details.
