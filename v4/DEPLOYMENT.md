# PartOps v4 - Deployment Guide

**Live URL:** https://php.sbastola.com/partops/v4/  
**Status:** Deployed ✅  
**Date:** December 2024

---

## 🚀 DEPLOYMENT STEPS COMPLETED

### 1. File Structure
- ✅ Renamed prototype HTML files to `_prototype-*.html`
- ✅ Created root `.htaccess` to redirect to `public/` directory
- ✅ Verified `public/.htaccess` for proper routing
- ✅ Ensured `public/index.php` is the entry point

### 2. Environment Configuration
- ✅ `.env` file configured with production database
- ✅ Database: `partsdb` (shared with v2)
- ✅ Database user: `partsuser` (set credentials in `.env`)
- ✅ APP_URL set to: `https://php.sbastola.com/partops/v4`

### 3. Dependencies
- ✅ Composer dependencies installed (`vendor/` directory)
- ✅ PSR-4 autoloading configured
- ✅ phpdotenv loaded

### 4. Directory Permissions
```bash
# Ensure proper permissions
chmod 755 storage/
chmod 755 storage/logs/
chmod 755 storage/cache/
chmod 755 storage/uploads/
chmod 666 storage/logs/err.log
```

### 5. Database Setup
The application uses the existing `partsdb` database with tables:
- suppliers
- technicians
- parts
- work_orders
- part_checkins
- part_checkouts
- work_order_returns
- returns

**Note:** If tables don't exist, run:
```bash
cd /mnt/shared/projects/partops/v4
php scripts/migrate.php
```

---

## 🔍 VERIFICATION

### Check Application is Running
Visit: https://php.sbastola.com/partops/v4/

Expected: Landing page with feature cards

### Test Routes
- `/` - Home page ✅
- `/dashboard` - Dashboard with stats
- `/parts` - Parts list
- `/suppliers` - Suppliers list
- `/technicians` - Technicians list
- `/work-orders` - Work orders list
- `/checkins` - Check-ins list
- `/checkouts` - Checkouts list
- `/returns` - Returns dashboard
- `/logs` - Error logs (admin)

### Test Database Connection
```bash
cd /mnt/shared/projects/partops/v4
php -r "
require 'vendor/autoload.php';
\$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
\$dotenv->load();
\$pdo = new PDO(
    'mysql:host='.\$_ENV['DB_HOST'].';dbname='.\$_ENV['DB_NAME'],
    \$_ENV['DB_USER'],
    \$_ENV['DB_PASS']
);
echo 'Database connection: OK' . PHP_EOL;
"
```

---

## 📁 FILE STRUCTURE

```
v4/
├── .htaccess                    ✅ Redirects to public/
├── public/
│   ├── .htaccess               ✅ Routes to index.php
│   └── index.php               ✅ Application entry point
├── app/
│   ├── Controllers/            ✅ 9 controllers
│   ├── Models/                 ✅ 4 models
│   ├── Views/                  ✅ 17 views
│   └── Core/                   ✅ Framework classes
├── routes/
│   └── web.php                 ✅ All routes defined
├── storage/
│   ├── logs/err.log            ✅ Error logging
│   ├── cache/                  ✅ Cache directory
│   └── uploads/                ✅ Upload directory
├── vendor/                     ✅ Composer dependencies
├── .env                        ✅ Environment config
└── _prototype-*.html           ✅ Preserved prototypes
```

---

## 🔧 APACHE/NGINX CONFIGURATION

### Apache (Current Setup)
The root `.htaccess` handles redirection to `public/`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^$ public/ [L]
    RewriteRule (.*) public/$1 [L]
</IfModule>
```

### Alternative: VirtualHost Configuration
For better performance, configure Apache VirtualHost to point directly to `public/`:

```apache
<VirtualHost *:80>
    ServerName php.sbastola.com
    DocumentRoot /mnt/shared/projects/partops/v4/public
    
    <Directory /mnt/shared/projects/partops/v4/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx Configuration
If using Nginx:

```nginx
server {
    listen 80;
    server_name php.sbastola.com;
    root /mnt/shared/projects/partops/v4/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## 🔒 SECURITY CHECKLIST

### Before Production
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Add authentication to log viewer (`/logs`)
- [ ] Implement CSRF token protection
- [ ] Enable HTTPS (SSL certificate)
- [ ] Set `SESSION_SECURE=true` in `.env`
- [ ] Review and restrict file permissions
- [ ] Set up automated backups
- [ ] Configure log rotation
- [ ] Add rate limiting
- [ ] Review security headers in `.htaccess`

### Current Security Features
- ✅ SQL injection protection (PDO prepared statements)
- ✅ XSS protection (htmlspecialchars output escaping)
- ✅ Environment variables for sensitive data
- ✅ .env file excluded from git
- ✅ Directory browsing disabled
- ✅ Sensitive files protected (.env, .git)
- ✅ Error logging system
- ⚠️ CSRF protection (to be implemented)
- ⚠️ Authentication (to be implemented)

---

## 🐛 TROUBLESHOOTING

### Issue: 404 Not Found
**Cause:** .htaccess not working or mod_rewrite disabled  
**Solution:**
```bash
# Enable mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Issue: 500 Internal Server Error
**Cause:** PHP error or missing dependencies  
**Solution:**
```bash
# Check error log
tail -f storage/logs/err.log

# Check Apache error log
tail -f /var/log/apache2/error.log

# Verify dependencies
composer install
```

### Issue: Database Connection Failed
**Cause:** Wrong credentials or database doesn't exist  
**Solution:**
```bash
# Verify .env settings
cat .env | grep DB_

# Test connection
mysql -u partsuser -p partsdb
```

### Issue: Permission Denied
**Cause:** Incorrect file permissions  
**Solution:**
```bash
# Fix permissions
chmod -R 755 storage/
chmod 666 storage/logs/err.log
chown -R www-data:www-data storage/
```

### Issue: Blank Page
**Cause:** PHP error with display_errors=off  
**Solution:**
```bash
# Check error log
cat storage/logs/err.log

# Temporarily enable errors
# Edit public/index.php and set APP_DEBUG=true
```

---

## 📊 MONITORING

### Health Checks
- **Application:** https://php.sbastola.com/partops/v4/
- **Error Logs:** https://php.sbastola.com/partops/v4/logs
- **Database:** Check via phpMyAdmin or CLI

### Log Files
- **Application Errors:** `storage/logs/err.log`
- **Apache Errors:** `/var/log/apache2/error.log`
- **Apache Access:** `/var/log/apache2/access.log`

### Performance Monitoring
```bash
# Check PHP processes
ps aux | grep php

# Check Apache status
systemctl status apache2

# Check disk space
df -h

# Check log file size
du -h storage/logs/err.log
```

---

## 🔄 UPDATES & MAINTENANCE

### Deploying Updates
```bash
cd /mnt/shared/projects/partops/v4

# Pull latest changes
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations if needed
php scripts/migrate.php

# Clear cache
rm -rf storage/cache/*

# Restart PHP-FPM (if using)
sudo systemctl restart php8.1-fpm
```

### Database Backups
```bash
# Backup database
mysqldump -u partsuser -p partsdb > backup-$(date +%Y%m%d).sql

# Restore database
mysql -u partsuser -p partsdb < backup-20241202.sql
```

### Log Rotation
```bash
# Rotate logs manually
cd /mnt/shared/projects/partops/v4
mv storage/logs/err.log storage/logs/err-$(date +%Y%m%d).log
touch storage/logs/err.log
chmod 666 storage/logs/err.log
```

---

## ✅ DEPLOYMENT CHECKLIST

### Initial Deployment
- [x] Rename prototype HTML files
- [x] Create root .htaccess
- [x] Configure .env file
- [x] Install Composer dependencies
- [x] Set directory permissions
- [x] Verify database connection
- [x] Test application routes
- [x] Enable error logging

### Pre-Production
- [ ] Set APP_ENV=production
- [ ] Set APP_DEBUG=false
- [ ] Add authentication
- [ ] Implement CSRF protection
- [ ] Enable HTTPS
- [ ] Configure backups
- [ ] Set up monitoring
- [ ] Load test application
- [ ] Security audit

### Post-Deployment
- [ ] Verify all routes work
- [ ] Test CRUD operations
- [ ] Check error logging
- [ ] Monitor performance
- [ ] Review logs daily
- [ ] Set up alerts

---

## 📞 SUPPORT

### Quick Commands
```bash
# View recent errors
tail -20 storage/logs/err.log

# Clear logs
> storage/logs/err.log

# Restart Apache
sudo systemctl restart apache2

# Check PHP version
php -v

# Test database connection
php -r "new PDO('mysql:host=localhost;dbname=partsdb', 'partsuser', getenv('DB_PASS'));"
```

### Common Issues
1. **White screen:** Check error log
2. **404 errors:** Check .htaccess and mod_rewrite
3. **Database errors:** Check credentials and tables
4. **Permission errors:** Check file ownership and permissions

---

**Deployment Status:** Live ✅  
**Last Updated:** December 2024  
**Version:** 1.0  
**Environment:** Production (set `APP_ENV=production`, `APP_DEBUG=false`)
