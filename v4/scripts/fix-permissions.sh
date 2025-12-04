#!/bin/bash
# Fix file permissions for PartOps v4

echo "Fixing file permissions for PartOps v4..."

cd "$(dirname "$0")/.."

# Make storage directories writable
echo "Setting storage directory permissions..."
chmod -R 775 storage/
chmod -R 777 storage/logs/
chmod -R 777 storage/cache/
chmod -R 777 storage/uploads/

# Make log file writable
echo "Setting log file permissions..."
if [ -f storage/logs/err.log ]; then
    chmod 666 storage/logs/err.log
else
    touch storage/logs/err.log
    chmod 666 storage/logs/err.log
fi

# Set ownership to web server user (if running as root)
if [ "$EUID" -eq 0 ]; then
    echo "Setting ownership to www-data..."
    chown -R www-data:www-data storage/
fi

echo "✓ Permissions fixed!"
echo ""
echo "Directory permissions:"
ls -la storage/
echo ""
echo "Log file permissions:"
ls -la storage/logs/err.log

echo ""
echo "Done! Try accessing the site again."
