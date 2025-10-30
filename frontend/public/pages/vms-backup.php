<?php
$config = require __DIR__ . '/../../src/Config/config.php';
$active = 'vms';

use Silo\Services\ApiService;
$api = new ApiService();

// Get VMs
$vmsResponse = $api->get("/vms");
$allVMs = $vmsResponse['data'] ?? [];

// Get nodes for dropdown
$nodesResponse = $api->get('/nodes');
$nodes = $nodesResponse['data'] ?? [];

// Get storage for dropdown
$storageResponse = $api->get('/storage');
$allStorage = $storageResponse['data'] ?? [];
?>

<?php include __DIR__ . '/../components/header.php'; ?>

<div class="main-wrapper">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Virtual Machines</h1>
            <div class="page-actions">
                <button class="btn btn-success" onclick="openCreateVMModal()">
                    <i class="fas fa-plus"></i> Create Virtual Machine
                </button>
                <button class="btn btn-secondary" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        
        <!-- VMs Overview -->
        <div class="dashboard-grid">
            <?php 
            $totalVMs = count($allVMs);
            $runningVMs = count(array_filter($allVMs, fn($vm) => ($vm['status'] ?? '') === 'running'));
            $stoppedVMs = $totalVMs - $runningVMs;
            $totalCPUs = array_sum(array_column($allVMs, 'cpus'));
            ?>
            
            <div class="dashboard-card">
                <div class="card-icon bg-blue-500">
                    <i class="fas fa-desktop"></i>
                </div>
                <div class="card-content">
                    <div class="card-value"><?php echo $totalVMs; ?></div>
                    <div class="card-label">Total VMs</div>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon bg-green-500">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="card-content">
                    <div class="card-value"><?php echo $runningVMs; ?></div>
                    <div class="card-label">Running</div>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon bg-gray-500">
                    <i class="fas fa-stop-circle"></i>
                </div>
                <div class="card-content">
                    <div class="card-value"><?php echo $stoppedVMs; ?></div>
                    <div class="card-label">Stopped</div>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon bg-purple-500">
                    <i class="fas fa-microchip"></i>
                </div>
                <div class="card-content">
                    <div class="card-value"><?php echo $totalCPUs; ?></div>
                    <div class="card-label">Total vCPUs</div>
                </div>
            </div>
        </div>
        
        <!-- VMs List -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Virtual Machines</h2>
            </div>
            <div class="card-body">
                <?php if (empty($allVMs)): ?>
                    <div style="text-align: center; padding: 3rem; color: #9ca3af;">
                        <i class="fas fa-desktop" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <p style="font-size: 1.125rem;">No virtual machines</p>
                        <button class="btn btn-success" onclick="openCreateVMModal()" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> Create Your First VM
                        </button>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Node</th>
                                <th>CPU</th>
                                <th>Memory</th>
                                <th>Uptime</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allVMs as $vm): ?>
                                <?php
                                $status = $vm['status'] ?? 'unknown';
                                $uptime = $vm['uptime'] ?? 0;
                                $uptimeStr = $uptime > 0 ? gmdate("H:i:s", $uptime) : '-';
                                if ($uptime > 86400) {
                                    $days = floor($uptime / 86400);
                                    $uptimeStr = "{$days}d " . gmdate("H:i:s", $uptime % 86400);
                                }
                                ?>
                                <tr>
                                    <td><strong><?php echo $vm['vmid'] ?? 'N/A'; ?></strong></td>
                                    <td><?php echo htmlspecialchars($vm['name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($status === 'running'): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-circle"></i> Running
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-circle"></i> Stopped
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($vm['node'] ?? 'N/A'); ?></td>
                                    <td><?php echo $vm['cpus'] ?? 0; ?> cores</td>
                                    <td><?php echo round(($vm['maxmem'] ?? 0) / (1024**3), 1); ?> GB</td>
                                    <td><?php echo $uptimeStr; ?></td>
                                    <td>
                                        <?php if ($status === 'running'): ?>
                                            <button class="btn btn-sm btn-warning" onclick="stopVM('<?php echo $vm['node']; ?>', <?php echo $vm['vmid']; ?>)">
                                                <i class="fas fa-stop"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success" onclick="startVM('<?php echo $vm['node']; ?>', <?php echo $vm['vmid']; ?>)">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-primary" onclick="viewVM('<?php echo $vm['node']; ?>', <?php echo $vm['vmid']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteVM('<?php echo $vm['node']; ?>', <?php echo $vm['vmid']; ?>)">
                                            <i class="fas fa-trash"></i>
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

<!-- Create VM Modal (ESXi-style with expandable sections) -->
<div id="createVMModal" class="modal">
    <div class="modal-content modal-xl">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-plus"></i> New Virtual Machine - Customize Settings
            </h3>
            <button class="modal-close" onclick="closeCreateVMModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="vm-creation-info">
                <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                    <i class="fas fa-info-circle"></i>
                    Configure the virtual machine hardware and options. Click on each section to expand/collapse details.
                </div>
            </div>
            
            <form id="createVMForm" onsubmit="return submitCreateVM(event)">
                <!-- Expandable Sections List -->
                <div class="vm-settings-list">
                    
                    <!-- Basic Settings Section -->
                    <div class="vm-section" onclick="toggleSection(this)">
                        <div class="vm-section-header">
                            <i class="fas fa-chevron-right section-arrow"></i>
                            <i class="fas fa-cog section-icon"></i>
                            <span class="section-title">Basic Settings</span>
                            <span class="section-value" id="basicValue">Not configured</span>
                        </div>
                        <div class="vm-section-body">
                            <div class="section-content">
                                <div class="form-group">
                                    <label class="form-label">Node *</label>
                                    <select name="node" class="form-control" required onchange="updateBasicValue()">
                                        <option value="">Select Node</option>
                                        <?php foreach ($nodes as $node): ?>
                                            <option value="<?php echo htmlspecialchars($node['node']); ?>">
                                                <?php echo htmlspecialchars($node['node']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">VM ID *</label>
                                        <input type="number" name="vmid" class="form-control" required 
                                               placeholder="100" min="100" max="999999999" onchange="updateBasicValue()">
                                        <small class="text-muted">Unique ID</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">VM Name *</label>
                                        <input type="text" name="name" class="form-control" required 
                                               placeholder="my-vm" onchange="updateBasicValue()">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-checkbox">
                                        <input type="checkbox" name="onboot" value="1">
                                        <span>Start VM automatically on boot</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CPU Section -->
                    <div class="vm-section" onclick="toggleSection(this)">
                        <div class="vm-section-header">
                            <i class="fas fa-chevron-right section-arrow"></i>
                            <i class="fas fa-microchip section-icon"></i>
                            <span class="section-title">CPU</span>
                            <span class="section-value" id="cpuValue">2 sockets, 2 cores</span>
                        </div>
                        <div class="vm-section-body">
                            <div class="section-content">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Sockets *</label>
                                        <input type="number" name="sockets" class="form-control" 
                                               value="1" min="1" max="4" required onchange="updateCPUValue()">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Cores per socket *</label>
                                        <input type="number" name="cores" class="form-control" 
                                               value="2" min="1" max="128" required onchange="updateCPUValue()">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">CPU Type</label>
                                    <select name="cpu" class="form-control">
                                        <option value="host">host (Use host CPU)</option>
                                        <option value="kvm64">kvm64 (Default)</option>
                                        <option value="qemu64">qemu64</option>
                                        <option value="Nehalem">Intel Nehalem</option>
                                        <option value="SandyBridge">Intel SandyBridge</option>
                                        <option value="IvyBridge">Intel IvyBridge</option>
                                        <option value="Haswell">Intel Haswell</option>
                                        <option value="Broadwell">Intel Broadwell</option>
                                        <option value="Skylake-Client">Intel Skylake</option>
                                        <option value="EPYC">AMD EPYC</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Memory Section -->
                    <div class="vm-section" onclick="toggleSection(this)">
                        <div class="vm-section-header">
                            <i class="fas fa-chevron-right section-arrow"></i>
                            <i class="fas fa-memory section-icon"></i>
                            <span class="section-title">Memory</span>
                            <span class="section-value" id="memoryValue">2048 MB</span>
                        </div>
                        <div class="vm-section-body">
                            <div class="section-content">
                                <div class="form-group">
                                    <label class="form-label">Memory (MB) *</label>
                                    <input type="number" name="memory" class="form-control" 
                                           value="2048" min="512" step="512" required onchange="updateMemoryValue()">
                                    <small class="text-muted">RAM allocated to the virtual machine</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hard Disk Section -->
                    <div class="vm-section expanded" onclick="toggleSection(this)">
                        <div class="vm-section-header">
                            <i class="fas fa-chevron-down section-arrow"></i>
                            <i class="fas fa-hdd section-icon"></i>
                            <span class="section-title">Hard disk 1</span>
                            <span class="section-value" id="diskValue">32 GB</span>
                        </div>
                        <div class="vm-section-body" style="display: block;">
                            <div class="section-content">
                                <div class="form-group">
                                    <label class="form-label">Storage *</label>
                                    <select name="storage" class="form-control" required onchange="updateDiskValue()">
                                        <option value="">Select Storage</option>
                                        <?php foreach ($allStorage as $storage): ?>
                                            <?php if (strpos($storage['content'] ?? '', 'images') !== false): ?>
                                                <option value="<?php echo htmlspecialchars($storage['storage']); ?>">
                                                    <?php echo htmlspecialchars($storage['storage']); ?> 
                                                    (<?php echo htmlspecialchars($storage['type']); ?>)
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Disk Size (GB) *</label>
                                    <input type="number" name="disksize" class="form-control" 
                                           value="32" min="1" max="9999" required onchange="updateDiskValue()">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Disk Controller</label>
                                    <select name="scsihw" class="form-control">
                                        <option value="virtio-scsi-pci">VirtIO SCSI</option>
                                        <option value="virtio-scsi-single">VirtIO SCSI Single</option>
                                        <option value="lsi">LSI 53C895A</option>
                                        <option value="megasas">MegaRAID SAS 8708EM2</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Cache Mode</label>
                                    <select name="cache" class="form-control">
                                        <option value="">Default (No cache)</option>
                                        <option value="writethrough">Write through</option>
                                        <option value="writeback">Write back</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Network Adapter Section -->
                    <div class="vm-section" onclick="toggleSection(this)">
                        <div class="vm-section-header">
                            <i class="fas fa-chevron-right section-arrow"></i>
                            <i class="fas fa-network-wired section-icon"></i>
                            <span class="section-title">Network Adapter 1</span>
                            <span class="section-value" id="networkValue">vmbr0, VirtIO</span>
                        </div>
                        <div class="vm-section-body">
                            <div class="section-content">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Bridge</label>
                                        <input type="text" name="bridge" class="form-control" 
                                               placeholder="vmbr0" value="vmbr0" onchange="updateNetworkValue()">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Model</label>
                                        <select name="model" class="form-control" onchange="updateNetworkValue()">
                                            <option value="virtio">VirtIO (paravirtualized)</option>
                                            <option value="e1000">Intel E1000</option>
                                            <option value="rtl8139">Realtek RTL8139</option>
                                            <option value="vmxnet3">VMware vmxnet3</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">VLAN Tag</label>
                                    <input type="number" name="vlan" class="form-control" 
                                           placeholder="Optional">
                                    <small class="text-muted">Leave empty for no VLAN tagging</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-checkbox">
                                        <input type="checkbox" checked>
                                        <span>Connect network adapter at power on</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CD/DVD Drive Section -->
                    <div class="vm-section" onclick="toggleSection(this)">
                        <div class="vm-section-header">
                            <i class="fas fa-chevron-right section-arrow"></i>
                            <i class="fas fa-compact-disc section-icon"></i>
                            <span class="section-title">CD/DVD Drive 1</span>
                            <span class="section-value" id="cdValue">No media</span>
                        </div>
                        <div class="vm-section-body">
                            <div class="section-content">
                                <div class="form-group">
                                    <label class="form-label">ISO Image</label>
                                    <select name="iso" class="form-control" id="isoSelect" onchange="updateCDValue()">
                                        <option value="">No media</option>
                                        <option value="none">Do not use any media</option>
                                    </select>
                                    <small class="text-muted">Select ISO image for OS installation</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-checkbox">
                                        <input type="checkbox" checked>
                                        <span>Connect CD/DVD at power on</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Guest OS Section -->
                    <div class="vm-section" onclick="toggleSection(this)">
                        <div class="vm-section-header">
                            <i class="fas fa-chevron-right section-arrow"></i>
                            <i class="fas fa-desktop section-icon"></i>
                            <span class="section-title">Guest OS</span>
                            <span class="section-value" id="osValue">Linux 5.x - 2.6 Kernel</span>
                        </div>
                        <div class="vm-section-body">
                            <div class="section-content">
                                <div class="form-group">
                                    <label class="form-label">Guest OS Type</label>
                                    <select name="ostype" class="form-control" onchange="updateOSValue()">
                                        <option value="l26">Linux 5.x - 2.6 Kernel</option>
                                        <option value="l24">Linux 2.4 Kernel</option>
                                        <option value="win11">Windows 11/2022</option>
                                        <option value="win10">Windows 10/2016/2019</option>
                                        <option value="win8">Windows 8.x/2012/2012r2</option>
                                        <option value="win7">Windows 7/2008r2</option>
                                        <option value="solaris">Solaris Kernel</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <small class="text-muted">Identifies the guest OS to optimize performance</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- VM Options Section -->
                    <div class="vm-section" onclick="toggleSection(this)">
                        <div class="vm-section-header">
                            <i class="fas fa-chevron-right section-arrow"></i>
                            <i class="fas fa-cogs section-icon"></i>
                            <span class="section-title">VM Options</span>
                            <span class="section-value">Boot order, BIOS settings</span>
                        </div>
                        <div class="vm-section-body">
                            <div class="section-content">
                                <div class="form-group">
                                    <label class="form-label">Boot Order</label>
                                    <input type="text" name="boot" class="form-control" 
                                           value="order=scsi0;ide2;net0" 
                                           placeholder="order=scsi0;ide2;net0">
                                    <small class="text-muted">Boot device priority</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">BIOS</label>
                                    <select name="bios" class="form-control">
                                        <option value="seabios">SeaBIOS (Legacy BIOS)</option>
                                        <option value="ovmf">OVMF (UEFI)</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-checkbox">
                                        <input type="checkbox" name="kvm" value="1" checked>
                                        <span>Enable KVM hardware virtualization</span>
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-checkbox">
                                        <input type="checkbox" name="acpi" value="1" checked>
                                        <span>Enable ACPI support</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeCreateVMModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="submit" form="createVMForm" class="btn btn-success">
                <i class="fas fa-check"></i> Finish
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

.modal-xl {
    max-width: 900px;
}

.vm-creation-info {
    margin-bottom: 1rem;
}

.vm-settings-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.vm-section {
    background: white;
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: all 0.2s ease;
}

.vm-section:hover {
    border-color: var(--blue-400);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.vm-section.expanded {
    border-color: var(--blue-500);
}

.vm-section-header {
    display: flex;
    align-items: center;
    padding: 0.875rem 1rem;
    cursor: pointer;
    user-select: none;
    background: var(--gray-50);
    transition: background 0.2s ease;
}

.vm-section-header:hover {
    background: var(--gray-100);
}

.vm-section.expanded .vm-section-header {
    background: linear-gradient(90deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.03) 100%);
    border-bottom: 1px solid var(--gray-200);
}

.section-arrow {
    color: var(--gray-500);
    font-size: 0.75rem;
    margin-right: 0.75rem;
    transition: transform 0.2s ease;
}

.vm-section.expanded .section-arrow {
    transform: rotate(90deg);
    color: var(--blue-500);
}

.section-icon {
    color: var(--gray-600);
    font-size: 1rem;
    margin-right: 0.75rem;
    width: 20px;
    text-align: center;
}

.vm-section.expanded .section-icon {
    color: var(--blue-500);
}

.section-title {
    font-weight: 600;
    color: var(--gray-800);
    flex: 0 0 180px;
}

.section-value {
    color: var(--gray-600);
    font-size: 0.875rem;
    flex: 1;
    text-align: right;
    padding-right: 0.5rem;
}

.vm-section-body {
    display: none;
    animation: slideDown 0.2s ease;
}

.vm-section.expanded .vm-section-body {
    display: block;
}

.section-content {
    padding: 1.25rem;
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
    }
    to {
        opacity: 1;
        max-height: 500px;
    }
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    color: var(--gray-700);
}

.form-checkbox input[type="checkbox"] {
    width: 1.25rem;
    height: 1.25rem;
    cursor: pointer;
}

@media (max-width: 1024px) {
    .form-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Toggle section expand/collapse
function toggleSection(element) {
    element.classList.toggle('expanded');
}

// Update section values dynamically
function updateBasicValue() {
    const node = document.querySelector('[name="node"]').value;
    const vmid = document.querySelector('[name="vmid"]').value;
    const name = document.querySelector('[name="name"]').value;
    
    let value = '';
    if (name) value += name;
    if (vmid) value += (value ? ' (' + vmid + ')' : vmid);
    if (node) value += (value ? ' on ' + node : node);
    
    document.getElementById('basicValue').textContent = value || 'Not configured';
}

function updateCPUValue() {
    const sockets = document.querySelector('[name="sockets"]').value;
    const cores = document.querySelector('[name="cores"]').value;
    document.getElementById('cpuValue').textContent = `${sockets} socket${sockets > 1 ? 's' : ''}, ${cores} core${cores > 1 ? 's' : ''} per socket`;
}

function updateMemoryValue() {
    const memory = document.querySelector('[name="memory"]').value;
    const gb = (memory / 1024).toFixed(1);
    document.getElementById('memoryValue').textContent = `${memory} MB (${gb} GB)`;
}

function updateDiskValue() {
    const disksize = document.querySelector('[name="disksize"]').value;
    const storage = document.querySelector('[name="storage"]').value;
    document.getElementById('diskValue').textContent = `${disksize} GB${storage ? ' on ' + storage : ''}`;
}

function updateNetworkValue() {
    const bridge = document.querySelector('[name="bridge"]').value;
    const model = document.querySelector('[name="model"]').value;
    const modelText = document.querySelector('[name="model"] option:checked').text;
    document.getElementById('networkValue').textContent = `${bridge}, ${modelText}`;
}

function updateCDValue() {
    const iso = document.getElementById('isoSelect');
    const selectedText = iso.options[iso.selectedIndex].text;
    document.getElementById('cdValue').textContent = selectedText;
}

function updateOSValue() {
    const ostype = document.querySelector('[name="ostype"]');
    const selectedText = ostype.options[ostype.selectedIndex].text;
    document.getElementById('osValue').textContent = selectedText;
}

function openCreateVMModal() {
    document.getElementById('createVMModal').classList.add('active');
    loadISOList();
    // Expand first section by default
    const firstSection = document.querySelector('.vm-section');
    if (firstSection && !firstSection.classList.contains('expanded')) {
        firstSection.classList.add('expanded');
    }
}

function closeCreateVMModal() {
    document.getElementById('createVMModal').classList.remove('active');
    document.getElementById('createVMForm').reset();
    // Reset all expanded sections
    document.querySelectorAll('.vm-section.expanded').forEach(section => {
        if (section.querySelector('.section-title').textContent !== 'Hard disk 1') {
            section.classList.remove('expanded');
        }
    });
}

async function loadISOList() {
    // Load ISO images from storage
    const nodeSelect = document.querySelector('[name="node"]');
    const isoSelect = document.getElementById('isoSelect');
    
    if (!nodeSelect.value) {
        nodeSelect.addEventListener('change', async function() {
            await fetchISOs(this.value);
        });
        return;
    }
    
    await fetchISOs(nodeSelect.value);
}

async function fetchISOs(node) {
    const isoSelect = document.getElementById('isoSelect');
    
    try {
        // Get all storage
        const response = await fetch('/api/v1/storage');
        const result = await response.json();
        
        if (result.success && result.data) {
            // Keep default options
            const defaultOptions = '<option value="">None</option><option value="none">Do not use any media</option>';
            let isoOptions = '';
            
            for (const storage of result.data) {
                if (storage.content && storage.content.includes('iso')) {
                    // Fetch ISOs from this storage
                    try {
                        const contentResponse = await fetch(`/api/v1/nodes/${node}/storage/${storage.storage}/content`);
                        const contentResult = await contentResponse.json();
                        
                        if (contentResult.success && contentResult.data) {
                            for (const item of contentResult.data) {
                                if (item.content === 'iso') {
                                    isoOptions += `<option value="${storage.storage}:iso/${item.volid.split('/').pop()}">${item.volid.split('/').pop()}</option>`;
                                }
                            }
                        }
                    } catch (e) {
                        console.log('Error fetching content from', storage.storage);
                    }
                }
            }
            
            isoSelect.innerHTML = defaultOptions + isoOptions;
        }
    } catch (error) {
        console.error('Error loading ISOs:', error);
    }
}

async function submitCreateVM(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const data = {};
    
    // Build VM configuration
    for (const [key, value] of formData.entries()) {
        if (value !== '' && value !== null) {
            if (key === 'onboot' || key === 'kvm' || key === 'acpi') {
                data[key] = 1;
            } else {
                data[key] = value;
            }
        }
    }
    
    // Convert numeric fields
    if (data.vmid) data.vmid = parseInt(data.vmid);
    if (data.sockets) data.sockets = parseInt(data.sockets);
    if (data.cores) data.cores = parseInt(data.cores);
    if (data.memory) data.memory = parseInt(data.memory);
    if (data.disksize) data.disksize = parseInt(data.disksize);
    if (data.vlan) data.vlan = parseInt(data.vlan);
    
    // Build network config
    const bridge = data.bridge || 'vmbr0';
    const model = data.model || 'virtio';
    const vlan = data.vlan ? `,tag=${data.vlan}` : '';
    data.net0 = `${model},bridge=${bridge}${vlan}`;
    
    // Build disk config
    if (data.storage && data.disksize) {
        data.scsi0 = `${data.storage}:${data.disksize}`;
        if (data.cache) {
            data.scsi0 += `,cache=${data.cache}`;
        }
    }
    
    // Set ISO if selected
    if (data.iso && data.iso !== 'none' && data.iso !== '') {
        data.ide2 = `${data.iso},media=cdrom`;
    }
    
    // Clean up form-specific fields
    delete data.bridge;
    delete data.model;
    delete data.vlan;
    delete data.disksize;
    delete data.iso;
    delete data.cache;
    
    const node = data.node;
    delete data.node;
    
    try {
        const response = await fetch(`/api/v1/nodes/${node}/qemu`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Virtual machine created successfully!');
            closeCreateVMModal();
            location.reload();
        } else {
            alert('Error creating VM: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
    
    return false;
}

async function startVM(node, vmid) {
    if (!confirm(`Start VM ${vmid}?`)) return;
    
    try {
        const response = await fetch(`/api/v1/nodes/${node}/qemu/${vmid}/status/start`, {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('VM started successfully!');
            location.reload();
        } else {
            alert('Error starting VM: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function stopVM(node, vmid) {
    if (!confirm(`Stop VM ${vmid}?`)) return;
    
    try {
        const response = await fetch(`/api/v1/nodes/${node}/qemu/${vmid}/status/stop`, {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('VM stopped successfully!');
            location.reload();
        } else {
            alert('Error stopping VM: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function deleteVM(node, vmid) {
    if (!confirm(`Are you sure you want to delete VM ${vmid}? This action cannot be undone!`)) return;
    
    try {
        const response = await fetch(`/api/v1/nodes/${node}/qemu/${vmid}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('VM deleted successfully!');
            location.reload();
        } else {
            alert('Error deleting VM: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

function viewVM(node, vmid) {
    window.location.href = `/nodes/${node}/qemu/${vmid}`;
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

// Auto-load ISOs when node changes
document.addEventListener('DOMContentLoaded', function() {
    const nodeSelect = document.querySelector('[name="node"]');
    if (nodeSelect) {
        nodeSelect.addEventListener('change', function() {
            fetchISOs(this.value);
        });
    }
});
</script>
