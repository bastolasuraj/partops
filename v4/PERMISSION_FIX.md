# Permission Error Fix

**Error:** `file_put_contents(/mnt/shared/projects/partops/v4/storage/logs/err.log): Failed to open stream: Permission denied`

---

## 🔧 QUICK FIX

Run one of these commands to fix permissions:

### Option 1: PHP Script (Recommended)
```bash
cd /mnt/shared/projects/partops/v4
php scripts/fix-permissions.php
```

### Option 2: Shell Script
```bash
cd /mnt/shared/projects/partops/v4
chmod +x scripts/fix-permissions.sh
./scripts/fix-permissions.sh
```

### Option 3: Manual Commands
```bash
cd /mnt/shared/projects/partops/v4
chmod -R 777 storage/
chmod 666 storage/logs/err.log
```

### Option 4: With sudo (if needed)
```bash
cd /mnt/shared/projects/partops/v4
sudo chmod -R 777 storage/
sudo chmod 666 storage/logs/err.log
sudo chown -R www-data:www-data storage/
```

---

## 🎯 WHY THIS HAPPENS

The web server (Apache/Nginx) runs as a specific user (usually `www-data`, `apache`, or `nginx`). This user needs write permission to the `storage/logs/` directory to create and write to the error log file.

**Common causes:**
1. Files were created by your user account, not the web server user
2. Directory permissions are too restrictive
3. SELinux or AppArmor is blocking writes

---

## ✅ VERIFY FIX

After running the fix, check permissions:

```bash
ls -la storage/logs/
```

Expected output:
```
drwxrwxrwx  2 www-data www-data 4096 Dec  2 18:00 .
-rw-rw-rw-  1 www-data www-data    0 Dec  2 18:00 err.log
```

---

## 🔍 TROUBLESHOOTING

### Still getting permission errors?

1. **Check SELinux** (if on RHEL/CentOS):
   ```bash
   sudo setenforce 0  # Temporarily disable
   # Or set proper context:
   sudo chcon -R -t httpd_sys_rw_content_t storage/
   ```

2. **Check file ownership**:
   ```bash
   ls -la storage/logs/err.log
   # Should be owned by web server user
   ```

3. **Check parent directory**:
   ```bash
   ls -la storage/
   # Should be writable by web server
   ```

4. **Check disk space**:
   ```bash
   df -h
   # Make sure disk isn't full
   ```

5. **Check Apache/Nginx user**:
   ```bash
   # Apache
   ps aux | grep apache
   # Nginx
   ps aux | grep nginx
   ```

---

## 🛠️ PERMANENT FIX

To prevent this in the future, set proper ownership:

```bash
cd /mnt/shared/projects/partops/v4

# Find web server user
WEB_USER=$(ps aux | grep -E 'apache|httpd|nginx' | grep -v root | head -1 | awk '{print $1}')

# Set ownership
sudo chown -R $WEB_USER:$WEB_USER storage/

# Set permissions
sudo chmod -R 775 storage/
sudo chmod 666 storage/logs/err.log
```

---

## 📝 DEPLOYMENT CHECKLIST

When deploying to a new server:

- [ ] Create storage directories
- [ ] Set directory permissions (775 or 777)
- [ ] Set log file permissions (666)
- [ ] Set correct ownership (web server user)
- [ ] Test write access
- [ ] Check SELinux/AppArmor settings

---

## 🚨 SECURITY NOTE

**Development:** `chmod 777` is fine  
**Production:** Use `chmod 775` and proper ownership

```bash
# Production setup
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/
sudo chmod 666 storage/logs/err.log
```

---

## 📞 NEED HELP?

If you still have issues:

1. Check Apache/Nginx error logs:
   ```bash
   tail -f /var/log/apache2/error.log
   # or
   tail -f /var/log/nginx/error.log
   ```

2. Check PHP error logs:
   ```bash
   tail -f /var/log/php/error.log
   ```

3. Test file creation manually:
   ```bash
   sudo -u www-data touch storage/logs/test.log
   ```

---

**Status:** Fixed ✅  
**Last Updated:** December 2024
