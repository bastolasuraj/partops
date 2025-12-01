<?php ob_start(); ?>

<h1>Inventory Management</h1>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
    <div style="background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 8px;">
        <h3 style="margin-bottom: 1rem; color: rgb(188,59,40);">Receive Parts</h3>
        <p style="margin-bottom: 1rem;">Record incoming parts from suppliers with pricing and core charges.</p>
        <a href="#" class="btn">Receive</a>
    </div>
    
    <div style="background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 8px;">
        <h3 style="margin-bottom: 1rem; color: rgb(188,59,40);">Checkout</h3>
        <p style="margin-bottom: 1rem;">Issue parts to technicians for work orders.</p>
        <a href="#" class="btn">Checkout</a>
    </div>
    
    <div style="background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 8px;">
        <h3 style="margin-bottom: 1rem; color: rgb(188,59,40);">Returns</h3>
        <p style="margin-bottom: 1rem;">Process part returns and core returns with rebates.</p>
        <a href="#" class="btn">Return</a>
    </div>
    
    <div style="background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 8px;">
        <h3 style="margin-bottom: 1rem; color: rgb(188,59,40);">Adjustments</h3>
        <p style="margin-bottom: 1rem;">Adjust inventory levels for corrections or cycle counts.</p>
        <a href="#" class="btn">Adjust</a>
    </div>
</div>

<div style="margin-top: 3rem;">
    <h2>Recent Movements</h2>
    <p style="margin-top: 1rem; color: #999;">Movement history will appear here once inventory operations are implemented.</p>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layout.php'; ?>
