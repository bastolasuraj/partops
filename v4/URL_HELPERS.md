# URL Helper Functions

**Purpose:** Handle subdirectory deployments correctly by automatically prepending the base path.

---

## 🎯 THE PROBLEM

When deploying to a subdirectory like `/partops/v4/`, hardcoded URLs like `/dashboard` will point to the wrong location:
- ❌ `href="/dashboard"` → `https://php.sbastola.com/dashboard` (wrong!)
- ✅ `href="<?= url('/dashboard') ?>"` → `https://php.sbastola.com/partops/v4/dashboard` (correct!)

---

## 📚 AVAILABLE FUNCTIONS

### `url(string $path = ''): string`

Generate a URL with the correct base path.

**Usage:**
```php
<!-- Navigation links -->
<a href="<?= url('/') ?>">Home</a>
<a href="<?= url('/dashboard') ?>">Dashboard</a>
<a href="<?= url('/parts') ?>">Parts</a>

<!-- Form actions -->
<form method="POST" action="<?= url('/parts') ?>">
    ...
</form>

<!-- Redirects in controllers -->
header('Location: ' . url('/parts'));
```

**Examples:**
```php
// Root deployment (/)
url('/dashboard')  // → /dashboard

// Subdirectory deployment (/partops/v4/)
url('/dashboard')  // → /partops/v4/dashboard
```

### `asset(string $path): string`

Generate an asset URL (alias for `url()`).

**Usage:**
```php
<link rel="stylesheet" href="<?= asset('/css/app.css') ?>">
<script src="<?= asset('/js/app.js') ?>"></script>
<img src="<?= asset('/images/logo.png') ?>" alt="Logo">
```

### `redirect(string $path): void`

Redirect to a URL with the correct base path.

**Usage:**
```php
// In controllers
redirect('/dashboard');
redirect('/parts');
redirect('/');
```

### `old(string $key, $default = '')`

Get old input value (for form validation).

**Usage:**
```php
<input type="text" name="name" value="<?= old('name') ?>">
<input type="email" name="email" value="<?= old('email', 'default@example.com') ?>">
```

---

## 🔧 HOW IT WORKS

The `url()` function automatically detects the base path from `$_SERVER['SCRIPT_NAME']`:

```php
function url(string $path = ''): string
{
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    $basePath = ($scriptName !== '/') ? $scriptName : '';
    $path = '/' . ltrim($path, '/');
    return $basePath . $path;
}
```

**Detection:**
- If `SCRIPT_NAME` = `/index.php` → base path = `` (root)
- If `SCRIPT_NAME` = `/partops/v4/public/index.php` → base path = `/partops/v4/public`

---

## ✅ USAGE GUIDELINES

### DO:
✅ Always use `url()` for internal links  
✅ Use `url()` in form actions  
✅ Use `redirect()` in controllers  
✅ Use `asset()` for static files  

### DON'T:
❌ Don't use hardcoded paths: `href="/dashboard"`  
❌ Don't use relative paths: `href="dashboard"`  
❌ Don't manually construct URLs  

---

## 📝 UPDATING VIEWS

### Before (Hardcoded):
```php
<a href="/parts">Parts</a>
<form action="/parts" method="POST">
```

### After (Using Helper):
```php
<a href="<?= url('/parts') ?>">Parts</a>
<form action="<?= url('/parts') ?>" method="POST">
```

---

## 🧪 TESTING

Test that URLs work correctly:

```bash
# Root deployment
php -S localhost:8000 -t public
# Visit: http://localhost:8000/
# Links should work: /dashboard, /parts, etc.

# Subdirectory deployment
# Visit: https://php.sbastola.com/partops/v4/
# Links should work: /partops/v4/dashboard, /partops/v4/parts, etc.
```

---

## 🔍 DEBUGGING

If links are broken, check:

1. **Helper loaded?**
   ```php
   // In public/index.php
   require_once __DIR__ . '/../app/Core/helpers.php';
   ```

2. **Using url() in views?**
   ```php
   // Check all views use url()
   grep -r 'href="/' app/Views/
   ```

3. **SCRIPT_NAME correct?**
   ```php
   // Add to any view temporarily
   echo '<pre>';
   echo 'SCRIPT_NAME: ' . $_SERVER['SCRIPT_NAME'] . "\n";
   echo 'Base path: ' . dirname($_SERVER['SCRIPT_NAME']) . "\n";
   echo 'url(/test): ' . url('/test') . "\n";
   echo '</pre>';
   ```

---

## 📦 FILES

- **Helper Functions:** `app/Core/helpers.php`
- **Loaded In:** `public/index.php`
- **Used In:** All view files

---

## 🚀 DEPLOYMENT CHECKLIST

When deploying to a new environment:

- [ ] Verify `url()` helper is loaded
- [ ] Test homepage links
- [ ] Test navigation menu
- [ ] Test form submissions
- [ ] Test redirects in controllers
- [ ] Check error pages (404)

---

**Status:** Implemented ✅  
**Version:** 1.0  
**Last Updated:** December 2024
