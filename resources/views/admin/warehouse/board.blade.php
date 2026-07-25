<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bodega — Pedidos en vivo — {{ config('app.name', 'Rómulo') }}</title>
    <x-favicon />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"
          integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <style>
        :root {
            --bg: #0b1220;
            --panel: #121a2b;
            --card: #18233a;
            --card-new: #1d2a18;
            --border: #243049;
            --text: #f1f5f9;
            --muted: #94a3b8;
            --accent: #f59e0b;
            --accent-soft: rgba(245, 158, 11, 0.18);
            --ok: #22c55e;
            --ok-soft: rgba(34, 197, 94, 0.16);
            --danger: #ef4444;
            --prep: #38bdf8;
            --prep-soft: rgba(56, 189, 248, 0.16);
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100%;
            background: radial-gradient(1200px 600px at 10% -10%, #1e293b 0%, transparent 55%),
                        radial-gradient(900px 500px at 100% 0%, #172554 0%, transparent 45%),
                        var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        }

        .board-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 1rem 1.25rem 1.5rem;
        }

        .board-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .board-title {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .board-title h1 {
            margin: 0;
            font-size: clamp(1.35rem, 2.4vw, 2rem);
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .board-title p {
            margin: 0.15rem 0 0;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .pulse-dot {
            width: 0.85rem;
            height: 0.85rem;
            border-radius: 50%;
            background: var(--ok);
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.55);
            animation: pulse 1.6s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.55); }
            70% { box-shadow: 0 0 0 12px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

        .board-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.65rem;
        }

        .stat-pill,
        .btn-board {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--panel);
            color: var(--text);
            padding: 0.45rem 0.9rem;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .stat-pill strong { font-variant-numeric: tabular-nums; }

        .btn-board {
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s ease, border-color 0.15s ease;
        }

        .btn-board:hover { background: #1e293b; border-color: #334155; }

        .btn-board.is-on {
            background: var(--ok-soft);
            border-color: rgba(34, 197, 94, 0.45);
            color: #86efac;
        }

        .btn-board.is-warn {
            background: var(--accent-soft);
            border-color: rgba(245, 158, 11, 0.5);
            color: #fcd34d;
        }

        .board-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1rem;
            align-content: start;
            flex: 1;
        }

        .order-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 1rem;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 280px;
            animation: fadeIn 0.35s ease;
        }

        .order-card.is-new {
            background: linear-gradient(180deg, var(--card-new), var(--card));
            border-color: rgba(34, 197, 94, 0.55);
            box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.2), 0 12px 30px rgba(0, 0, 0, 0.25);
            animation: popIn 0.45s ease;
        }

        .order-card.is-processing {
            border-color: rgba(56, 189, 248, 0.4);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: none; }
        }

        @keyframes popIn {
            0% { opacity: 0; transform: scale(0.96); }
            60% { transform: scale(1.02); }
            100% { opacity: 1; transform: none; }
        }

        .order-head {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 1rem 1.1rem 0.75rem;
            border-bottom: 1px solid var(--border);
        }

        .order-code {
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            font-variant-numeric: tabular-nums;
        }

        .order-time {
            color: var(--muted);
            font-size: 0.95rem;
            margin-top: 0.2rem;
        }

        .badge-status {
            align-self: flex-start;
            border-radius: 999px;
            padding: 0.35rem 0.7rem;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .badge-status.paid {
            background: var(--accent-soft);
            color: #fbbf24;
        }

        .badge-status.processing {
            background: var(--prep-soft);
            color: #7dd3fc;
        }

        .order-body {
            padding: 0.9rem 1.1rem;
            flex: 1;
        }

        .customer-line {
            font-weight: 700;
            font-size: 1.05rem;
            margin-bottom: 0.25rem;
        }

        .ship-line {
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.35;
            margin-bottom: 0.85rem;
        }

        .items-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 0.45rem;
        }

        .items-list li {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 0.65rem;
            align-items: start;
            background: rgba(15, 23, 42, 0.45);
            border-radius: 0.65rem;
            padding: 0.55rem 0.7rem;
        }

        .qty {
            min-width: 2.1rem;
            height: 2.1rem;
            display: grid;
            place-items: center;
            border-radius: 0.5rem;
            background: var(--accent);
            color: #111827;
            font-weight: 800;
            font-size: 1rem;
            font-variant-numeric: tabular-nums;
        }

        .item-name { font-weight: 600; line-height: 1.25; }
        .item-sku { color: var(--muted); font-size: 0.8rem; margin-top: 0.15rem; }

        .order-foot {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding: 0.85rem 1.1rem 1.1rem;
            border-top: 1px solid var(--border);
        }

        .action-btn {
            flex: 1;
            min-width: 120px;
            border: 0;
            border-radius: 0.75rem;
            padding: 0.75rem 0.9rem;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            color: #0f172a;
        }

        .action-btn:disabled {
            opacity: 0.55;
            cursor: wait;
        }

        .action-btn.prep { background: #38bdf8; }
        .action-btn.ship { background: #22c55e; }

        .empty-state {
            grid-column: 1 / -1;
            display: grid;
            place-items: center;
            min-height: 50vh;
            text-align: center;
            color: var(--muted);
            border: 1px dashed var(--border);
            border-radius: 1rem;
            background: rgba(15, 23, 42, 0.35);
            padding: 2rem;
        }

        .empty-state i { font-size: 2.5rem; color: #64748b; margin-bottom: 0.75rem; }
        .empty-state h2 { margin: 0 0 0.35rem; color: var(--text); font-size: 1.4rem; }
        .empty-state p { margin: 0; }

        .toast-error {
            position: fixed;
            bottom: 1.25rem;
            right: 1.25rem;
            background: #7f1d1d;
            color: #fecaca;
            border: 1px solid #991b1b;
            border-radius: 0.75rem;
            padding: 0.85rem 1rem;
            max-width: min(420px, calc(100vw - 2rem));
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
            z-index: 50;
            display: none;
        }

        .toast-error.is-visible { display: block; }

        .sound-banner {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.72);
            display: none;
            place-items: center;
            z-index: 40;
            padding: 1.5rem;
        }

        .sound-banner.is-visible { display: grid; }

        .sound-card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 1.1rem;
            padding: 1.5rem;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
        }

        .sound-card h2 { margin: 0 0 0.5rem; font-size: 1.35rem; }
        .sound-card p { margin: 0 0 1.1rem; color: var(--muted); }
        .sound-card button {
            border: 0;
            border-radius: 0.8rem;
            background: var(--accent);
            color: #111827;
            font-weight: 800;
            padding: 0.85rem 1.2rem;
            cursor: pointer;
            font-size: 1rem;
        }
    </style>
</head>
<body>
<div class="board-shell">
    <header class="board-header">
        <div class="board-title">
            <span class="pulse-dot" aria-hidden="true"></span>
            <div>
                <h1>Pedidos en bodega</h1>
                <p>Se actualiza solo · campanazo al llegar un pedido nuevo</p>
            </div>
        </div>
        <div class="board-meta">
            <span class="stat-pill" title="Pedidos nuevos (pagados)">
                Nuevos <strong id="count-new">0</strong>
            </span>
            <span class="stat-pill" title="En preparación">
                Preparando <strong id="count-prep">0</strong>
            </span>
            <span class="stat-pill" id="clock-pill">—</span>
            <button type="button" class="btn-board" id="btn-sound" aria-pressed="false">
                <i class="bi bi-bell-slash"></i> Sonido off
            </button>
            @if(auth()->user()?->isAdmin())
            <a href="{{ route('admin.orders.index') }}" class="btn-board">
                <i class="bi bi-arrow-left"></i> Ventas
            </a>
            @else
            <a href="{{ route('admin.account.password') }}" class="btn-board">
                <i class="bi bi-key"></i> Clave
            </a>
            <form action="{{ route('admin.logout') }}" method="post" style="display:inline;margin:0;">
                @csrf
                <button type="submit" class="btn-board">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </button>
            </form>
            @endif
        </div>
    </header>

    <div id="board-grid" class="board-grid" aria-live="polite"></div>
</div>

<div class="sound-banner is-visible" id="sound-banner" role="dialog" aria-modal="true" aria-labelledby="sound-title">
    <div class="sound-card">
        <h2 id="sound-title">Activar campanazo</h2>
        <p>Los navegadores bloquean el audio hasta que toques la pantalla. Actívalo para que suene cuando llegue un pedido.</p>
        <button type="button" id="btn-enable-sound">
            <i class="bi bi-bell-fill"></i> Activar sonido
        </button>
    </div>
</div>

<div class="toast-error" id="toast-error" role="alert"></div>

<script>
(() => {
    const feedUrl = @json(route('admin.warehouse.feed'));
    const updateUrlTemplate = @json(route('admin.warehouse.update', ['order' => '__ID__']));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const pollMs = 5000;

    const grid = document.getElementById('board-grid');
    const countNew = document.getElementById('count-new');
    const countPrep = document.getElementById('count-prep');
    const clockPill = document.getElementById('clock-pill');
    const btnSound = document.getElementById('btn-sound');
    const soundBanner = document.getElementById('sound-banner');
    const btnEnableSound = document.getElementById('btn-enable-sound');
    const toastError = document.getElementById('toast-error');

    let knownIds = new Set();
    let primed = false;
    let soundEnabled = false;
    let audioCtx = null;
    let pollTimer = null;
    let fetching = false;

    function updateUrl(id) {
        return updateUrlTemplate.replace('__ID__', String(id));
    }

    function showError(message) {
        toastError.textContent = message;
        toastError.classList.add('is-visible');
        clearTimeout(showError._t);
        showError._t = setTimeout(() => toastError.classList.remove('is-visible'), 4500);
    }

    function ensureAudio() {
        if (!audioCtx) {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return null;
            audioCtx = new Ctx();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        return audioCtx;
    }

    function playBell() {
        const ctx = ensureAudio();
        if (!ctx || !soundEnabled) return;

        const now = ctx.currentTime;
        const master = ctx.createGain();
        master.gain.setValueAtTime(0.0001, now);
        master.gain.exponentialRampToValueAtTime(0.45, now + 0.02);
        master.gain.exponentialRampToValueAtTime(0.0001, now + 1.1);
        master.connect(ctx.destination);

        [880, 1174.7].forEach((freq, i) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(freq, now);
            gain.gain.setValueAtTime(i === 0 ? 1 : 0.55, now);
            osc.connect(gain);
            gain.connect(master);
            osc.start(now + i * 0.08);
            osc.stop(now + 1.15);
        });
    }

    function setSoundEnabled(on) {
        soundEnabled = on;
        btnSound.setAttribute('aria-pressed', on ? 'true' : 'false');
        btnSound.classList.toggle('is-on', on);
        btnSound.classList.toggle('is-warn', !on);
        btnSound.innerHTML = on
            ? '<i class="bi bi-bell-fill"></i> Sonido on'
            : '<i class="bi bi-bell-slash"></i> Sonido off';
        if (on) {
            soundBanner.classList.remove('is-visible');
            ensureAudio();
            playBell();
        }
    }

    btnEnableSound.addEventListener('click', () => setSoundEnabled(true));
    btnSound.addEventListener('click', () => {
        if (!soundEnabled) {
            setSoundEnabled(true);
            return;
        }
        setSoundEnabled(false);
    });

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    function renderOrder(order) {
        const isNew = order.status === 'paid';
        const items = (order.items || []).map((item) => `
            <li>
                <span class="qty">${escapeHtml(item.quantity)}</span>
                <div>
                    <div class="item-name">${escapeHtml(item.name)}</div>
                    ${item.sku ? `<div class="item-sku">${escapeHtml(item.sku)}</div>` : ''}
                </div>
            </li>
        `).join('');

        const shipBits = [
            order.shipping_address,
            [order.shipping_comuna, order.shipping_region].filter(Boolean).join(', '),
            order.shipping_phone,
        ].filter(Boolean).map(escapeHtml).join('<br>');

        const action = isNew
            ? `<button type="button" class="action-btn prep" data-action="processing" data-id="${order.id}">
                    <i class="bi bi-box-seam"></i> Preparar
               </button>`
            : `<button type="button" class="action-btn ship" data-action="shipped" data-id="${order.id}">
                    <i class="bi bi-truck"></i> Despachar
               </button>`;

        return `
            <article class="order-card ${isNew ? 'is-new' : 'is-processing'}" data-order-id="${order.id}">
                <div class="order-head">
                    <div>
                        <div class="order-code">#${escapeHtml(order.code)}</div>
                        <div class="order-time">${escapeHtml(order.created_at_full)} · ${escapeHtml(order.total_label)} · ${escapeHtml(order.items_count)} ítems</div>
                    </div>
                    <span class="badge-status ${escapeHtml(order.status)}">${escapeHtml(order.status_label)}</span>
                </div>
                <div class="order-body">
                    <div class="customer-line">${escapeHtml(order.customer_name)}</div>
                    <div class="ship-line">${shipBits || 'Sin dirección'}</div>
                    <ul class="items-list">${items}</ul>
                </div>
                <div class="order-foot">${action}</div>
            </article>
        `;
    }

    function render(orders) {
        const paid = orders.filter((o) => o.status === 'paid').length;
        const prep = orders.filter((o) => o.status === 'processing').length;
        countNew.textContent = String(paid);
        countPrep.textContent = String(prep);

        if (!orders.length) {
            grid.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h2>Sin pedidos en cola</h2>
                    <p>Cuando alguien pague un pedido, aparecerá aquí con campanazo.</p>
                </div>
            `;
            return;
        }

        grid.innerHTML = orders.map(renderOrder).join('');
    }

    async function updateStatus(orderId, status, button) {
        if (button) button.disabled = true;
        try {
            const res = await fetch(updateUrl(orderId), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ status }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(data.message || 'No se pudo actualizar el pedido');
            }
            await poll(false);
        } catch (err) {
            showError(err.message || 'Error al actualizar');
            if (button) button.disabled = false;
        }
    }

    grid.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action]');
        if (!button) return;
        const id = Number(button.dataset.id);
        const status = button.dataset.action;
        if (!id || !status) return;
        updateStatus(id, status, button);
    });

    async function poll(announceNew) {
        if (fetching) return;
        fetching = true;
        try {
            const res = await fetch(feedUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store',
            });
            if (!res.ok) throw new Error('No se pudo cargar la cola de bodega');
            const data = await res.json();
            const orders = Array.isArray(data.orders) ? data.orders : [];
            const nextIds = new Set(orders.map((o) => o.id));

            if (primed && announceNew) {
                const newcomers = orders.filter((o) => !knownIds.has(o.id) && o.status === 'paid');
                if (newcomers.length) {
                    playBell();
                    if (newcomers.length > 1) {
                        setTimeout(playBell, 220);
                    }
                }
            }

            knownIds = nextIds;
            primed = true;
            render(orders);
        } catch (err) {
            showError(err.message || 'Error de conexión');
        } finally {
            fetching = false;
        }
    }

    function tickClock() {
        clockPill.textContent = new Date().toLocaleTimeString('es-CL', {
            hour: '2-digit', minute: '2-digit', second: '2-digit',
        });
    }

    poll(false);
    pollTimer = setInterval(() => poll(true), pollMs);
    tickClock();
    setInterval(tickClock, 1000);

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            poll(true);
        }
    });
})();
</script>
</body>
</html>
