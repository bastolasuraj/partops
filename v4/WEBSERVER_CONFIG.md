# Web Server Configuration for PartOps v4

## Problem
The URL shows `/public` in the path: `https://php.sbastola.com/partops/v4/public/`
We want: `https://php.sbastola.com/partops/v4/`

## Root Cause
The web server configuration likely has an Alias or DocumentRoot pointing directly to the `public` directory, bypassing the `.htaccess` rewrite rules in the parent directory.

## Solution

### Option 1: Apache Configuration (Recommended)
Update your Apache virtual host or site configuration file:

```apache
# Instead of:
Alias /partops/v4 /mnt/shared/projects/partops/v4/public

# Use:
Alias /partops/v4 /mnt/shared/projects/partops/v4

<Directory /mnt/shared/projects/partops/v4>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

<Directory /mnt/shared/projects/partops/v4/public>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

### Option 2: Nginx Configuration
If using Nginx, update your server block:

```nginx
location /partops/v4 {
    alias /mnt/shared/projects/partops/v4/public;
    try_files $uri $uri/ /partops/v4/index.php?$query_string;
    
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $request_filename;
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
    }
}
```

### Option 3: .htaccess Only (If you can't modify server config)
The `.htaccess` file in `/mnt/shared/projects/partops/v4/` has been updated to redirect any `/public` URLs to the clean version.

However, this only works if:
1. The web server Alias points to `/mnt/shared/projects/partops/v4` (not `/v4/public`)
2. `.htaccess` files are enabled (`AllowOverride All`)
3. `mod_rewrite` is enabled

## How to Apply Changes

### For Apache:
1. Edit your site configuration (usually in `/etc/apache2/sites-available/`)
2. Update the Alias directive as shown above
3. Restart Apache: `sudo systemctl restart apache2`

### For Nginx:
1. Edit your site configuration (usually in `/etc/nginx/sites-available/`)
2. Update the location block as shown above
3. Test config: `sudo nginx -t`
4. Reload Nginx: `sudo systemctl reload nginx`

## Verification
After applying changes:
- Visit: `https://php.sbastola.com/partops/v4/`
- Should see the application homepage
- URL should NOT contain `/public`
- If you manually visit `https://php.sbastola.com/partops/v4/public/`, it should redirect to `/partops/v4/`

## Current File Structure
```
/mnt/shared/projects/partops/v4/
├── .htaccess              # Handles rewrites to public/
├── public/
│   ├── .htaccess          # Handles routing to index.php
│   └── index.php          # Application entry point
├── app/
├── routes/
└── ...
```

## Security Note
The `public` directory is the only directory that should be web-accessible. All other directories (app, routes, storage, etc.) should be protected from direct web access. This is why the web server should point to the v4 directory and let `.htaccess` handle the internal rewrite to `public/`.
