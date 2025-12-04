<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PartOps</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .animated-gradient {
            background: linear-gradient(-45deg, #0f172a, #1e3a8a, #0369a1, #0f766e);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
    </style>
</head>
<body class="animated-gradient min-h-screen flex items-center justify-center p-4">
    <div class="bg-white/95 backdrop-blur-lg rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-white/20">
        <div class="p-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 text-blue-600 mb-4 shadow-inner">
                    <i class="fas fa-cogs text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Welcome Back</h1>
                <p class="text-gray-500 mt-2">Sign in to PartOps Inventory</p>
            </div>

            <form action="<?= url('/login') ?>" method="POST" class="space-y-6">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <div>
                    <label for="username" class="block mb-2 text-sm font-medium text-gray-900">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" id="username" name="username" required autofocus 
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5 transition-all"
                            placeholder="Enter your username">
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block mb-2 text-sm font-medium text-gray-900">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5 transition-all"
                            placeholder="••••••••">
                    </div>
                </div>
                
                <button type="submit" class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5">
                    Sign In
                </button>
            </form>
        </div>
        <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-500">&copy; <?= date('Y') ?> PartOps System</p>
        </div>
    </div>
</body>
</html>