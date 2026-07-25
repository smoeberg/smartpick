<?php
// SmartPick WMS Mobile & Desktop Picking Dashboard Template
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartPick WMS - Pluk & Pak</title>
    <style>
        :root {
            --primary: #0066cc;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --dark: #212529;
            --light: #f8f9fa;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
            color: var(--dark);
        }
        .header {
            background: var(--primary);
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header h1 { margin: 0; font-size: 1.2rem; }
        .container {
            max-width: 800px;
            margin: 15px auto;
            padding: 0 10px;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .bin-badge {
            background: #e1f0ff;
            color: var(--primary);
            font-size: 1.4rem;
            font-weight: bold;
            padding: 10px 15px;
            border-radius: 8px;
            display: inline-block;
            margin-bottom: 10px;
        }
        .product-image {
            width: 100%;
            max-height: 200px;
            object-fit: contain;
            border-radius: 8px;
            background: #fafafa;
        }
        .product-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 10px 0 5px 0;
        }
        .product-meta {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 15px;
        }
        .qty-counter {
            font-size: 2.2rem;
            font-weight: bold;
            color: var(--primary);
            text-align: center;
            margin: 15px 0;
        }
        .scan-input-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .scan-input {
            flex: 1;
            padding: 14px;
            font-size: 1.2rem;
            border: 2px solid #ced4da;
            border-radius: 8px;
            outline: none;
        }
        .scan-input:focus { border-color: var(--primary); }
        .btn {
            padding: 14px 20px;
            font-size: 1rem;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-warning { background: var(--warning); color: var(--dark); }
        .btn-danger { background: var(--danger); color: white; }
        .btn:hover { opacity: 0.9; }
        .actions-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 15px;
        }
        .status-msg {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: none;
            font-weight: 500;
        }
        .status-success { background: #d4edda; color: #155724; display: block; }
        .status-error { background: #f8d7da; color: #721c24; display: block; }
    </style>
</head>
<body>

    <div class="header">
        <h1>SmartPick WMS</h1>
        <span id="route-count">Henter plukrute...</span>
    </div>

    <div class="container">

        <div id="status-box" class="status-msg"></div>

        <!-- Aktuel Vare Card -->
        <div class="card" id="active-item-card">
            <div class="bin-badge" id="item-location">Hylde / Placering: -</div>

            <div class="product-title" id="item-label">Indlæser produkt...</div>
            <div class="product-meta">Varenr: <strong id="item-ref">-</strong> | EAN: <strong id="item-barcode">-</strong></div>

            <div class="qty-counter">
                <span id="qty-picked">0</span> / <span id="qty-to-pick">0</span> stki
            </div>

            <form id="scan-form" onsubmit="handleScan(event)">
                <div class="scan-input-group">
                    <input type="text" id="barcode-input" class="scan-input" placeholder="Scan stregkode eller varenr..." autofocus autocomplete="off">
                    <button type="submit" class="btn btn-primary">Scan</button>
                </div>
            </form>

            <div class="actions-grid">
                <button type="button" class="btn btn-warning" onclick="handlePartialPick()">Delvist Pluk / Restordre</button>
                <button type="button" class="btn btn-success" onclick="nextItem()">Næste Vare →</button>
            </div>
        </div>

        <!-- Afslutningsknap -->
        <div class="card" style="text-align: center;">
            <button class="btn btn-success" style="width: 100%; font-size: 1.2rem;" onclick="completeBatch()">
                Gennemfør Pluk & Book Fragt i Shipmondo
            </button>
        </div>

    </div>

    <script>
        let currentRoute = [];
        let currentIndex = 0;

        // Web Audio Synth for lydeffekter (Beep/Fejl)
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

        function playAudioCue(isSuccess) {
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);

            if (isSuccess) {
                osc.frequency.setValueAtTime(800, audioCtx.currentTime); // Høj tone for succes
                gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.15);
            } else {
                osc.frequency.setValueAtTime(250, audioCtx.currentTime); // Lav tone for fejl
                gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.35);
            }
        }

        async function loadRoute() {
            try {
                const res = await fetch('../api/scan.php?action=get_route');
                const data = await res.json();
                if (data.success && data.items.length > 0) {
                    currentRoute = data.items;
                    currentIndex = 0;
                    renderCurrentItem();
                } else {
                    document.getElementById('route-count').innerText = 'Ingen ventende pluk';
                    document.getElementById('active-item-card').innerHTML = '<h3>Alle varer i plukkøen er færdigplukket!</h3>';
                }
            } catch (err) {
                showStatus('Fejl ved hentning af plukrute', false);
            }
        }

        function renderCurrentItem() {
            if (currentIndex >= currentRoute.length) {
                document.getElementById('active-item-card').innerHTML = '<h3>Plukrute gennemført! Klik nedenfor for at booke fragt.</h3>';
                return;
            }

            const item = currentRoute[currentIndex];
            document.getElementById('route-count').innerText = `Vare ${currentIndex + 1} af ${currentRoute.length}`;
            document.getElementById('item-location').innerText = `Placering: Hylde ${item.loc_rack || 'A'} - Bin ${item.loc_bin || '1'}`;
            document.getElementById('item-label').innerText = item.label;
            document.getElementById('item-ref').innerText = item.product_ref;
            document.getElementById('item-barcode').innerText = item.barcode || 'Ingen stregkode';
            document.getElementById('qty-picked').innerText = item.qty_picked;
            document.getElementById('qty-to-pick').innerText = item.qty_to_pick;

            const input = document.getElementById('barcode-input');
            input.value = '';
            input.focus();
        }

        async function handleScan(e) {
            e.preventDefault();
            const input = document.getElementById('barcode-input');
            const code = input.value.trim();
            if (!code) return;

            const item = currentRoute[currentIndex];

            try {
                const res = await fetch(`../api/scan.php?action=scan&queue_id=${item.rowid}&barcode=${encodeURIComponent(code)}`);
                const data = await res.json();

                if (data.success) {
                    playAudioCue(true);
                    showStatus(data.message, true);
                    item.qty_picked = data.qty_picked;
                    document.getElementById('qty-picked').innerText = data.qty_picked;

                    if (data.is_completed) {
                        setTimeout(() => {
                            currentIndex++;
                            renderCurrentItem();
                        }, 500);
                    }
                } else {
                    playAudioCue(false);
                    showStatus(data.message, false);
                }
            } catch (err) {
                playAudioCue(false);
                showStatus('Netværksfejl under scanning', false);
            }

            input.value = '';
            input.focus();
        }

        async function handlePartialPick() {
            const item = currentRoute[currentIndex];
            const qty = prompt(`Indtast hvor mange stki af "${item.label}" du kan plukke:`, item.qty_picked);
            if (qty === null) return;

            try {
                const res = await fetch(`../api/scan.php?action=partial_pick&queue_id=${item.rowid}&picked_qty=${parseInt(qty)}`);
                const data = await res.json();
                if (data.success) {
                    showStatus('Delvist pluk registreret', true);
                    currentIndex++;
                    renderCurrentItem();
                }
            } catch (err) {
                showStatus('Fejl ved registrering af delvist pluk', false);
            }
        }

        function nextItem() {
            currentIndex++;
            renderCurrentItem();
        }

        async function completeBatch() {
            if (!confirm('Ønsker du at afslutte pluk og oprette/booke forsendelsen i Shipmondo?')) return;

            try {
                const res = await fetch('../api/scan.php?action=complete_batch');
                const data = await res.json();
                if (data.success) {
                    alert('Pluk gennemført! ' + data.shipmondo_status);
                    loadRoute();
                }
            } catch (err) {
                alert('Fejl ved færdiggørelse af pluk');
            }
        }

        function showStatus(msg, isSuccess) {
            const box = document.getElementById('status-box');
            box.innerText = msg;
            box.className = isSuccess ? 'status-msg status-success' : 'status-msg status-error';
            setTimeout(() => { box.style.display = 'none'; }, 3000);
        }

        // Hent plukrute ved opstart
        window.onload = loadRoute;
    </script>
</body>
</html>
