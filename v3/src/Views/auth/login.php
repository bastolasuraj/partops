<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PartOps</title>
    <style>
        :root {
            --bg: #131013;
            --panel: #231f20;
            --accent: rgb(188, 59, 40);
            --accent-2: rgb(255, 222, 63);
            --text: #f3f0f0;
            --muted: #c3bfbf;
            --border: rgba(255, 255, 255, 0.14);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: radial-gradient(circle at 18% 20%, rgba(255, 222, 63, 0.07), transparent 32%), 
                        radial-gradient(circle at 82% 15%, rgba(188, 59, 40, 0.14), transparent 32%), 
                        linear-gradient(135deg, #161215 0%, #1f1a1c 55%, #171316 100%);
            color: var(--text);
            font-family: "Inter", "Segoe UI", system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        .brand {
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent-2);
            margin-bottom: 0.5rem;
        }
        .subtitle {
            text-align: center;
            color: var(--muted);
            margin-bottom: 2rem;
        }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--muted);
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #0f0c0d;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 1rem;
            font-family: inherit;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(188, 59, 40, 0.2);
        }
        .btn {
            width: 100%;
            padding: 0.85rem;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn:hover { background: rgb(200, 70, 50); }
        .flash {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .flash-error { background: rgba(244, 67, 54, 0.2); border: 1px solid rgba(244, 67, 54, 0.4); color: #f44336; }
        .flash-success { background: rgba(76, 175, 80, 0.2); border: 1px solid rgba(76, 175, 80, 0.4); color: #4caf50; }
        .hint {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--muted);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">PartOps</div>
        <p class="subtitle">Parts Inventory Management</p>

        <?php 
        use PartOps\Services\Session;
        $error = Session::getFlash('error');
        $success = Session::getFlash('success');
        if ($error): ?>
            <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="flash flash-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login">
            <?= \PartOps\Services\CSRF::getField() ?>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn">Sign In</button>
        </form>

        <p class="hint">Default: admin / password</p>
    </div>
</body>
</html>
