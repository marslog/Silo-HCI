<?php
$config = require __DIR__ . '/../../src/Config/config.php';
$active = 'vms';

$node = $_GET['node'] ?? '';
$vmid = $_GET['vmid'] ?? '';

if (!$node || !$vmid) {
    http_response_code(400);
    echo 'Missing node or vmid';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Console - <?php echo htmlspecialchars($node . '/' . $vmid); ?></title>
    <link rel="stylesheet" href="/assets/fonts/fontawesome.css">
    <link rel="icon" type="image/svg+xml" href="/assets/img/logo-silo.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="/assets/img/logo-silo.svg">
    <meta name="theme-color" content="#0b1220">
    <style>
        :root {
            --silo-bg: #0b1220;
            --silo-surface: #0f172a;
            --silo-surface-2: #0b0f1a; /* darker, reduce gray look */
            --silo-border: #1f2a44;
            --silo-text: #e5e7eb;
            --silo-text-dim: #9ca3af;
            --silo-accent: #22d3ee;
            --silo-success: #10b981;
            --silo-warn: #f59e0b;
            --silo-error: #ef4444;
        }
        * { box-sizing: border-box; }
        body { margin:0; background:var(--silo-surface-2); color:var(--silo-text); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .toolbar {
            padding: 0.5rem 0.75rem;
            background: linear-gradient(180deg, var(--silo-bg), #0d1528);
            border-bottom: 1px solid var(--silo-border);
            display:flex; align-items:center; gap:0.75rem;
            position: sticky; top: 0; z-index: 10;
            box-shadow: 0 2px 10px rgba(0,0,0,0.35);
        }
        .brand { display:flex; align-items:center; gap:0.6rem; min-width: 0; }
        .brand img { height:20px; width:auto; opacity:0.9; }
        .brand .title { display:flex; flex-direction:column; line-height:1.1; }
        .brand .name { font-weight:600; font-size:0.9rem; letter-spacing:0.2px; }
        .brand .sub { color:var(--silo-text-dim); font-size:0.75rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .actions { display:flex; align-items:center; gap:0.4rem; margin-left:auto; }
        .btn { border:1px solid var(--silo-border); background:#111a2c; color:var(--silo-text); border-radius:8px; cursor:pointer; height:30px; display:inline-flex; align-items:center; gap:0.4rem; padding:0 0.55rem; transition:all .15s ease; }
        .btn:hover { background:#18233a; border-color:#2a3a60; box-shadow: 0 1px 0 rgba(255,255,255,0.04) inset, 0 1px 8px rgba(0,0,0,0.25); }
        .btn-secondary { font-size:0.8rem; }
        .btn i { opacity:0.9; }
        .chip { display:inline-flex; align-items:center; gap:0.4rem; border:1px solid var(--silo-border); border-radius:999px; padding:0 0.6rem; height:26px; font-size:0.78rem; color:var(--silo-text-dim); background:#0f1a2d; }
        .chip .dot { width:8px; height:8px; border-radius:999px; background:var(--silo-text-dim); box-shadow:0 0 0 2px rgba(255,255,255,0.03) inset }
        .chip.ok { color:var(--silo-success); border-color: rgba(16,185,129,0.35); background: rgba(16,185,129,0.08); }
        .chip.ok .dot { background: var(--silo-success); }
        .chip.warn { color:var(--silo-warn); border-color: rgba(245,158,11,0.35); background: rgba(245,158,11,0.08); }
        .chip.warn .dot { background: var(--silo-warn); }
        .chip.err { color:var(--silo-error); border-color: rgba(239,68,68,0.35); background: rgba(239,68,68,0.08); }
        .chip.err .dot { background: var(--silo-error); }
        .surface { background:var(--silo-surface-2); }
        .vnc-stage { flex:1; position:relative; overflow:hidden; background: var(--silo-surface-2); }
        .bg-grid {
            background-image:
                repeating-linear-gradient(0deg, rgba(255,255,255,0.02) 0, rgba(255,255,255,0.02) 1px, transparent 1px, transparent 32px),
                repeating-linear-gradient(90deg, rgba(255,255,255,0.02) 0, rgba(255,255,255,0.02) 1px, transparent 1px, transparent 32px);
            background-color: var(--silo-surface-2);
        }
        .bg-glow {
            background-image:
                radial-gradient(1200px 60% at 30% -20%, rgba(34,211,238,0.07), transparent 60%),
                radial-gradient(1200px 60% at 80% 120%, rgba(99,102,241,0.06), transparent 60%),
                repeating-linear-gradient(0deg, rgba(255,255,255,0.02) 0, rgba(255,255,255,0.02) 1px, transparent 1px, transparent 32px),
                repeating-linear-gradient(90deg, rgba(255,255,255,0.02) 0, rgba(255,255,255,0.02) 1px, transparent 1px, transparent 32px);
            background-color: var(--silo-surface-2);
        }
        @media (max-width: 720px) {
            .brand .sub { display:none; }
            .actions { gap: 0.3rem; }
            .btn { height:28px; padding:0 0.5rem; }
            .chip { display:none; }
        }
    </style>
</head>
<body>

<main style="padding: 0; margin-left:0; background: transparent;">
    <div style="background:#0b1220; height:100vh; display:flex; flex-direction:column;">
        <div class="toolbar">
            <div class="brand">
                <img src="/assets/img/logo-silo.svg" alt="Silo" />
                <div class="title">
                    <div class="name">Silo HCI</div>
                    <div class="sub">Console • <?php echo htmlspecialchars($node . ' / ' . $vmid); ?></div>
                </div>
            </div>
            <div class="actions">
                <button id="btnReconn" class="btn btn-secondary" title="Reconnect">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button id="btnCAD" class="btn btn-secondary" title="Ctrl+Alt+Del">
                    <i class="fas fa-keyboard"></i> CAD
                </button>
                <button id="btnFit" class="btn btn-secondary" title="Toggle Fit to Window">
                    <i class="fas fa-expand-arrows-alt"></i> Fit
                </button>
                <button id="btnFullscreen" class="btn btn-secondary" title="Fullscreen">
                    <i class="fas fa-window-maximize"></i>
                </button>
                <button id="btnNative" class="btn btn-secondary" title="Open via Proxmox (native noVNC)">
                    <i class="fas fa-external-link-alt"></i> Native
                </button>
                <div id="status" class="chip warn"><span class="dot"></span><span class="txt">Connecting…</span></div>
            </div>
        </div>
        <div id="vnc_container" class="vnc-stage">
            <!-- noVNC renders here -->
            <input id="vnc_touch_input" type="text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" style="position:absolute; left:-10000px; top:-10000px; opacity:0; width:1px; height:1px;">
        </div>
    </div>
</main>

<script type="module">
// Use Proxmox native noVNC inside an iframe by default
const USE_NATIVE_IFRAME = true;
const node = <?php echo json_encode($node); ?>;
const vmid = <?php echo json_encode($vmid); ?>;
const statusEl = document.getElementById('status');
const container = document.getElementById('vnc_container');
let iframe = null;
let rfb = null;
let lastWsUrl = null;
let triedFallback = false;
let fitToWindow = true;
let lastTicket = null;
let authRetryDone = false;
let lastCsrfToken = null;
let lastVncCert = null;  // VNC server certificate for validation
let isConnecting = false; // prevent parallel connection attempts
let isFetchingTicket = false; // prevent parallel ticket requests
let getConsolePromise = null; // dedupe/await concurrent ticket requests
let hadSecurityFailure = false; // mark last error as VNC security failure
const ALLOW_DIRECT_FALLBACK = true; // TRY direct connection to Proxmox if proxy fails
const PROXMOX_HOST = '192.168.0.200';
const PROXMOX_PORT = 8006;

function setStatus(text, ok=false, level=null) {
    // support chip UI with inner text span, fallback to element text
    const txtEl = statusEl.querySelector ? statusEl.querySelector('.txt') : null;
    if (txtEl) txtEl.textContent = text; else statusEl.textContent = text;
    if (statusEl.classList) {
        statusEl.classList.remove('ok','warn','err');
        if (level) statusEl.classList.add(level);
        else statusEl.classList.add(ok ? 'ok' : 'warn');
    } else {
        statusEl.style.color = ok ? '#10b981' : '#9ca3af';
    }
}

async function getConsole() {
    // Dedupe: if a ticket request is in-flight, await it instead of throwing
    if (getConsolePromise) {
        console.log('[VNC] Awaiting in-flight ticket request…');
        return getConsolePromise;
    }
    isFetchingTicket = true;
    getConsolePromise = (async () => {
        const res = await fetch(`/api/v1/nodes/${node}/qemu/${vmid}/console`);
        const data = await res.json();
        if (!data.success) throw new Error(data.error || 'Console ticket failed');
        if (!data.data.ws_path) throw new Error('WebSocket path not provided');
    
    // Store CSRF token and set headers that Proxmox requires
    if (data.data.csrf_token) {
        lastCsrfToken = data.data.csrf_token;
        console.log('[Console] CSRF Token:', lastCsrfToken.substring(0, 40) + '...');
        // Set CSRF token as a header that the browser will include
        // Note: X-CSRF-Token is a common pattern, but Proxmox uses CSRFPreventionToken
        // We'll set both in case Proxmox WebSocket checks different header names
    }
    
    // Set the PVEAuthCookie immediately so the browser includes it in the WebSocket handshake
    if (data.data.pve_auth_cookie) {
        const cookieVal = data.data.pve_auth_cookie;
        // Set cookie with Path=/ so it's included in ALL requests, including WebSocket upgrades
        // Clear any old cookie first, then set new one
        document.cookie = `PVEAuthCookie=; Path=/; Max-Age=0`;
        // Use Secure and SameSite=None to ensure cookie is sent on HTTPS WebSocket requests
        document.cookie = `PVEAuthCookie=${cookieVal}; Path=/; Max-Age=7200; Secure; SameSite=None`;
        console.log('[Console] Set PVEAuthCookie:', cookieVal.substring(0, 40) + '...');
        console.log('[Console] Current cookies:', document.cookie);
        // Wait for browser to persist the cookie before WebSocket connects
        await new Promise(r => setTimeout(r, 250));
    }
    
    // Debug: log the exact ticket format we're about to use
    console.log('[Console] Ticket from vncproxy:', data.data.ticket.substring(0, 50) + '...');
    
    // Store the VNC server certificate for validation
    if (data.data.vnc_cert) {
        lastVncCert = data.data.vnc_cert;
        console.log('[Console] Stored VNC cert for server validation');
    }
    
        return data.data;
    })();
    try {
        return await getConsolePromise;
    } finally {
        isFetchingTicket = false;
        getConsolePromise = null;
    }
}

function applyScaling() {
    if (!rfb) return;
    rfb.scaleViewport = fitToWindow;
    rfb.resizeSession = false;
}

function connect(wsUrl, ticket) {
    if (isConnecting) {
        console.warn('[VNC] Connection attempt ignored (already connecting)');
        return;
    }
    isConnecting = true;
    if (!RFB) { setStatus('Error: RFB module not loaded'); return; }
    if (!wsUrl || typeof wsUrl !== 'string') {
        console.error('[VNC] Missing or invalid WebSocket URL:', wsUrl);
        setStatus('Error: Invalid WebSocket URL');
        isConnecting = false;
        return;
    }
    if (rfb) { try { rfb.disconnect(); } catch (e) {} rfb = null; }
    lastWsUrl = wsUrl;
    lastTicket = ticket || lastTicket;
    // Log exactly what we're about to connect with
    console.log('[VNC] Connecting with:');
    console.log('  Full URL:', wsUrl);
    console.log('  URL length:', wsUrl.length);
    const urlObj = new URL(wsUrl);
    const qp_port = urlObj.searchParams.get('port');
    const qp_ticket = urlObj.searchParams.get('vncticket');
    console.log('  Query params:', {
        port: qp_port,
        vncticket_len: qp_ticket?.length,
        vncticket_first_80: qp_ticket?.substring(0, 80),
        vncticket_has_plus: qp_ticket?.includes('+'),
    });

    // KasmVNC/noVNC variant signature used by our build: new RFB(target, focusEl, url, options)
    // Provide a hidden focusable input as the second parameter, then the URL
    const hiddenInput = ensureHiddenInput();
    const rfbOptions = {
        shared: true,
        wsProtocols: ['binary'],
        credentials: { password: lastTicket || '' },
    };
    rfb = new RFB(container, hiddenInput, wsUrl, rfbOptions);
    // Also set credentials directly on the instance (covers variants)
    try { if (lastTicket) rfb.credentials = { password: lastTicket }; } catch (e) { /* ignore */ }
    rfb.viewOnly = false;
    rfb.scaleViewport = fitToWindow;
    rfb.background = '#111827';
    rfb.addEventListener('connect', () => { 
        setStatus('Connected', true); 
        triedFallback = false; 
        authRetryDone = false;
        hadSecurityFailure = false;
        isConnecting = false;
        console.log('[VNC] Connected successfully to Proxmox VNC server');
    });
    rfb.addEventListener('desktopname', (e) => {
        console.log('[VNC] Desktop name:', e.detail.name);
    });
    rfb.addEventListener('securityfailure', (e) => {
        // Catch security failures and log them in detail
        const detail = e?.detail || {};
        console.warn('[VNC] Security failure event:', detail);
        setStatus('Security error: ' + (detail.reason || 'Unknown'));
        hadSecurityFailure = true;
        
        // Log what we have available
        console.log('[Debug] Available for auth:');
        console.log('  - lastTicket:', lastTicket ? lastTicket.substring(0, 50) + '...' : 'MISSING');
        console.log('  - lastCsrfToken:', lastCsrfToken ? lastCsrfToken.substring(0, 40) + '...' : 'MISSING');
        console.log('  - Cookie set:', document.cookie.includes('PVEAuthCookie') ? 'YES' : 'NO');
        
        // On security failure, first attempt a FRESH-TICKET proxied retry (most robust)
        if (!authRetryDone) {
            authRetryDone = true;
            console.warn('[VNC] Security failed, requesting fresh ticket and retrying via proxy first...');
            setTimeout(async () => {
                try {
                    try { if (rfb) rfb.disconnect(); } catch {}
                    isConnecting = false;
                    const freshInfo = await getConsole();
                    lastWsInfo = freshInfo;
                    lastTicket = freshInfo.ticket;
                    const proxiedWs = `wss://${window.location.host}/pve${freshInfo.ws_path}`;
                    console.log('[VNC] Retrying proxied connection with fresh ticket...');
                    connect(proxiedWs, freshInfo.ticket);
                } catch (err) {
                    console.error('[VNC] Error fetching fresh ticket for proxied retry:', err);
                    // If fetching a fresh ticket failed, try direct as a last resort if allowed
                    if (ALLOW_DIRECT_FALLBACK && lastWsInfo && lastWsInfo.direct_ws_url) {
                        console.warn('[VNC] Falling back to DIRECT due to ticket fetch error...');
                        try {
                            if (rfb) rfb.disconnect();
                        } catch {}
                        isConnecting = false;
                        setTimeout(() => connect(lastWsInfo.direct_ws_url, lastTicket), 100);
                    }
                }
            }, 200);
        }
    });
    rfb.addEventListener('disconnect', (e) => {
        isConnecting = false;
        const reason = (e && e.detail && e.detail.reason) ? `: ${e.detail.reason}` : '';
        const clean = (e && e.detail && typeof e.detail.clean !== 'undefined') ? e.detail.clean : 'unknown';
        setStatus('Disconnected' + reason);
        console.log('[VNC Disconnect]', { reason: e?.detail?.reason, clean, detail: e?.detail });
        const reasonText = (e && e.detail && e.detail.reason) ? (e.detail.reason + '') : '';
        
        // If we recently saw security failure or the reason matches auth failure, try DIRECT
        if (!triedFallback && (hadSecurityFailure || /Authentication failed|Security negotiation failed|authentication/i.test(reasonText))) {
            authRetryDone = true;
            console.warn('[VNC] Auth/Security failed with proxied connection');
            
            if (ALLOW_DIRECT_FALLBACK && lastWsInfo && lastWsInfo.direct_ws_url) {
                console.log('[VNC] Attempting DIRECT connection to Proxmox (bypassing Nginx)...');
                console.log('[VNC] Direct URL:', lastWsInfo.direct_ws_url.substring(0, 100) + '...');
                triedFallback = true;
                // Request a FRESH ticket before retrying with direct connection
                // because vncticket has a very short TTL (2-5 seconds)
                setTimeout(async () => {
                    try {
                        console.log('[VNC] Requesting fresh ticket before direct retry...');
                        const freshInfo = await getConsole();
                        lastWsInfo = freshInfo;
                        lastTicket = freshInfo.ticket;
                        const freshDirectUrl = `wss://${PROXMOX_HOST}:${PROXMOX_PORT}/api2/json/nodes/${node}/qemu/${vmid}/vncwebsocket?port=${freshInfo.port}&vncticket=${encodeURIComponent(freshInfo.ticket)}`;
                        console.log('[VNC] Got fresh ticket, retrying direct connection...');
                        connect(freshDirectUrl, freshInfo.ticket);
                    } catch (err) {
                        console.error('[VNC] Error requesting fresh ticket or direct connection:', err);
                        setStatus('Retry failed: ' + err.message);
                    }
                }, 200);
                return;
            } else {
                console.error('[VNC] Authentication failed - no fallback available');
                setStatus('Authentication failed - please check console logs');
                return;
            }
        }
        
        // General reconnect only for network-level unclean disconnects (not auth failures)
        if (e.detail && e.detail.clean === false) {
            console.log('[VNC] Unclean disconnect (network error), reconnecting in 2s...');
            setTimeout(init, 2000);
        }
    });
    rfb.addEventListener('credentialsrequired', (e) => {
        // Try multiple credential formats that Proxmox might accept
        if (lastTicket && rfb) {
            try {
                console.log('[VNC] credentialsrequired event fired');
                console.log('[VNC] Attempting authentication with vncticket...');
                
                // Method 1: vncticket as password (standard)
                console.log('[VNC] Trying: password = vncticket');
                rfb.sendCredentials({ password: lastTicket }); 
            } catch (err) {
                console.error('[VNC] Error sending credentials:', err);
            }
        } else {
            console.warn('[VNC] credentialsrequired but no ticket available');
        }
    });
    
    rfb.addEventListener('clipboard', (e) => {
        console.log('[VNC] Clipboard event:', e.detail);
    });
}

function ensureHiddenInput() {
    let el = document.getElementById('novncHiddenInput');
    if (!el) {
        el = document.createElement('input');
        el.type = 'text';
        el.id = 'novncHiddenInput';
        el.autocomplete = 'off';
        el.style.position = 'absolute';
        el.style.opacity = '0';
        el.style.pointerEvents = 'none';
        el.style.height = '0';
        el.style.width = '0';
        container.appendChild(el);
    }
    return el;
}

let lastWsInfo = null;
async function init() {
    try {
        if (USE_NATIVE_IFRAME) {
            setStatus('Preparing native console…');
            // Ensure we have a valid PVE session cookie (same-origin for /pve/)
            await getConsole();
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = 'pve_iframe';
                iframe.style.position = 'absolute';
                iframe.style.inset = '0';
                iframe.style.width = '100%';
                iframe.style.height = '100%';
                iframe.style.border = '0';
                container.appendChild(iframe);
            }
            const nativeUrl = `/pve/?console=kvm&novnc=1&node=${encodeURIComponent(String(node))}&vmid=${encodeURIComponent(String(vmid))}&resize=1`;
            iframe.onload = () => setStatus('Connected (native)', true);
            iframe.onerror = () => setStatus('Failed to load native console');
            iframe.src = nativeUrl;
            return;
        }

        if (isConnecting || isFetchingTicket) {
            console.log('[Init] Skipping init (busy)');
            return;
        }
        setStatus('Requesting ticket…');
        const info = await getConsole();
        lastWsInfo = info;
        lastTicket = info.ticket || lastTicket;
        const proxiedWs = `wss://${window.location.host}/pve${info.ws_path}`;
        triedFallback = false;
        authRetryDone = false;
        setTimeout(() => connect(proxiedWs, info.ticket), 50);
    } catch (err) {
        isFetchingTicket = false;
        setStatus('Error: ' + (err.message || err));
        console.error(err);
    }
}

document.getElementById('btnReconn').addEventListener('click', () => {
    if (USE_NATIVE_IFRAME) {
        if (iframe) iframe.src = iframe.src; else init();
    } else {
        init();
    }
});
// Hide CAD and Fit in native mode
if (USE_NATIVE_IFRAME) {
    document.getElementById('btnCAD').style.display = 'none';
    document.getElementById('btnFit').style.display = 'none';
} else {
    document.getElementById('btnCAD').addEventListener('click', function() { if (rfb) rfb.sendCtrlAltDel(); });
    document.getElementById('btnFit').addEventListener('click', function() {
        fitToWindow = !fitToWindow;
        this.innerHTML = fitToWindow ? '<i class="fas fa-expand-arrows-alt"></i> Fit' : '<i class="fas fa-search"></i> 1:1';
        applyScaling();
    });
}
document.getElementById('btnFullscreen').addEventListener('click', async function() {
    try {
        const target = container;
        if (!document.fullscreenElement) await target.requestFullscreen();
        else await document.exitFullscreen();
    } catch (e) { console.warn('Fullscreen error', e); }
});

// Open Proxmox's native noVNC console via our /pve/ proxy (same-origin)
document.getElementById('btnNative').addEventListener('click', async function() {
    try {
        // Ensure we have a current session cookie first
        try { await getConsole(); } catch (e) { /* ignore errors, cookie may already be set */ }
        const nativeUrl = `/pve/?console=kvm&novnc=1&node=${encodeURIComponent(String(node))}&vmid=${encodeURIComponent(String(vmid))}&resize=1`;
        window.open(nativeUrl, '_blank', 'noopener');
    } catch (e) {
        console.error('Open native console failed', e);
        setStatus('Open native console failed: ' + (e.message || e));
    }
});

init();
</script>

</body>
</html>
