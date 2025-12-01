# Development Guide

## Getting Started

### Prerequisites
- PHP 8.1+
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Apache or IIS

### Initial Setup

```bash
# Clone and navigate to v1 directory
cd /path/to/partops/v1

# Install dependencies
composer install

# Configure environment
cp .env.example .env
nano .env  # Edit with your settings

# Setup database
make migrate
make seed

# Verify setup
php scripts/verify-setup.php

# Start server
make run
```

## Project Architecture

### MVC Pattern

```
Request → Router → Controller → Model → Database
                      ↓
                    View → Response
```

### Request Flow

1. All requests hit `public/index.php`
2. Bootstrap loads config and initializes app
3. Router matches URL to controller method
4. Controller processes request
5. Model handles data operations
6. View renders response

## Adding New Features

### Create a New Controller

```php
<?php
namespace PartOps\Controllers;

use PartOps\Core\Controller;

class MyController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();  // Require authentication
        $data = ['title' => 'My Page'];
        $this->view('my.index', $data);
    }
    
    public function store(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid token'], 403);
        }
        
        // Process form data
        $name = $this->post('name');
        
        // Return response
        $this->redirect('/my-resource');
    }
}
```

### Create a New Model

```php
<?php
namespace PartOps\Models;

use PartOps\Core\Model;

class MyModel extends Model
{
    protected string $table = 'my_table';
    
    public function findByCustomField(string $value): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE custom_field = ? LIMIT 1"
        );
        $stmt->execute([$value]);
        return $stmt->fetch() ?: null;
    }
}
```

### Add Routes

Edit `app/routes.php`:

```php
$router->get('/my-resource', [MyController::class, 'index']);
$router->post('/my-resource', [MyController::class, 'store']);
$router->get('/my-resource/{id}', [MyController::class, 'show']);
```

### Create Views

Create `app/Views/my/index.php`:

```php
<?php ob_start(); ?>

<h1><?= htmlspecialchars($title) ?></h1>
<p>Your content here</p>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
```

## Database Operations

### Using Models

```php
// Find by ID
$part = $partModel->find(1);

// Find all with conditions
$parts = $partModel->all(['is_active' => 1]);

// Create
$id = $partModel->create([
    'name' => 'New Part',
    'anchor_slug' => 'new-part-001'
]);

// Update
$partModel->update($id, ['name' => 'Updated Name']);

// Delete (soft delete if is_active column exists)
$partModel->delete($id);

// Count
$count = $partModel->count(['is_active' => 1]);
```

### Raw Queries

```php
// SELECT query
$results = $this->query(
    "SELECT * FROM parts WHERE name LIKE ?",
    ['%search%']
);

// INSERT/UPDATE/DELETE
$success = $this->execute(
    "UPDATE parts SET is_active = 0 WHERE id = ?",
    [$id]
);
```

### Transactions

```php
use PartOps\Config\Database;

try {
    Database::beginTransaction();
    
    // Your operations here
    $partModel->create($data);
    $inventoryModel->update($id, $changes);
    
    Database::commit();
} catch (Exception $e) {
    Database::rollback();
    throw $e;
}
```

## Security Best Practices

### CSRF Protection

Always include CSRF token in forms:

```php
<form method="POST">
    <input type="hidden" name="_csrf_token" 
           value="<?= $this->generateCsrf() ?>">
    <!-- form fields -->
</form>
```

Validate in controller:

```php
if (!$this->validateCsrf()) {
    $this->json(['error' => 'Invalid token'], 403);
}
```

### Input Validation

```php
// Get and validate input
$name = $this->post('name');
if (empty($name)) {
    $this->json(['error' => 'Name required'], 400);
}

// Sanitize
$name = trim($name);
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
```

### Authentication

```php
// Require any authenticated user
$this->requireAuth();

// Require admin role
$this->requireAdmin();

// Check manually
if (!isset($_SESSION['user_id'])) {
    $this->redirect('/login');
}
```

## Testing

### Running Tests

```bash
make test
```

### Writing Tests

Create tests in `tests/` directory:

```php
<?php
namespace PartOps\Tests;

use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    public function testSomething(): void
    {
        $this->assertTrue(true);
    }
}
```

## Debugging

### Enable Debug Mode

In `.env`:

```
APP_DEBUG=true
```

### Logging

```php
error_log("Debug message: " . print_r($data, true));
```

### Database Queries

Enable query logging in development:

```php
$db->setAttribute(PDO::ATTR_STATEMENT_CLASS, ['LoggingStatement']);
```

## Common Tasks

### Add a Migration

Create `database/migrations/002_my_changes.sql`:

```sql
-- Add new column
ALTER TABLE parts ADD COLUMN new_field VARCHAR(255) NULL;

-- Create new table
CREATE TABLE my_table (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Run: `make migrate`

### Add Sample Data

Edit `database/seeds/001_sample_data.sql` or create new seed file.

Run: `make seed`

### Clear Cache

```bash
make clean
```

## Code Style

- Follow PSR-4 autoloading
- Use type declarations
- Keep line length ≤100 characters
- Use meaningful names
- Comment complex logic
- Prefer explicit over implicit

### Example

```php
<?php
declare(strict_types=1);

namespace PartOps\Services;

class InventoryService
{
    /**
     * Process part checkout with validation
     */
    public function checkout(
        int $partId,
        int $quantity,
        int $technicianId,
        int $workOrderId
    ): bool {
        // Implementation
    }
}
```

## Troubleshooting

### Database Connection Failed
- Check `.env` credentials
- Verify MySQL is running
- Check firewall settings

### 404 on All Routes
- Verify `.htaccess` or `web.config` is present
- Check mod_rewrite (Apache) or URL Rewrite (IIS) is enabled
- Ensure `public/` is the web root

### Session Issues
- Check `storage/` directories are writable
- Verify session settings in `php.ini`
- Clear browser cookies

### Composer Errors
- Run `composer clear-cache`
- Delete `vendor/` and run `composer install` again
- Check PHP version compatibility

## Resources

- [PHP Documentation](https://www.php.net/docs.php)
- [PSR Standards](https://www.php-fig.org/psr/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- Project docs in `/docs` directory

## Getting Help

1. Check this guide
2. Review project documentation in `/docs`
3. Check error logs in `storage/logs/`
4. Run `php scripts/verify-setup.php`
5. Contact the development team
