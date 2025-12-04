<div class="text-center py-12">
    <h1 class="text-5xl font-bold text-dark mb-4">🔧 Welcome to PartOps v4</h1>
    <p class="text-xl text-gray-600 mb-8">Parts Inventory Management System</p>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-12">
        <a href="<?= url('/parts') ?>" class="bg-gradient-to-br from-primary to-secondary p-6 rounded-lg shadow-lg hover:shadow-xl transition transform hover:-translate-y-1">
            <div class="text-4xl mb-2">📦</div>
            <h3 class="text-xl font-bold text-dark">Parts</h3>
            <p class="text-gray-700">Manage inventory</p>
        </a>
        
        <a href="<?= url('/checkins') ?>" class="bg-gradient-to-br from-success to-green-300 p-6 rounded-lg shadow-lg hover:shadow-xl transition transform hover:-translate-y-1">
            <div class="text-4xl mb-2">📥</div>
            <h3 class="text-xl font-bold text-dark">Check-In</h3>
            <p class="text-gray-700">Receive parts</p>
        </a>
        
        <a href="<?= url('/checkouts') ?>" class="bg-gradient-to-br from-warning to-yellow-300 p-6 rounded-lg shadow-lg hover:shadow-xl transition transform hover:-translate-y-1">
            <div class="text-4xl mb-2">📤</div>
            <h3 class="text-xl font-bold text-dark">Checkout</h3>
            <p class="text-gray-700">Issue to work orders</p>
        </a>
        
        <a href="<?= url('/returns') ?>" class="bg-gradient-to-br from-info to-blue-200 p-6 rounded-lg shadow-lg hover:shadow-xl transition transform hover:-translate-y-1">
            <div class="text-4xl mb-2">↩️</div>
            <h3 class="text-xl font-bold text-dark">Returns</h3>
            <p class="text-gray-700">Process returns</p>
        </a>
    </div>
    
    <div class="mt-12">
        <a href="<?= url('/dashboard') ?>" class="inline-block bg-dark text-white px-8 py-3 rounded-lg font-semibold hover:bg-gray-800 transition">
            Go to Dashboard →
        </a>
    </div>
</div>
