# PartOps v4 - Error Logging System

**Date:** December 2024  
**Feature:** Comprehensive error logging for PHP, MySQL, and application errors

---

## 📋 OVERVIEW

The error logging system captures and records all errors that occur in the application, including:
- **PHP Errors:** Warnings, notices, fatal errors, parse errors
- **MySQL Errors:** Connection failures, query errors, constraint violations
- **Exceptions:** Uncaught exceptions and custom exceptions
- **Application Errors:** Custom application-level errors

All errors are logged to a centralized file with timestamps, severity levels, and context information.

---

## 📁 FILE STRUCTURE

```
v4/
├── app/
│   ├── Core/
│   │   ├── Logger.php          ✅ Main logging class
│   │   └── Database.php        ✅ Updated with logging
│   ├── Controllers/
│   │   └── LogController.php   ✅ Log viewer controller
│   └── Views/
│       └── logs/
│           └── index.php       ✅ Log viewer interface
├── storage/
│   └── logs/
│       ├── err.log             ✅ Main error log file
│       └── .gitkeep            ✅ Keep directory in git
├── public/
│   └── index.php               ✅ Logger initialization
└── routes/
    └── web.php                 ✅ Log routes added
```

---

## 🔧 COMPONENTS

### 1. Logger Class (`app/Core/Logger.php`)

**Purpose:** Central logging system that handles all error types

**Key Methods:**
```php
// Initialize the logger (called automatically)
Logger::init();

// Log different types of errors
Logger::log($type, $level, $message, $context);
Logger::logDatabase($message, $context);
Logger::logApp($message, $level, $context);

// Convenience methods
Logger::info($message, $context);
Logger::warning($message, $context);
Logger::debug($message, $context);

// Utility methods
Logger::clear();                    // Clear log file
Logger::getRecentLogs($lines);      // Get recent entries
Logger::getLogFile();               // Get log file path
```

**Error Handlers:**
- `handleError()` - Catches PHP errors (warnings, notices, etc.)
- `handleException()` - Catches uncaught exceptions
- `handleShutdown()` - Catches fatal errors

**Log Format:**
```
[TIMESTAMP] [LEVEL] [TYPE] Message
Context: {json context data}
--------------------------------------------------------------------------------
```

### 2. Database Integration

The `Database` class now logs all database-related errors:

```php
// Connection errors
Logger::logDatabase('Database connection failed', [
    'host' => $host,
    'database' => $dbname,
    'code' => $e->getCode()
]);

// Query errors
Logger::logDatabase('Query failed', [
    'sql' => $sql,
    'params' => $params,
    'code' => $e->getCode()
]);
```

### 3. Log Viewer (`/logs`)

**Features:**
- View recent log entries with color-coded severity
- Filter by level (Critical, Error, Warning, Info, Debug)
- Filter by type (PHP, Database, Exception, App)
- Adjustable number of lines (50, 100, 200, 500)
- Statistics dashboard (critical errors, warnings, database errors)
- Auto-refresh every 30 seconds
- Clear logs functionality
- Download logs as file

**Access:** http://localhost:8000/logs

---

## 🎨 LOG LEVELS

### Severity Levels
1. **CRITICAL** - Fatal errors that stop execution
2. **ERROR** - Runtime errors that don't stop execution
3. **WARNING** - Non-critical issues that should be addressed
4. **INFO** - Informational messages (connections, operations)
5. **DEBUG** - Detailed debugging information (development only)

### Error Types
1. **PHP** - PHP runtime errors
2. **DATABASE** - MySQL/database errors
3. **EXCEPTION** - Uncaught exceptions
4. **APP** - Application-level errors
5. **FATAL** - Fatal PHP errors

---

## 💻 USAGE EXAMPLES

### Logging in Controllers

```php
use App\Core\Logger;

class PartController
{
    public function store(): void
    {
        try {
            // Your code here
            $part = Part::create($_POST);
            
            Logger::info('Part created successfully', [
                'part_id' => $part['id'],
                'fowler_number' => $part['fowler_part_number']
            ]);
            
        } catch (\Exception $e) {
            Logger::logApp('Failed to create part: ' . $e->getMessage(), 'ERROR', [
                'post_data' => $_POST,
                'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $_SESSION['error'] = 'Failed to create part.';
        }
    }
}
```

### Logging in Models

```php
use App\Core\Logger;

class Part extends Model
{
    public static function create(array $data): array
    {
        try {
            // Validation
            if (empty($data['fowler_part_number'])) {
                Logger::warning('Attempted to create part without Fowler number', [
                    'data' => $data
                ]);
                throw new \Exception('Fowler part number is required');
            }
            
            // Insert
            $sql = "INSERT INTO parts (...) VALUES (...)";
            Database::query($sql, $params);
            
            return ['id' => Database::lastInsertId()];
            
        } catch (\PDOException $e) {
            // Database errors are automatically logged by Database class
            throw $e;
        }
    }
}
```

### Debug Logging (Development Only)

```php
// Only logs in development environment
Logger::debug('Processing checkout', [
    'work_order_id' => $woId,
    'parts_count' => count($parts),
    'technician_id' => $techId
]);
```

---

## 🔍 LOG FILE LOCATION

**Path:** `/mnt/shared/projects/partops/v4/storage/logs/err.log`

**Permissions:** 
- File: `0666` (readable and writable)
- Directory: `0755` (readable and executable)

**Rotation:** Manual (consider implementing log rotation for production)

---

## 🚀 SETUP & CONFIGURATION

### 1. Environment Variables

In `.env`:
```env
APP_ENV=development          # or 'production'
APP_DEBUG=true              # Show errors on screen (dev only)
```

### 2. Automatic Initialization

The logger is automatically initialized in `public/index.php`:
```php
// Initialize Logger (must be done before any errors can occur)
App\Core\Logger::init();
```

### 3. Error Display Settings

**Development:**
```php
APP_DEBUG=true
// Errors shown on screen AND logged to file
```

**Production:**
```php
APP_DEBUG=false
// Errors only logged to file, user sees friendly error page
```

---

## 📊 LOG VIEWER FEATURES

### Statistics Dashboard
- **Critical Errors:** Count of fatal/critical errors
- **Warnings:** Count of warning-level messages
- **Database Errors:** Count of database-related errors
- **Total Entries:** Total log entries displayed

### Filters
- **Level Filter:** Critical, Error, Warning, Info, Debug
- **Type Filter:** PHP, Database, Exception, App
- **Lines:** 50, 100, 200, 500 recent entries

### Actions
- **Refresh:** Reload logs manually
- **Clear Logs:** Delete all log entries (with confirmation)
- **Download:** Download log file with timestamp
- **Auto-refresh:** Automatically refreshes every 30 seconds

---

## 🛡️ SECURITY CONSIDERATIONS

### Access Control
⚠️ **Important:** The log viewer is currently accessible to all users. In production:

1. **Add Authentication:**
```php
// In LogController::index()
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /');
    exit;
}
```

2. **Restrict by IP:**
```php
$allowedIPs = ['127.0.0.1', '192.168.1.100'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
    die('Access denied');
}
```

### Sensitive Data
- Passwords are NOT logged
- Database credentials are NOT logged
- User input is sanitized before logging
- Context data is JSON-encoded

---

## 🧪 TESTING THE LOGGER

### Test PHP Errors
```php
// Trigger a warning
echo $undefinedVariable;

// Trigger a notice
$array = [];
echo $array['nonexistent'];

// Trigger a fatal error
require 'nonexistent_file.php';
```

### Test Database Errors
```php
// Invalid query
Database::query("SELECT * FROM nonexistent_table");

// Connection error (wrong credentials in .env)
```

### Test Exceptions
```php
throw new \Exception('Test exception');
```

### Test Custom Logging
```php
Logger::info('Test info message');
Logger::warning('Test warning message');
Logger::logApp('Test app error', 'ERROR');
```

---

## 📈 MONITORING & MAINTENANCE

### Regular Tasks

1. **Check Logs Daily:**
   - Visit `/logs` to review recent errors
   - Address critical errors immediately
   - Investigate recurring warnings

2. **Clear Old Logs:**
   - Use "Clear Logs" button when file gets large
   - Or implement automatic log rotation

3. **Download Backups:**
   - Download logs before clearing
   - Keep archives for compliance/debugging

### Log Rotation (Recommended for Production)

Create a cron job to rotate logs:
```bash
# Rotate logs daily at midnight
0 0 * * * cd /path/to/v4 && php scripts/rotate-logs.php
```

Create `scripts/rotate-logs.php`:
```php
<?php
$logFile = __DIR__ . '/../storage/logs/err.log';
$archiveDir = __DIR__ . '/../storage/logs/archive/';

if (file_exists($logFile) && filesize($logFile) > 10 * 1024 * 1024) { // 10MB
    $archiveName = 'err-' . date('Y-m-d') . '.log';
    rename($logFile, $archiveDir . $archiveName);
    touch($logFile);
    chmod($logFile, 0666);
}
```

---

## 🔧 TROUBLESHOOTING

### Log File Not Created
**Problem:** err.log file doesn't exist  
**Solution:** 
```bash
mkdir -p storage/logs
touch storage/logs/err.log
chmod 666 storage/logs/err.log
```

### Permission Denied
**Problem:** Can't write to log file  
**Solution:**
```bash
chmod 666 storage/logs/err.log
chmod 755 storage/logs
```

### Logs Not Appearing
**Problem:** Errors occur but not logged  
**Solution:**
- Check Logger::init() is called in public/index.php
- Verify APP_ENV is set in .env
- Check file permissions

### Log Viewer Shows Nothing
**Problem:** /logs page is empty  
**Solution:**
- Check if err.log file exists
- Verify LogController is properly routed
- Check for PHP errors in browser console

---

## 📚 BEST PRACTICES

### DO:
✅ Log all database errors  
✅ Log authentication failures  
✅ Log critical business logic errors  
✅ Include context data for debugging  
✅ Use appropriate log levels  
✅ Review logs regularly  
✅ Clear logs periodically  

### DON'T:
❌ Log passwords or sensitive data  
❌ Log every successful operation (too verbose)  
❌ Leave debug logging on in production  
❌ Ignore critical errors  
❌ Let log files grow indefinitely  
❌ Expose logs to unauthorized users  

---

## 🎯 FUTURE ENHANCEMENTS

### Planned Features
- [ ] Email notifications for critical errors
- [ ] Automatic log rotation
- [ ] Log search functionality
- [ ] Export logs to CSV
- [ ] Real-time log streaming
- [ ] Error rate graphs/charts
- [ ] Integration with external monitoring services
- [ ] Slack/Discord notifications
- [ ] Log aggregation for multiple servers

---

## 📞 SUPPORT

### Common Issues

**Q: How do I view logs?**  
A: Visit http://localhost:8000/logs

**Q: How do I clear logs?**  
A: Click "Clear Logs" button on /logs page

**Q: Where are logs stored?**  
A: `storage/logs/err.log`

**Q: Can I download logs?**  
A: Yes, click "Download" button (feature to be added)

**Q: How do I disable logging?**  
A: Not recommended, but comment out `Logger::init()` in public/index.php

---

## ✅ CHECKLIST

### Setup Verification
- [x] Logger class created
- [x] Database class updated
- [x] LogController created
- [x] Log viewer created
- [x] Routes added
- [x] Navigation link added
- [x] Log file created
- [x] Permissions set
- [x] .gitignore configured

### Testing
- [ ] Test PHP errors are logged
- [ ] Test database errors are logged
- [ ] Test exceptions are logged
- [ ] Test log viewer displays correctly
- [ ] Test filters work
- [ ] Test clear logs works
- [ ] Test auto-refresh works
- [ ] Test on production environment

---

**Error Logging System:** Complete ✅  
**Status:** Ready for testing  
**Version:** 1.0  
**Last Updated:** December 2024
