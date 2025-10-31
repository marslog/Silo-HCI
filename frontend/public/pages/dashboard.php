<?php
$config = require __DIR__ . '/../../src/Config/config.php';
$active = 'dashboard';

use Silo\Services\ApiService;
$api = new ApiService();

// Get dashboard data with defaults
$summaryResponse = $api->get('/monitoring/summary');
$nodesResponse = $api->get('/nodes');

$summary = [
    'data' => $summaryResponse['data'] ?? [
        'nodes' => ['online' => 0, 'total' => 0],
        'vms' => ['running' => 0, 'total' => 0],
        'cpu' => ['percentage' => 0],
        'memory' => ['percentage' => 0]
    ]
];

$nodes = [
    'data' => $nodesResponse['data'] ?? []
];
?>

<?php include __DIR__ . '/../components/header.php'; ?>

<div class="main-wrapper">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Dashboard</h1>
            <div class="page-actions">
                <button class="btn btn-primary" onclick="refreshDashboard()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="dashboard-grid">
            <!-- Nodes Card -->
            <div class="dashboard-card">
                <div class="card-icon bg-blue-500">
                    <i class="fas fa-server"></i>
                </div>
                <div class="card-content">
                    <div class="card-value">
                        <?php echo $summary['data']['nodes']['online'] ?? 0; ?> / 
                        <?php echo $summary['data']['nodes']['total'] ?? 0; ?>
                    </div>
                    <div class="card-label">Nodes Online</div>
                </div>
            </div>
            
            <!-- VMs Card -->
            <div class="dashboard-card">
                <div class="card-icon bg-green-500">
                    <i class="fas fa-desktop"></i>
                </div>
                <div class="card-content">
                    <div class="card-value">
                        <?php echo $summary['data']['vms']['running'] ?? 0; ?> / 
                        <?php echo $summary['data']['vms']['total'] ?? 0; ?>
                    </div>
                    <div class="card-label">VMs Running</div>
                </div>
            </div>
            
            <!-- CPU Card -->
            <div class="dashboard-card">
                <div class="card-icon bg-yellow-500">
                    <i class="fas fa-microchip"></i>
                </div>
                <div class="card-content">
                    <div class="card-value">
                        <?php echo number_format($summary['data']['cpu']['percentage'] ?? 0, 1); ?>%
                    </div>
                    <div class="card-label">CPU Usage</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $summary['data']['cpu']['percentage'] ?? 0; ?>%"></div>
                    </div>
                </div>
            </div>
            
            <!-- Memory Card -->
            <div class="dashboard-card">
                <div class="card-icon bg-purple-500">
                    <i class="fas fa-memory"></i>
                </div>
                <div class="card-content">
                    <div class="card-value">
                        <?php echo number_format($summary['data']['memory']['percentage'] ?? 0, 1); ?>%
                    </div>
                    <div class="card-label">Memory Usage</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $summary['data']['memory']['percentage'] ?? 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Grafana-like Widgets -->
        <div class="widgets-grid">
            <div class="widget-card">
                <div class="widget-header">
                    <h3>Cluster CPU</h3>
                </div>
                <div class="widget-body">
                    <div id="chartClusterCpuGauge" class="chart-box" aria-label="Cluster CPU Gauge"></div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-header">
                    <h3>Cluster Memory</h3>
                </div>
                <div class="widget-body">
                    <div id="chartClusterMemGauge" class="chart-box" aria-label="Cluster Memory Gauge"></div>
                </div>
            </div>
        </div>
        
        <!-- Nodes Overview (icon tiles) -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Cluster Nodes</h2>
            </div>
            <div class="card-body">
                <div id="nodesGrid" class="info-grid"></div>
            </div>
        </div>

        <!-- Storage Overview (icon tiles) -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Storage Overview</h2>
            </div>
            <div class="card-body">
                <div id="storageGrid" class="storage-grid" aria-label="Storage Tiles"></div>
            </div>
        </div>

        <!-- Recent Tasks (preview) -->
        <div class="content-card">
            <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
                <h2 class="card-title">Recent Cluster Tasks</h2>
                <a href="/tasks" class="btn btn-outline" style="text-decoration:none"><i class="fas fa-list"></i> View all</a>
            </div>
            <div class="card-body">
                <div class="table-scroll small-table">
                <table class="data-table" id="tasksTable">
                    <thead>
                        <tr>
                            <th>UPID</th>
                            <th>Node</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Start</th>
                            <th>End</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                </div>
            </div>
        </div>
        
    </main>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>

<?php
function formatUptime($seconds) {
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    return "{$days}d {$hours}h {$minutes}m";
}
?>

<style>
/* Widgets layout – align with Silo light theme (no dark mode) */
.widgets-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-auto-rows: 180px; /* compact */
    gap: 16px;
    margin-top: 16px;
    margin-bottom: 18px; /* add space below gauges */
}
.widget-card, .widget-wide-card {
    background: var(--glass-surface);
    border: 1px solid var(--glass-border);
    border-radius: 10px;
    box-shadow: var(--glass-shadow);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.widget-wide-card { grid-column: span 2; }
.widget-header {
    padding: 12px 14px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.18);
    background: linear-gradient(135deg, rgba(248, 250, 252, 0.9) 0%, rgba(241, 245, 249, 0.85) 100%);
}
.widget-header h3 { font-size: 11px; font-weight: 600; color: var(--gray-800); }
.widget-body { flex: 1; padding: 6px 10px; }
.chart-box { width: 100%; height: 100%; min-height: 160px; }

.storage-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
    margin: 8px 0 12px;
}

/* Generic compact info tiles (used by Nodes and Storage) */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
    margin: 8px 0 4px;
}
.info-tile, .storage-tile {
    background: var(--glass-surface);
    border: 1px solid var(--glass-border);
    border-radius: 10px;
    padding: 12px 14px;
    box-shadow: var(--glass-shadow);
}
.info-tile:hover, .storage-tile:hover { border-color: #93c5fd; transform: translateY(-1px); transition: border-color 0.15s ease, transform 0.15s ease; cursor: pointer; }
.tile-head { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
.tile-icon { width:26px; height:26px; border-radius:6px; display:flex; align-items:center; justify-content:center; color:#fff; }
.tile-icon.ok { background: var(--gradient-blue); }
.tile-icon.bad { background: var(--gradient-danger); }
.tile-title { font-weight:700; color: var(--gray-800); flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.tile-title a { color: inherit; text-decoration: none; }
.tile-status { font-size: 11px; color: var(--gray-600); }
.tile-body { display:flex; flex-direction:column; gap:8px; }
.tile-row { display:grid; grid-template-columns: 16px 64px 1fr auto; align-items:center; gap:8px; font-size:12px; color: var(--gray-700); }
.tile-row i { text-align:center; color:#64748b; }
.mini-bar { height:6px; background: rgba(148,163,184,0.2); border-radius:999px; overflow:hidden; }
.mini-bar > div { height:100%; background: var(--gradient-blue); }
.tile-meta { font-size:12px; color:#475569; }

/* Section spacing for clearer separation between blocks */
.widgets-grid + .content-card { margin-top: 18px; }
.content-card { margin-bottom: 16px; }
.content-card .card-header { margin-bottom: 4px; }

@media (max-width: 1024px) {
  .widgets-grid { grid-template-columns: 1fr; }
  .widget-wide-card { grid-column: span 1; }
}

/* Scale down overall dashboard UI further for compact look */
.page-title { font-size: 1.35rem; }
.dashboard-card { padding: calc(var(--spacing-md)*0.7) calc(var(--spacing-lg)*0.7); }
.dashboard-card .card-icon { width: 30px; height: 30px; font-size: 0.95rem; }
.card-value { font-size: 1.05rem; }
.card-header { padding: calc(var(--spacing-lg)*0.7) calc(var(--spacing-xl)*0.7); }
.card-body { padding: calc(var(--spacing-lg)*0.7) calc(var(--spacing-xl)*0.7); }
.data-table th, .data-table td { padding: calc(var(--spacing-md)*0.65); }
.data-table th { font-size: 0.78rem; }

/* Small scrollable table for Recent Tasks */
.table-scroll { max-height: 260px; overflow-y: auto; }
.small-table thead th, .small-table td { font-size: 12px; }
.small-table thead th { position: sticky; top: 0; z-index: 1; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); }
</style>

<script>
function refreshDashboard() {
    location.reload();
}

function viewNode(node) {
    window.location.href = '/nodes/' + node;
}

document.addEventListener('DOMContentLoaded', async () => {
    try {
        // Build nodes icon grid with compact info
        try {
            const r = await fetch('/api/v1/nodes');
            const j = await r.json();
            const list = Array.isArray(j?.data) ? j.data : [];
            const grid = document.getElementById('nodesGrid');
            if (grid) {
                grid.innerHTML = '';
                for (const n of list) {
                    const node = n.node;
                    try {
                        const [stRes, kvmRes, vmsRes] = await Promise.all([
                            fetch(`/api/v1/nodes/${node}/status`).then(r=>r.json()).catch(()=>({})),
                            fetch(`/api/v1/nodes/${node}/kvm`).then(r=>r.json()).catch(()=>({})),
                            fetch(`/api/v1/nodes/${node}/vms`).then(r=>r.json()).catch(()=>({}))
                        ]);
                        const status = (n.status||'unknown').toLowerCase();
                        const cpuPct = typeof n.cpu==='number' ? Math.round(n.cpu*1000)/10 : 0;
                        const memPct = (n.mem && n.maxmem) ? Math.round((n.mem/n.maxmem)*1000)/10 : 0;
                        const version = stRes?.data?.pveversion || stRes?.data?.pveversioninfo?.release || '—';
                        const kvmAvail = (kvmRes?.data?.available === false) ? false : true;
                        const vmCount = Array.isArray(vmsRes?.data) ? vmsRes.data.length : '—';
                        const card = document.createElement('div');
                        card.className = 'info-tile';
                        card.innerHTML = `
                            <div class="tile-head">
                                <div class="tile-icon ${status==='online'?'ok':'bad'}"><i class="fas fa-network-wired"></i></div>
                                <div class="tile-title"><a href="/nodes/${node}">${node}</a></div>
                                <span class="tile-status ${status}">${status}</span>
                            </div>
                            <div class="tile-body">
                                <div class="tile-row"><i class="fas fa-microchip"></i><span>CPU</span><div class="mini-bar"><div style="width:${cpuPct}%"></div></div><b>${cpuPct}%</b></div>
                                <div class="tile-row"><i class="fas fa-memory"></i><span>Memory</span><div class="mini-bar"><div style="width:${memPct}%"></div></div><b>${memPct}%</b></div>
                                <div class="tile-meta"><i class="fas fa-code-branch"></i> ${version} • <i class="fas fa-desktop"></i> ${vmCount} • <i class="fas fa-bolt"></i> ${kvmAvail?'KVM':'No KVM'}</div>
                            </div>`;
                        grid.appendChild(card);
                    } catch(e){}
                }
            }
        } catch (e) {}

        // Storage overview tiles
        await refreshStorageTiles();

        // Recent tasks
        try {
            const res = await fetch('/api/v1/cluster/tasks');
            const data = await res.json();
            if (data.success && Array.isArray(data.data)) {
                const tasks = data.data
                    .sort((a,b)=> (b.starttime||0) - (a.starttime||0))
                    .slice(0, 10);
                const tbody = document.querySelector('#tasksTable tbody');
                if (tbody) {
                    tbody.innerHTML = '';
                    tasks.forEach(t => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td title="${t.upid || ''}">${(t.upid||'').toString().slice(0,18)}…</td>
                            <td>${t.node || '—'}</td>
                            <td>${t.user || '—'}</td>
                            <td>${t.type || '—'}</td>
                            <td>${t.status || '—'}</td>
                            <td>${formatTime(t.starttime)}</td>
                            <td>${formatTime(t.endtime)}</td>`;
                        tbody.appendChild(tr);
                    });
                }
            }
        } catch (e) {}

        // Charts: load ECharts and initialize widgets
        await ensureEChartsLoaded();
        if (window.echarts) {
            initDashboardCharts();
            // Initial gauges from PHP summary
            try {
                const cpu0 = Number(<?php echo json_encode($summary['data']['cpu']['percentage'] ?? 0); ?>);
                const mem0 = Number(<?php echo json_encode($summary['data']['memory']['percentage'] ?? 0); ?>);
                updateGauges(cpu0, mem0);
            } catch (e) {}

            // Poll summary every 5s for gauges
            setInterval(async () => {
                try {
                    const r = await fetch('/api/v1/monitoring/summary');
                    const j = await r.json();
                    const cpu = Number(j?.data?.cpu?.percentage || 0);
                    const mem = Number(j?.data?.memory?.percentage || 0);
                    updateGauges(cpu, mem);
                } catch (e) { /* ignore */ }
            }, 5000);

            // Poll storage tiles every 30s
            await refreshStorageTiles();
            setInterval(refreshStorageTiles, 30000);
        }
    } catch (err) {
        console.warn('Dashboard enrichment failed', err);
    }
});

function formatBytes(bytes){
    if (!bytes || bytes <= 0) return '0 B';
    const units = ['B','KiB','MiB','GiB','TiB','PiB'];
    let i = Math.floor(Math.log(bytes)/Math.log(1024));
    i = Math.min(i, units.length-1);
    return (bytes/Math.pow(1024,i)).toFixed(i>1?2:1)+' '+units[i];
}
function formatTime(ts){
    if (!ts) return '—';
    try { const d = new Date(ts*1000); return d.toLocaleString(); } catch(e){ return '—'; }
}

// ---------- Charts logic ----------
let charts = { cpuGauge: null, memGauge: null };

function ensureEChartsLoaded() {
    return new Promise((resolve) => {
        if (window.echarts) return resolve(true);
        const s = document.createElement('script');
        // Load ECharts via same-origin CDN proxy. Note: unpkg paths do not include the 'npm/' segment.
        // Using '/cdn/echarts@5.5.0/...' ensures Nginx proxies to 'https://unpkg.com/echarts@5.5.0/...'
        s.src = '/cdn/echarts@5.5.0/dist/echarts.min.js';
        s.async = true;
        s.onload = () => resolve(true);
        s.onerror = () => resolve(false);
        document.head.appendChild(s);
        // Timeout fallback in 5s
        setTimeout(() => resolve(!!window.echarts), 5000);
    });
}

function initDashboardCharts() {
    const cpuEl = document.getElementById('chartClusterCpuGauge');
    const memEl = document.getElementById('chartClusterMemGauge');
    if (cpuEl) charts.cpuGauge = echarts.init(cpuEl, null, { renderer: 'canvas' });
    if (memEl) charts.memGauge = echarts.init(memEl, null, { renderer: 'canvas' });

    setGaugeOptions(charts.cpuGauge, 'CPU');
    setGaugeOptions(charts.memGauge, 'Memory');

    window.addEventListener('resize', () => {
        Object.values(charts).forEach(c => c && c.resize());
    });
}

function setGaugeOptions(chart, label) {
    if (!chart) return;
    const option = {
        backgroundColor: 'transparent',
        tooltip: { formatter: '{a}<br/>{c}%' },
        series: [{
            name: label,
            type: 'gauge',
            center: ['50%','58%'],
            startAngle: 200,
            endAngle: -20,
            radius: '95%',
            min: 0,
            max: 100,
            splitNumber: 5,
            itemStyle: { color: '#60a5fa' },
            progress: { show: true, width: 10 },
            pointer: { show: true, width: 3 },
            axisLine: { lineStyle: { width: 10, color: [[0.5,'#10b981'],[0.8,'#f59e0b'],[1,'#ef4444']] } },
            axisTick: { distance: -15, length: 6, lineStyle: { color: '#94a3b8' } },
            splitLine: { distance: -15, length: 12, lineStyle: { color: '#94a3b8' } },
            axisLabel: { distance: -32, color: '#cbd5e1', fontSize: 10 },
            title: { show: true, offsetCenter: [0, '-30%'], color: '#e5e7eb', fontSize: 12 },
            detail: { valueAnimation: true, formatter: '{value}%', color: '#e5e7eb' },
            data: [{ value: 0, name: label }]
        }]
    };
    chart.setOption(option);
}

function updateGauges(cpuPct, memPct) {
    if (charts.cpuGauge) charts.cpuGauge.setOption({ series: [{ data: [{ value: Number(cpuPct.toFixed ? cpuPct.toFixed(1) : cpuPct), name: 'CPU' }] }] });
    if (charts.memGauge) charts.memGauge.setOption({ series: [{ data: [{ value: Number(memPct.toFixed ? memPct.toFixed(1) : memPct), name: 'Memory' }] }] });
}

// removed trend/per-node chart helpers

async function refreshStorageTiles() {
    try {
        const res = await fetch('/api/v1/cluster/resources');
        const data = await res.json();
        const storages = (data?.data || []).filter(r => r.type === 'storage');
        const sorted = storages
            .map(s=>({
                id: s.storage || s.id || 'unknown',
                type: s.storagetype || '—',
                total: Number(s.maxdisk||0),
                used: Number(s.disk||0),
                shared: !!s.shared
            }))
            .sort((a,b)=>b.total-a.total)
            .slice(0,8);

        const grid = document.getElementById('storageGrid');
        if (!grid) return;
        grid.innerHTML = '';
        sorted.forEach(s => {
            const used = s.used; const total = s.total; const pct = total ? Math.round((used/total)*100) : 0;
            const card = document.createElement('div');
            card.className = 'storage-tile';
            card.innerHTML = `
                <div class="tile-head">
                    <div class="tile-icon ok"><i class="fas fa-hdd"></i></div>
                    <div class="tile-title" title="${s.id}">${s.id}</div>
                    <span class="tile-status">${s.shared ? 'Shared' : 'Local'}</span>
                </div>
                <div class="tile-body">
                    <div class="tile-row"><i class="fas fa-database"></i><span>Type</span><b>${s.type}</b></div>
                    <div class="tile-row"><i class="fas fa-chart-pie"></i><span>Usage</span>
                        <div class="mini-bar"><div style="width:${pct}%"></div></div><b>${pct}%</b>
                    </div>
                    <div class="tile-meta">${formatBytes(used)} / ${formatBytes(total)}</div>
                </div>`;
            grid.appendChild(card);
        });
    } catch (e) { /* ignore */ }
}

function cssSafeId(s){ return String(s).replace(/[^a-zA-Z0-9_-]+/g,'-'); }
</script>
