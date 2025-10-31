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
            <div class="widget-wide-card">
                <div class="widget-header">
                    <h3>Utilization Trend (last ~10 min)</h3>
                </div>
                <div class="widget-body">
                    <div id="chartTrendCpuMem" class="chart-box" aria-label="CPU and Memory Trend"></div>
                </div>
            </div>
            <div class="widget-wide-card">
                <div class="widget-header">
                    <h3>Per-Node CPU</h3>
                </div>
                <div class="widget-body">
                    <div id="chartPerNodeCpu" class="chart-box" aria-label="Per-Node CPU"></div>
                </div>
            </div>
        </div>
        
        <!-- Nodes Table (enhanced) -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Cluster Nodes</h2>
            </div>
            <div class="card-body">
                <table class="data-table" id="nodesTable">
                    <thead>
                        <tr>
                            <th>Node</th>
                            <th>Status</th>
                            <th>CPU</th>
                            <th>Memory</th>
                            <th>Uptime</th>
                            <th>Version</th>
                            <th>KVM</th>
                            <th>NICs</th>
                            <th>VMs</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($nodes['data'])): ?>
                            <?php foreach ($nodes['data'] as $node): ?>
                                <tr data-node-row="<?php echo htmlspecialchars($node['node']); ?>">
                                    <td>
                                        <a href="/nodes/<?php echo $node['node']; ?>" class="text-blue-400 hover:underline">
                                            <?php echo htmlspecialchars($node['node']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $node['status'] === 'online' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($node['status'] ?? 'unknown'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $cpuPercent = isset($node['cpu']) ? round($node['cpu'] * 100, 1) : 0;
                                        echo $cpuPercent . '%';
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($node['mem']) && isset($node['maxmem'])) {
                                            $memPercent = round(($node['mem'] / $node['maxmem']) * 100, 1);
                                            echo $memPercent . '%';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo isset($node['uptime']) ? formatUptime($node['uptime']) : 'N/A'; ?></td>
                                    <td data-node-version>—</td>
                                    <td data-node-kvm>—</td>
                                    <td data-node-nics>—</td>
                                    <td data-node-vms>—</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="viewNode('<?php echo $node['node']; ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">No nodes found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Storage Overview -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Storage Overview</h2>
            </div>
            <div class="card-body">
                <div id="storageDonuts" class="storage-grid" aria-label="Storage Utilization Donuts"></div>
                <table class="data-table" id="storageTable">
                    <thead>
                        <tr>
                            <th>Storage</th>
                            <th>Type</th>
                            <th>Shared</th>
                            <th>Used</th>
                            <th>Total</th>
                            <th>Usage</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <!-- Recent Tasks -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Recent Cluster Tasks</h2>
            </div>
            <div class="card-body">
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
/* Widgets layout for Grafana-like look */
.widgets-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-auto-rows: 260px;
    gap: 16px;
    margin-top: 16px;
}
.widget-card, .widget-wide-card {
    background: #0f172a;
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 10px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.35);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.widget-wide-card { grid-column: span 2; }
.widget-header {
    padding: 12px 14px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0));
}
.widget-header h3 { font-size: 14px; font-weight: 600; color: #e5e7eb; }
.widget-body { flex: 1; padding: 6px 10px; }
.chart-box { width: 100%; height: 100%; min-height: 200px; }

.storage-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 12px;
    margin-bottom: 12px;
}
.storage-donut-card {
    background: #0b1220;
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 10px;
    padding: 8px;
}
.storage-donut-title { font-size: 12px; color: #cbd5e1; margin-bottom: 6px; }
.storage-donut-box { width: 100%; height: 160px; }

@media (max-width: 1024px) {
  .widgets-grid { grid-template-columns: 1fr; }
  .widget-wide-card { grid-column: span 1; }
}
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
        // Enrich node rows with version/KVM/NICs/VMs
        const rows = Array.from(document.querySelectorAll('tr[data-node-row]'));
        for (const row of rows) {
            const node = row.getAttribute('data-node-row');
            try {
                const [stRes, kvmRes, netRes, vmsRes] = await Promise.all([
                    fetch(`/api/v1/nodes/${node}/status`).then(r=>r.json()).catch(()=>({})),
                    fetch(`/api/v1/nodes/${node}/kvm`).then(r=>r.json()).catch(()=>({})),
                    fetch(`/api/v1/nodes/${node}/network`).then(r=>r.json()).catch(()=>({})),
                    fetch(`/api/v1/nodes/${node}/vms`).then(r=>r.json()).catch(()=>({}))
                ]);
                const version = stRes?.data?.pveversion || stRes?.data?.pveversioninfo?.release || '—';
                const kvm = (kvmRes?.data?.available === false) ? 'Unavailable' : 'Available';
                const kvmTitle = kvmRes?.data?.reason || '';
                const nics = Array.isArray(netRes?.data) ? netRes.data.filter(n=>String(n?.active||'').toString()==='1' || n?.autostart==='1').length : '—';
                const vms = Array.isArray(vmsRes?.data) ? vmsRes.data.length : '—';
                const vCell = row.querySelector('[data-node-version]'); if (vCell) vCell.textContent = version;
                const kCell = row.querySelector('[data-node-kvm]'); if (kCell) { kCell.textContent = kvm; if (kvmTitle) kCell.title = kvmTitle; }
                const nCell = row.querySelector('[data-node-nics]'); if (nCell) nCell.textContent = nics;
                const vmCell = row.querySelector('[data-node-vms]'); if (vmCell) vmCell.textContent = vms;
            } catch (e) { /* ignore per-node errors */ }
        }

        // Storage overview via cluster resources
        try {
            const res = await fetch('/api/v1/cluster/resources');
            const data = await res.json();
            if (data.success && Array.isArray(data.data)) {
                const storages = data.data.filter(r => r.type === 'storage');
                const tbody = document.querySelector('#storageTable tbody');
                if (tbody) {
                    tbody.innerHTML = '';
                    storages.forEach(s => {
                        const total = s.maxdisk || 0; const used = s.disk || 0;
                        const pct = total ? Math.round((used/total)*100) : 0;
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${s.storage || s.id || '—'}</td>
                            <td>${s.storagetype || '—'}</td>
                            <td>${s.shared ? 'Yes' : 'No'}</td>
                            <td>${formatBytes(used)}</td>
                            <td>${formatBytes(total)}</td>
                            <td>
                                <div class="progress-bar"><div class="progress-fill" style="width:${pct}%"></div></div>
                            </td>`;
                        tbody.appendChild(tr);
                    });
                }
            }
        } catch (e) {}

        // Recent tasks
        try {
            const res = await fetch('/api/v1/cluster/tasks');
            const data = await res.json();
            if (data.success && Array.isArray(data.data)) {
                const tasks = data.data.slice(0, 10);
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
            // Initial data push from PHP summary
            try {
                const cpu0 = Number(<?php echo json_encode($summary['data']['cpu']['percentage'] ?? 0); ?>);
                const mem0 = Number(<?php echo json_encode($summary['data']['memory']['percentage'] ?? 0); ?>);
                pushTrendPoint(cpu0, mem0);
                updateGauges(cpu0, mem0);
            } catch (e) {}

            // Poll summary every 5s for trends and gauges
            setInterval(async () => {
                try {
                    const r = await fetch('/api/v1/monitoring/summary');
                    const j = await r.json();
                    const cpu = Number(j?.data?.cpu?.percentage || 0);
                    const mem = Number(j?.data?.memory?.percentage || 0);
                    pushTrendPoint(cpu, mem);
                    updateGauges(cpu, mem);
                } catch (e) { /* ignore */ }
            }, 5000);

            // Poll nodes for per-node CPU every 10s
            await refreshPerNodeCpu();
            setInterval(refreshPerNodeCpu, 10000);

            // Poll storage for donuts every 30s
            await refreshStorageDonuts();
            setInterval(refreshStorageDonuts, 30000);
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
let trendState = { t: [], cpu: [], mem: [] };
let charts = { cpuGauge: null, memGauge: null, trend: null, nodeCpu: null };

function ensureEChartsLoaded() {
    return new Promise((resolve) => {
        if (window.echarts) return resolve(true);
        const s = document.createElement('script');
        s.src = '/cdn/npm/echarts@5.5.0/dist/echarts.min.js';
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
    const trendEl = document.getElementById('chartTrendCpuMem');
    const nodeCpuEl = document.getElementById('chartPerNodeCpu');
    if (cpuEl) charts.cpuGauge = echarts.init(cpuEl, null, { renderer: 'canvas' });
    if (memEl) charts.memGauge = echarts.init(memEl, null, { renderer: 'canvas' });
    if (trendEl) charts.trend = echarts.init(trendEl, null, { renderer: 'canvas' });
    if (nodeCpuEl) charts.nodeCpu = echarts.init(nodeCpuEl, null, { renderer: 'canvas' });

    setGaugeOptions(charts.cpuGauge, 'CPU');
    setGaugeOptions(charts.memGauge, 'Memory');
    setTrendOptions();
    setNodeCpuOptions([]);

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

function setTrendOptions() {
    if (!charts.trend) return;
    const option = {
        backgroundColor: 'transparent',
        grid: { left: 40, right: 20, top: 20, bottom: 28 },
        tooltip: { trigger: 'axis' },
        legend: { data: ['CPU %','Memory %'], textStyle: { color: '#cbd5e1' } },
        xAxis: { type: 'category', data: trendState.t, axisLabel: { color: '#94a3b8' }, axisLine: { lineStyle: { color: '#334155' } } },
        yAxis: { type: 'value', min: 0, max: 100, axisLabel: { color: '#94a3b8' }, splitLine: { lineStyle: { color: '#1f2937' } } },
        series: [
            { name: 'CPU %', type: 'line', smooth: true, showSymbol: false, data: trendState.cpu, areaStyle: { opacity: 0.15 }, lineStyle: { width: 2 }, color: '#60a5fa' },
            { name: 'Memory %', type: 'line', smooth: true, showSymbol: false, data: trendState.mem, areaStyle: { opacity: 0.08 }, lineStyle: { width: 2 }, color: '#34d399' }
        ]
    };
    charts.trend.setOption(option);
}

function pushTrendPoint(cpuPct, memPct) {
    const ts = new Date();
    const label = ts.toLocaleTimeString([], { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const maxPoints = 120; // ~10 minutes at 5s
    trendState.t.push(label);
    trendState.cpu.push(Number(cpuPct));
    trendState.mem.push(Number(memPct));
    if (trendState.t.length > maxPoints) {
        trendState.t.shift(); trendState.cpu.shift(); trendState.mem.shift();
    }
    setTrendOptions();
}

async function refreshPerNodeCpu() {
    if (!charts.nodeCpu) return;
    try {
        const r = await fetch('/api/v1/nodes');
        const j = await r.json();
        const list = Array.isArray(j?.data) ? j.data : [];
        const nodes = list.map(n => ({ name: n.node, cpu: Math.round((n.cpu||0)*1000)/10 }));
        // sort by cpu desc, top 12
        nodes.sort((a,b)=>b.cpu-a.cpu);
        setNodeCpuOptions(nodes.slice(0,12));
    } catch (e) { /* ignore */ }
}

function setNodeCpuOptions(items) {
    if (!charts.nodeCpu) return;
    const names = items.map(i=>i.name);
    const vals = items.map(i=>i.cpu);
    const option = {
        backgroundColor: 'transparent',
        grid: { left: 80, right: 20, top: 10, bottom: 20 },
        xAxis: { type: 'value', max: 100, axisLabel: { color: '#94a3b8' }, splitLine: { lineStyle: { color: '#1f2937' } } },
        yAxis: { type: 'category', data: names, axisLabel: { color: '#cbd5e1' }, axisLine: { lineStyle: { color: '#334155' } } },
        series: [{
            type: 'bar', data: vals, barWidth: 14,
            label: { show: true, position: 'right', formatter: '{c}%', color: '#e5e7eb' },
            itemStyle: { color: (params)=> {
                const v = params.value;
                if (v<50) return '#10b981'; if (v<80) return '#f59e0b'; return '#ef4444';
            }}
        }]
    };
    charts.nodeCpu.setOption(option);
}

async function refreshStorageDonuts() {
    if (!window.echarts) return;
    try {
        const res = await fetch('/api/v1/cluster/resources');
        const data = await res.json();
        const storages = (data?.data || []).filter(r => r.type === 'storage');
        // pick top 6 by total size
        const sorted = storages
            .map(s=>({
                id: s.storage || s.id || 'unknown',
                type: s.storagetype || '—',
                total: Number(s.maxdisk||0),
                used: Number(s.disk||0),
                shared: !!s.shared
            }))
            .sort((a,b)=>b.total-a.total)
            .slice(0,6);

        const grid = document.getElementById('storageDonuts');
        if (!grid) return;
        grid.innerHTML = '';
        const donutCharts = [];
        sorted.forEach(s => {
            const card = document.createElement('div');
            card.className = 'storage-donut-card';
            const title = document.createElement('div');
            title.className = 'storage-donut-title';
            title.textContent = `${s.id} • ${s.shared ? 'Shared' : 'Local'} • ${s.type}`;
            const box = document.createElement('div');
            const cid = `storage-donut-${cssSafeId(s.id)}`;
            box.id = cid; box.className = 'storage-donut-box';
            card.appendChild(title); card.appendChild(box);
            grid.appendChild(card);
            const chart = echarts.init(box, null, { renderer: 'canvas' });
            donutCharts.push({ chart, s });
        });

        donutCharts.forEach(({chart, s}) => {
            const used = s.used; const total = s.total; const free = Math.max(total-used,0);
            const usedPct = total ? Math.round((used/total)*100) : 0;
            chart.setOption({
                backgroundColor: 'transparent',
                tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
                title: { left: 'center', top: '38%', text: usedPct + '%', textStyle: { color: '#e5e7eb', fontSize: 18, fontWeight: 'bold' } },
                series: [{
                    type: 'pie', radius: ['60%','85%'], avoidLabelOverlap: true, label: { show: false },
                    data: [
                        { value: used, name: 'Used', itemStyle: { color: '#60a5fa' } },
                        { value: free, name: 'Free', itemStyle: { color: '#1f2937' } }
                    ]
                }]
            });
        });
    } catch (e) { /* ignore */ }
}

function cssSafeId(s){ return String(s).replace(/[^a-zA-Z0-9_-]+/g,'-'); }
</script>
