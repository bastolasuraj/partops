<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'PartOps') ?> - PartOps</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: rgb(35, 31, 32);
            color: #f5f5f5;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: rgb(188, 59, 40);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        header h1 {
            color: white;
            font-size: 1.5rem;
        }
        
        nav ul {
            list-style: none;
            display: flex;
            gap: 1.5rem;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background 0.2s;
        }
        
        nav a:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgb(188, 59, 40);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn:hover {
            background: rgb(168, 49, 30);
        }
        
        .btn-secondary {
            background: #555;
        }
        
        .btn-secondary:hover {
            background: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        th {
            background: rgba(255, 255, 255, 0.05);
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            color: white;
            font-size: 1rem;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: rgb(188, 59, 40);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .alert-error {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.5);
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.5);
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <header>
        <div class="container">
            <h1>PartOps</h1>
            <nav>
                <ul>
                    <li><a href="<?= url('/') ?>">Dashboard</a></li>
                    <li><a href="<?= url('/parts') ?>">Parts</a></li>
                    <li><a href="<?= url('/suppliers') ?>">Suppliers</a></li>
                    <li><a href="<?= url('/locations') ?>">Locations</a></li>
                    <li><a href="<?= url('/inventory') ?>">Inventory</a></li>
                    <li><a href="<?= url('/work-orders') ?>">Work Orders</a></li>
                    <li>
                        <form action="<?= url('/logout') ?>" method="POST" style="display: inline;">
                            <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token'] ?? '' ?>">
                            <button type="submit" class="btn btn-secondary">Logout</button>
                        </form>
                    </li>
                </ul>
            </nav>
        </div>
    </header>
    <?php endif; ?>
    
    <main class="container">
        <?php echo $content ?? ''; ?>
    </main>
</body>
</html>
