<?php
// Get base URL
$baseUrl = $_ENV['APP_URL'] ?? '';
if (empty($baseUrl)) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $baseUrl = $protocol . '://' . $host . dirname(dirname($scriptName));
}
$baseUrl = rtrim($baseUrl, '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PartOps</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        .login-panel {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            color: var(--accent-2);
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: var(--muted);
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--muted);
            font-weight: 500;
        }
        .btn-login {
            width: 100%;
            padding: 0.85rem;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <?php if ($flash ?? null): ?>
        <div class="alert alert-<?= $flash['type'] ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
        <?php endif; ?>

        <div class="login-panel">
            <div class="login-header">
                <h1>PartOps</h1>
                <p>Parts Inventory Management</p>
            </div>

            <form method="POST" action="<?= $baseUrl ?>/login">
                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus
                           placeholder="Enter your username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Enter your password">
                </div>

                <button type="submit" class="btn-primary btn-login">Sign In</button>
            </form>

            <div class="form-hint" style="margin-top: 1.5rem; text-align: center;">
                <?php if ($_ENV['LDAP_ENABLED'] ?? false): ?>
                    Use your network credentials to sign in.
                <?php else: ?>
                    Default: admin / password
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
