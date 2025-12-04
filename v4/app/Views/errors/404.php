<?php
// app/Views/errors/404.php
// 404 Not Found page
?>
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="text-center">
        <h1 class="text-9xl font-bold text-primary mb-4">404</h1>
        <h2 class="text-3xl font-bold text-dark mb-4">Page Not Found</h2>
        <p class="text-gray-600 mb-8">The page you're looking for doesn't exist or has been moved.</p>
        <div class="flex gap-4 justify-center">
            <a href="<?= url('/') ?>" class="bg-primary text-dark px-6 py-3 rounded-lg hover:bg-secondary transition">
                Go Home
            </a>
            <a href="<?= url('/dashboard') ?>" class="bg-gray-200 text-dark px-6 py-3 rounded-lg hover:bg-gray-300 transition">
                Dashboard
            </a>
        </div>
    </div>
</div>
