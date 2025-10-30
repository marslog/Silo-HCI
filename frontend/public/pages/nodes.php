<?php
$config = require __DIR__ . '/../../src/Config/config.php';
$active = 'nodes';

use Silo\Services\ApiService;
$api = new ApiService();

// Get nodes
$response = $api->get('/nodes');
$nodes = $response['data'] ?? [];
?>

<?php include __DIR__ . '/../components/header.php'; ?>

<div class="main-wrapper">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Nodes</h1>
            <div class="page-actions">
                <button class="btn btn-success" onclick="openAddNodeModal()">
                    <i class="fas fa-plus"></i> Add Node
                </button>
                <button class="btn btn-secondary" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        
        <!-- Nodes Overview -->
        <div class="dashboard-grid">
            <?php 
            $totalNodes = count($nodes);
            $onlineNodes = count(array_filter($nodes, fn($n) => ($n['status'] ?? '') === 'online'));
            $totalCPU = array_sum(array_column($nodes, 'maxcpu'));
            $totalRAM = array_sum(array_column($nodes, 'maxmem'));
            ?>
            
            <div class="dashboard-card">
                <div class="card-icon bg-blue-500">
                    <i class="fas fa-server"></i>
                </div>
                <div class="card-content">
                    <div class="card-value"><?php echo $totalNodes; ?></div>
                    <div class="card-label">Total Nodes</div>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon bg-green-500">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-content">
                    <div class="card-value"><?php echo $onlineNodes; ?></div>
                    <div class="card-label">Online Nodes</div>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon bg-purple-500">
                    <i class="fas fa-microchip"></i>
                </div>
                <div class="card-content">
                    <div class="card-value"><?php echo $totalCPU; ?></div>
                    <div class="card-label">Total CPU Cores</div>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon bg-yellow-500">
                    <i class="fas fa-memory"></i>
                </div>
                <div class="card-content">
                    <div class="card-value"><?php echo round($totalRAM / (1024**3)); ?> GB</div>
                    <div class="card-label">Total Memory</div>
                </div>
            </div>
        </div>
        
        <!-- Nodes List -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Cluster Nodes</h2>
            </div>
            <div class="card-body">
                <?php if (empty($nodes)): ?>
                    <div style="text-align: center; padding: 3rem; color: #9ca3af;">
                        <i class="fas fa-server" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <p style="font-size: 1.125rem;">No nodes configured</p>
                        <button class="btn btn-success" onclick="openAddNodeModal()" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> Add Your First Node
                        </button>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Node</th>
                                <th>Status</th>
                                <th>CPU Usage</th>
                                <th>Memory Usage</th>
                                <th>Uptime</th>
                                <th>Version</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($nodes as $node): ?>
                                <?php
                                $status = $node['status'] ?? 'unknown';
                                $cpuPercent = isset($node['cpu']) && isset($node['maxcpu']) 
                                    ? ($node['cpu'] / $node['maxcpu']) * 100 : 0;
                                $memPercent = isset($node['mem']) && isset($node['maxmem']) 
                                    ? ($node['mem'] / $node['maxmem']) * 100 : 0;
                                $uptime = $node['uptime'] ?? 0;
                                $uptimeStr = gmdate("H:i:s", $uptime);
                                if ($uptime > 86400) {
                                    $days = floor($uptime / 86400);
                                    $uptimeStr = "{$days}d " . gmdate("H:i:s", $uptime % 86400);
                                }
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($node['node'] ?? 'N/A'); ?></strong>
                                        <br>
                                        <small style="color: var(--gray-500);">
                                            <?php echo htmlspecialchars($node['ip'] ?? 'N/A'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($status === 'online'): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-circle"></i> Online
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">
                                                <i class="fas fa-circle"></i> Offline
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill <?php 
                                                echo $cpuPercent > 90 ? 'bg-red-500' : 
                                                     ($cpuPercent > 70 ? 'bg-yellow-500' : 'bg-green-500'); 
                                            ?>" style="width: <?php echo $cpuPercent; ?>%"></div>
                                        </div>
                                        <small><?php echo round($cpuPercent, 1); ?>% (<?php echo $node['maxcpu'] ?? 0; ?> cores)</small>
                                    </td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill <?php 
                                                echo $memPercent > 90 ? 'bg-red-500' : 
                                                     ($memPercent > 70 ? 'bg-yellow-500' : 'bg-green-500'); 
                                            ?>" style="width: <?php echo $memPercent; ?>%"></div>
                                        </div>
                                        <small>
                                            <?php echo round($memPercent, 1); ?>% 
                                            (<?php echo round(($node['mem'] ?? 0) / (1024**3), 1); ?> / 
                                            <?php echo round(($node['maxmem'] ?? 0) / (1024**3), 1); ?> GB)
                                        </small>
                                    </td>
                                    <td><?php echo $uptimeStr; ?></td>
                                    <td>
                                        <small>
                                            <?php echo htmlspecialchars($node['pveversion'] ?? 'N/A'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="viewNode('<?php echo htmlspecialchars($node['node']); ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning" onclick="shellNode('<?php echo htmlspecialchars($node['node']); ?>')">
                                            <i class="fas fa-terminal"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Add Node Modal (Auto-Discovery) -->
<div id="addNodeModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-plus"></i> Add Node to Cluster
            </h3>
            <button class="modal-close" onclick="closeAddNodeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                <i class="fas fa-info-circle"></i>
                <strong>Auto-Discovery:</strong> Scan your network to automatically detect Proxmox nodes, or enter details manually.
            </div>
            
            <!-- Network Scan Section -->
            <div class="scan-section">
                <h4 style="margin-bottom: 1rem;">
                    <i class="fas fa-network-wired"></i> Network Discovery
                </h4>
                
                <div class="form-group">
                    <label class="form-label">Network Range</label>
                    <div style="display: flex; gap: 0.5rem; align-items: end;">
                        <div style="flex: 1;">
                            <input type="text" id="networkRange" class="form-control" 
                                   placeholder="192.168.0.0/24" value="192.168.0.0/24">
                            <small class="text-muted">CIDR notation (e.g., 192.168.0.0/24)</small>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="scanNetwork()" id="scanBtn">
                            <i class="fas fa-search"></i> Scan Network
                        </button>
                    </div>
                </div>
                
                <!-- Scan Results -->
                <div id="scanResults" style="display: none;">
                    <div class="detected-storage-section">
                        <h4>
                            <i class="fas fa-server"></i> Detected Proxmox Nodes
                            <span id="detectedCount" class="badge badge-success" style="margin-left: 0.5rem;">0</span>
                        </h4>
                        <div id="nodesList" class="storage-list"></div>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin: 2rem 0; color: var(--gray-400);">
                <span style="padding: 0 1rem; background: white; position: relative; z-index: 1;">OR</span>
                <hr style="margin-top: -1rem; border-color: var(--gray-200);">
            </div>
            
            <!-- Manual Entry Form -->
            <div class="scan-section">
                <h4 style="margin-bottom: 1rem;">
                    <i class="fas fa-edit"></i> Manual Configuration
                </h4>
                
                <form id="addNodeForm" onsubmit="return submitAddNode(event)">
                    <div class="form-group">
                        <label class="form-label">Node Hostname *</label>
                        <input type="text" name="hostname" id="nodeHostname" class="form-control" required 
                               placeholder="node2.example.com">
                        <small class="text-muted">Hostname or FQDN of the Proxmox node</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">IP Address *</label>
                        <input type="text" name="ip" id="nodeIP" class="form-control" required 
                               placeholder="192.168.0.201">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">API Port</label>
                        <input type="number" name="port" class="form-control" value="8006" 
                               placeholder="8006">
                        <small class="text-muted">Proxmox API port (default: 8006)</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Root Password *</label>
                        <input type="password" name="password" class="form-control" required>
                        <small class="text-muted">Root password for cluster join</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Cluster Fingerprint</label>
                        <textarea name="fingerprint" class="form-control" rows="3" 
                                  placeholder="Optional: Cluster SSL fingerprint for verification"></textarea>
                        <small class="text-muted">Leave empty to skip SSL verification</small>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Note:</strong> Adding a node to the cluster will configure it for high availability. 
                        Make sure the node is accessible and has Proxmox VE installed.
                    </div>
                </form>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeAddNodeModal()">Cancel</button>
            <button type="submit" form="addNodeForm" class="btn btn-success">
                <i class="fas fa-plus"></i> Add Node to Cluster
            </button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>

<style>
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.btn-sm + .btn-sm {
    margin-left: 0.25rem;
}

.modal-lg {
    max-width: 900px;
}

.scan-section {
    padding: 1.5rem;
    background: var(--gray-50);
    border-radius: var(--border-radius);
    border: 1px solid var(--gray-200);
    margin-bottom: 1.5rem;
}

.scan-section h4 {
    color: var(--gray-700);
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.detected-storage-section {
    margin-top: 1.5rem;
    padding: 1rem;
    background: white;
    border-radius: var(--border-radius);
    border: 1px solid var(--gray-200);
}

.detected-storage-section h4 {
    margin-bottom: 1rem;
    color: var(--gray-700);
    font-size: 1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
}

.storage-list {
    display: grid;
    gap: 0.75rem;
}

.storage-item {
    padding: 1rem;
    background: var(--gray-50);
    border-radius: var(--border-radius);
    border: 2px solid var(--gray-200);
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.storage-item:hover {
    border-color: var(--blue-500);
    background: var(--blue-50);
    transform: translateX(4px);
}

.storage-item.selected {
    border-color: var(--green-500);
    background: linear-gradient(90deg, rgba(34, 197, 94, 0.1) 0%, rgba(22, 163, 74, 0.05) 100%);
}

.storage-item-info {
    flex: 1;
}

.storage-item-name {
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
    font-size: 1.125rem;
}

.storage-item-details {
    font-size: 0.875rem;
    color: var(--gray-600);
    display: flex;
    gap: 1rem;
    margin-top: 0.5rem;
}

.storage-item-action {
    margin-left: 1rem;
}

.node-tag {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: var(--blue-100);
    color: var(--blue-700);
    border-radius: var(--border-radius);
    font-size: 0.75rem;
    margin-right: 0.25rem;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.scan-progress {
    text-align: center;
    padding: 2rem;
    color: var(--gray-600);
}

.scan-progress i {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: var(--blue-500);
}
</style>

<script>
let detectedNodes = [];

function openAddNodeModal() {
    document.getElementById('addNodeModal').classList.add('active');
}

function closeAddNodeModal() {
    document.getElementById('addNodeModal').classList.remove('active');
    document.getElementById('addNodeForm').reset();
    document.getElementById('scanResults').style.display = 'none';
    document.getElementById('nodesList').innerHTML = '';
    detectedNodes = [];
}

async function scanNetwork() {
    const networkRange = document.getElementById('networkRange').value;
    if (!networkRange) {
        alert('Please enter a network range');
        return;
    }
    
    const scanBtn = document.getElementById('scanBtn');
    const originalHTML = scanBtn.innerHTML;
    scanBtn.disabled = true;
    scanBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning...';
    
    const resultsDiv = document.getElementById('scanResults');
    resultsDiv.style.display = 'block';
    
    const nodesList = document.getElementById('nodesList');
    nodesList.innerHTML = '<div class="scan-progress"><i class="fas fa-spinner fa-spin"></i><p>Scanning network for Proxmox nodes...</p></div>';
    
    try {
        // Call backend API to scan network
        const response = await fetch('/api/v1/cluster/scan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ network: networkRange })
        });
        
        const result = await response.json();
        
        if (result.success && result.data && result.data.length > 0) {
            detectedNodes = result.data;
            displayDetectedNodes(result.data);
        } else {
            nodesList.innerHTML = '<p style="color: var(--gray-500); text-align: center; padding: 2rem;">No Proxmox nodes detected in this network range.</p>';
        }
    } catch (error) {
        nodesList.innerHTML = '<p style="color: var(--red-500); text-align: center; padding: 2rem;"><i class="fas fa-exclamation-triangle"></i> Error scanning network: ' + error.message + '</p>';
    } finally {
        scanBtn.disabled = false;
        scanBtn.innerHTML = originalHTML;
    }
}

function displayDetectedNodes(nodes) {
    const nodesList = document.getElementById('nodesList');
    const countBadge = document.getElementById('detectedCount');
    
    countBadge.textContent = nodes.length;
    nodesList.innerHTML = '';
    
    nodes.forEach(node => {
        const div = document.createElement('div');
        div.className = 'storage-item';
        div.onclick = () => selectDetectedNode(node, div);
        
        const hostname = node.hostname || node.node || 'Unknown';
        const ip = node.ip || 'N/A';
        const version = node.version || 'Unknown';
        const status = node.online ? 'Online' : 'Offline';
        const statusClass = node.online ? 'badge-success' : 'badge-warning';
        
        div.innerHTML = `
            <div class="storage-item-info">
                <div class="storage-item-name">
                    ${hostname}
                    <span class="badge ${statusClass}" style="font-size: 0.75rem; margin-left: 0.5rem;">
                        <i class="fas fa-circle"></i> ${status}
                    </span>
                </div>
                <div class="storage-item-details">
                    <span><i class="fas fa-network-wired"></i> ${ip}</span>
                    <span><i class="fas fa-box"></i> ${version}</span>
                    ${node.clustered ? '<span class="node-tag"><i class="fas fa-sitemap"></i> In Cluster</span>' : ''}
                </div>
            </div>
            <div class="storage-item-action">
                <i class="fas fa-chevron-right"></i>
            </div>
        `;
        
        nodesList.appendChild(div);
    });
}

function selectDetectedNode(node, element) {
    // Unselect all
    document.querySelectorAll('.storage-item').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    
    // Pre-fill manual form
    document.getElementById('nodeHostname').value = node.hostname || node.node || '';
    document.getElementById('nodeIP').value = node.ip || '';
    
    // Scroll to form
    document.querySelector('.scan-section:last-of-type').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function submitAddNode(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Remove empty fields
    Object.keys(data).forEach(key => {
        if (data[key] === '' || data[key] === null) {
            delete data[key];
        }
    });
    
    try {
        const response = await fetch('/api/v1/cluster/nodes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Node added to cluster successfully!');
            closeAddNodeModal();
            location.reload();
        } else {
            alert('Error adding node: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
    
    return false;
}

function viewNode(nodeName) {
    window.location.href = `/nodes/${nodeName}`;
}

function shellNode(nodeName) {
    window.open(`/nodes/${nodeName}/shell`, '_blank');
}

function refreshData() {
    location.reload();
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}
</script>
