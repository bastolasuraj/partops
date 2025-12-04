<div class="page-header">
    <div>
        <p class="eyebrow">Tools</p>
        <h1>QR Scanner</h1>
        <p class="lede">Scan or enter QR codes for quick part lookup and stock adjustments.</p>
    </div>
</div>

<div style="max-width: 600px;">
    <div class="form-group">
        <label for="qr_input">QR Code / Token</label>
        <input type="text" id="qr_input" class="form-control" placeholder="Paste or scan QR token here..." autofocus>
    </div>
    <button type="button" id="lookupBtn" class="btn btn-primary">Lookup Part</button>
</div>

<div id="result" style="margin-top: 2rem; display: none;">
    <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem;">
        <h3 id="partName" style="color: var(--accent-2); margin-bottom: 1rem;"></h3>
        <p><strong>Anchor:</strong> <span id="partAnchor"></span></p>
        <p><strong>Description:</strong> <span id="partDesc"></span></p>
        
        <h4 style="margin: 1rem 0 0.5rem;">Stock Levels</h4>
        <div id="stockLevels"></div>

        <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem;">
            <a id="viewPartLink" href="#" class="btn btn-ghost">View Full Details</a>
        </div>
    </div>
</div>

<div id="error" style="margin-top: 2rem; display: none;" class="flash flash-error"></div>

<script>
document.getElementById('lookupBtn').addEventListener('click', async function() {
    const token = document.getElementById('qr_input').value.trim();
    if (!token) return;

    const resultDiv = document.getElementById('result');
    const errorDiv = document.getElementById('error');
    resultDiv.style.display = 'none';
    errorDiv.style.display = 'none';

    try {
        const response = await fetch('/api/qr/lookup?token=' + encodeURIComponent(token));
        const data = await response.json();

        if (data.error) {
            errorDiv.textContent = data.error;
            errorDiv.style.display = 'block';
            return;
        }

        const part = data.part;
        document.getElementById('partName').textContent = part.name;
        document.getElementById('partAnchor').textContent = part.anchor_slug;
        document.getElementById('partDesc').textContent = part.description || 'N/A';
        document.getElementById('viewPartLink').href = '/parts/' + part.id;

        let stockHtml = '';
        if (part.inventory && part.inventory.length > 0) {
            stockHtml = '<table class="grid" style="margin-top: 0.5rem;"><thead><tr><th>Location</th><th>On Hand</th><th>Available</th></tr></thead><tbody>';
            part.inventory.forEach(inv => {
                stockHtml += '<tr><td>' + inv.location_formatted + '</td><td>' + inv.on_hand + '</td><td>' + (inv.on_hand - inv.reserved) + '</td></tr>';
            });
            stockHtml += '</tbody></table>';
        } else {
            stockHtml = '<p style="color: var(--muted);">No inventory found</p>';
        }
        document.getElementById('stockLevels').innerHTML = stockHtml;

        resultDiv.style.display = 'block';
    } catch (err) {
        errorDiv.textContent = 'Failed to lookup part';
        errorDiv.style.display = 'block';
    }
});

document.getElementById('qr_input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        document.getElementById('lookupBtn').click();
    }
});
</script>
