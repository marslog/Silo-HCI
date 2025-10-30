<?php
use Silo\Services\ApiService;
$api = new ApiService();

// Get cluster status
try {
    $clusterStatus = $api->get('/cluster/status');
    $clusterData = $clusterStatus['data'] ?? [];
} catch (Exception $e) {
    $clusterData = [];
}
?>

<aside class="control-panel">
    <div class="panel-header">
        <h3 class="panel-title">
            <i class="fas fa-tachometer-alt"></i>
            System Monitor
        </h3>
    </div>
    
    <div class="panel-content">
        <!-- Cluster Info -->
        <div class="panel-section">
            <h4 class="section-title">
                <i class="fas fa-layer-group"></i>
                Cluster Status
            </h4>
            <div class="status-item">
                <span class="status-label">Status</span>
                <span class="status-value">
                    <span class="status-indicator online"></span>
                    Online
                </span>
            </div>
            <div class="status-item">
                <span class="status-label">Nodes</span>
                <span class="status-value">
                    <?php echo count($clusterData); ?>
                </span>
            </div>
            <div class="status-item">
                <span class="status-label">Quorum</span>
                <span class="status-value">
                    <span class="status-indicator online"></span>
                    Active
                </span>
            </div>
        </div>
        
        <!-- Resource Usage -->
        <div class="panel-section">
            <h4 class="section-title">
                <i class="fas fa-chart-line"></i>
                Resources
            </h4>
            
            <!-- CPU -->
            <div class="metric-item">
                <div class="metric-header">
                    <span class="metric-label">
                        <i class="fas fa-microchip"></i> CPU
                    </span>
                    <span class="metric-value" id="cpuUsage">--%</span>
                </div>
                <div class="metric-bar">
                    <div class="metric-fill" id="cpuBar" style="width: 0%"></div>
                </div>
            </div>
            
            <!-- Memory -->
            <div class="metric-item">
                <div class="metric-header">
                    <span class="metric-label">
                        <i class="fas fa-memory"></i> RAM
                    </span>
                    <span class="metric-value" id="memUsage">--%</span>
                </div>
                <div class="metric-bar">
                    <div class="metric-fill" id="memBar" style="width: 0%"></div>
                </div>
            </div>
            
            <!-- Storage -->
            <div class="metric-item">
                <div class="metric-header">
                    <span class="metric-label">
                        <i class="fas fa-hdd"></i> Storage
                    </span>
                    <span class="metric-value" id="diskUsage">--%</span>
                </div>
                <div class="metric-bar">
                    <div class="metric-fill" id="diskBar" style="width: 0%"></div>
                </div>
            </div>
        </div>
        
        <!-- Network Activity -->
        <div class="panel-section">
            <h4 class="section-title">
                <i class="fas fa-exchange-alt"></i>
                Network
            </h4>
            <div class="network-stats">
                <div class="stat-item">
                    <i class="fas fa-arrow-down text-success"></i>
                    <div>
                        <div class="stat-label">Incoming</div>
                        <div class="stat-value" id="netIn">-- KB/s</div>
                    </div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-arrow-up text-primary"></i>
                    <div>
                        <div class="stat-label">Outgoing</div>
                        <div class="stat-value" id="netOut">-- KB/s</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Running VMs/Containers -->
        <div class="panel-section">
            <h4 class="section-title">
                <i class="fas fa-running"></i>
                Active Resources
            </h4>
            <div class="status-item">
                <span class="status-label">
                    <i class="fas fa-desktop"></i> VMs
                </span>
                <span class="status-value" id="runningVms">--</span>
            </div>
            <div class="status-item">
                <span class="status-label">
                    <i class="fas fa-cube"></i> Containers
                </span>
                <span class="status-value" id="runningCt">--</span>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="panel-section">
            <h4 class="section-title">
                <i class="fas fa-bolt"></i>
                Quick Actions
            </h4>
            <div class="quick-actions">
                <button class="action-btn action-primary" onclick="window.location.href='/vms'">
                    <i class="fas fa-plus"></i>
                    <span>Create VM</span>
                </button>
                <button class="action-btn action-info" onclick="window.location.href='/containers'">
                    <i class="fas fa-plus"></i>
                    <span>Create LXC</span>
                </button>
                <button class="action-btn action-success" onclick="window.location.href='/backup'">
                    <i class="fas fa-save"></i>
                    <span>Backup</span>
                </button>
                <button class="action-btn action-secondary" onclick="refreshMetrics()">
                    <i class="fas fa-sync-alt"></i>
                    <span>Refresh</span>
                </button>
            </div>
        </div>
    </div>
</aside>

<script>
// Auto-refresh metrics every 5 seconds
setInterval(refreshMetrics, 5000);
refreshMetrics();

function refreshMetrics() {
    // Fetch cluster resources
    fetch('/api/v1/cluster/resources', {credentials: 'include'})
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data) {
                updateMetrics(data.data);
            }
        })
        .catch(err => console.error('Error fetching metrics:', err));
}

function updateMetrics(resources) {
    let totalCpu = 0, usedCpu = 0;
    let totalMem = 0, usedMem = 0;
    let totalDisk = 0, usedDisk = 0;
    let runningVms = 0, runningCt = 0;
    
    resources.forEach(resource => {
        if (resource.type === 'node') {
            totalCpu += resource.maxcpu || 0;
            usedCpu += resource.cpu || 0;
            totalMem += resource.maxmem || 0;
            usedMem += resource.mem || 0;
            totalDisk += resource.maxdisk || 0;
            usedDisk += resource.disk || 0;
        } else if (resource.type === 'qemu' && resource.status === 'running') {
            runningVms++;
        } else if (resource.type === 'lxc' && resource.status === 'running') {
            runningCt++;
        }
    });
    
    // Update CPU
    const cpuPercent = totalCpu > 0 ? Math.round((usedCpu / totalCpu) * 100) : 0;
    document.getElementById('cpuUsage').textContent = cpuPercent + '%';
    document.getElementById('cpuBar').style.width = cpuPercent + '%';
    document.getElementById('cpuBar').className = 'metric-fill ' + getColorClass(cpuPercent);
    
    // Update Memory
    const memPercent = totalMem > 0 ? Math.round((usedMem / totalMem) * 100) : 0;
    document.getElementById('memUsage').textContent = memPercent + '%';
    document.getElementById('memBar').style.width = memPercent + '%';
    document.getElementById('memBar').className = 'metric-fill ' + getColorClass(memPercent);
    
    // Update Disk
    const diskPercent = totalDisk > 0 ? Math.round((usedDisk / totalDisk) * 100) : 0;
    document.getElementById('diskUsage').textContent = diskPercent + '%';
    document.getElementById('diskBar').style.width = diskPercent + '%';
    document.getElementById('diskBar').className = 'metric-fill ' + getColorClass(diskPercent);
    
    // Update Running VMs/Containers
    document.getElementById('runningVms').textContent = runningVms;
    document.getElementById('runningCt').textContent = runningCt;
}

function getColorClass(percent) {
    if (percent > 90) return 'metric-danger';
    if (percent > 70) return 'metric-warning';
    return 'metric-success';
}
</script>
