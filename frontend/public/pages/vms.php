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
        <div style="padding: 1.5rem 2rem;">
            <h1 style="font-size: 2rem; font-weight: 700; color: #1f2937; margin: 0 0 1rem 0;">
                <i class="fas fa-desktop" style="margin-right: 0.75rem; color: #3b82f6;"></i> Virtual Machines
            </h1>
            
            <!-- Tab Navigation -->
            <div style="display: flex; gap: 0; border-bottom: 2px solid #e5e7eb; margin-bottom: 2rem;">
                <button class="storage-tab-btn active" onclick="switchVMTab('overview')" style="padding: 0.75rem 1.5rem; border: none; background: none; cursor: pointer; font-weight: 500; color: #6b7280; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: all 0.2s;" onmouseover="this.style.color='#1f2937'" onmouseout="this.style.color='#6b7280'">
                    <i class="fas fa-chart-line" style="margin-right: 0.5rem;"></i> Overview
                </button>
                <button class="storage-tab-btn" onclick="switchVMTab('vms-list')" style="padding: 0.75rem 1.5rem; border: none; background: none; cursor: pointer; font-weight: 500; color: #6b7280; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: all 0.2s;" onmouseover="this.style.color='#1f2937'" onmouseout="this.style.color='#6b7280'">
                    <i class="fas fa-list" style="margin-right: 0.5rem;"></i> Virtual Machines
                </button>
                <button class="storage-tab-btn" onclick="switchVMTab('create-vm')" style="padding: 0.75rem 1.5rem; border: none; background: none; cursor: pointer; font-weight: 500; color: #6b7280; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: all 0.2s;" onmouseover="this.style.color='#1f2937'" onmouseout="this.style.color='#6b7280'">
                    <i class="fas fa-plus" style="margin-right: 0.5rem;"></i> Create VM
                </button>
            </div>
            
            <!-- Overview Tab -->
            <div id="overview-tab" class="vm-tab-content" style="display: block;">
        
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
            </div> <!-- End Overview Tab -->
        
        <!-- VMs List Tab -->
        <div id="vms-list-tab" class="vm-tab-content" style="display: none;">
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Virtual Machines</h2>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <button class="btn btn-secondary" onclick="refreshData()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
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
                                            <span class="badge badge-success" style="display: inline-block; padding: 0.375rem 0.75rem; border-radius: 0.25rem; background-color: #10b981; color: #ffffff; font-size: 0.875rem; font-weight: 500; white-space: nowrap;">
                                                <i class="fas fa-circle" style="margin-right: 0.375rem;"></i> Running
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary" style="display: inline-block; padding: 0.375rem 0.75rem; border-radius: 0.25rem; background-color: #6b7280; color: #ffffff; font-size: 0.875rem; font-weight: 500; white-space: nowrap;">
                                                <i class="fas fa-circle" style="margin-right: 0.375rem;"></i> Stopped
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($vm['node'] ?? 'N/A'); ?></td>
                                    <td><?php echo $vm['cpus'] ?? 0; ?> cores</td>
                                    <td><?php echo round(($vm['maxmem'] ?? 0) / (1024**3), 1); ?> GB</td>
                                    <td><?php echo $uptimeStr; ?></td>
                                    <td>
                                        <?php if ($status === 'running'): ?>
                                            <button class="btn btn-sm btn-warning" onclick="stopVM('<?php echo $vm['node']; ?>', <?php echo $vm['vmid']; ?>)" title="Stop VM">
                                                <i class="fas fa-stop"></i>
                                            </button>
                                            <button class="btn btn-sm btn-secondary" onclick="restartVM('<?php echo $vm['node']; ?>', <?php echo $vm['vmid']; ?>)" title="Restart VM">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success" onclick="startVM('<?php echo $vm['node']; ?>', <?php echo $vm['vmid']; ?>)" title="Start VM">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-info" onclick="openConsoleVM('<?php echo $vm['node']; ?>', <?php echo $vm['vmid']; ?>)" title="Open Console">
                                            <i class="fas fa-desktop"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary" onclick="viewVM('<?php echo $vm['node']; ?>', <?php echo $vm['vmid']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteVM('<?php echo $vm['node']; ?>', <?php echo $vm['vmid']; ?>)" title="Delete VM">
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
            </div> <!-- End VMs List Tab -->
        
        <!-- Create VM Tab -->
        <div id="create-vm-tab" class="vm-tab-content" style="display: none;">
        <div style="max-width: 1200px;">
            <div class="vmware-table-header" style="margin-bottom: 2rem;">
                <h2 class="vmware-section-title">
                    <i class="fas fa-plus"></i> Create New Virtual Machine
                </h2>
            </div>
            
            <!-- Create VM Form -->
            <div id="createVMFormContainer" style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 2rem;">
                
                <!-- Basic Settings Section -->
                <div style="margin-bottom: 2rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 1.5rem;">
                    <h3 style="font-size: 1.125rem; font-weight: 700; color: #1f2937; margin: 0 0 1.5rem 0;">
                        <i class="fas fa-cog" style="margin-right: 0.5rem; color: #3b82f6;"></i> Basic Settings
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937;">VM Name <span style="color: #ef4444;">*</span></label>
                            <input type="text" id="vmName" placeholder="e.g., ubuntu-vm-01" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937;">Node <span style="color: #ef4444;">*</span></label>
                            <select id="vmNode" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                                <option value="auto" selected>Auto (Best Available)</option>
                                <?php foreach ($nodes as $node): ?>
                                    <option value="<?php echo $node['node']; ?>"><?php echo $node['node']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Hardware Section -->
                <div style="margin-bottom: 2rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 1.5rem;">
                    <h3 style="font-size: 1.125rem; font-weight: 700; color: #1f2937; margin: 0 0 1.5rem 0;">
                        <i class="fas fa-microchip" style="margin-right: 0.5rem; color: #8b5cf6;"></i> Hardware
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937;">vCPU Count</label>
                            <input type="number" id="vmCPU" value="2" min="1" max="128" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937;">Memory (GB)</label>
                            <input type="number" id="vmMemory" value="4" min="0.5" max="1024" step="0.5" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                        </div>
                    </div>
                </div>
                
                <!-- Storage Section -->
                <div style="margin-bottom: 2rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 1.5rem;">
                    <h3 style="font-size: 1.125rem; font-weight: 700; color: #1f2937; margin: 0 0 1.5rem 0;">
                        <i class="fas fa-hdd" style="margin-right: 0.5rem; color: #f59e0b;"></i> Storage
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937;">Storage <span style="color: #ef4444;">*</span></label>
                            <select id="vmStorage" onchange="updateDiskFormat()" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                                <option value="">Select Storage</option>
                                <?php 
                                // Filter storages to show only those that support VM images (have 'images' in content)
                                $vmStorages = array_filter($allStorage, function($storage) {
                                    return isset($storage['content']) && strpos($storage['content'], 'images') !== false;
                                });
                                foreach ($vmStorages as $storage): 
                                    $storageId = $storage['storage'] ?? '';
                                    $type = $storage['type'] ?? '';
                                    $freeLabel = '';
                                    if (isset($storage['available_gb']) && is_numeric($storage['available_gb'])) {
                                        $freeLabel = ', ' . round((float)$storage['available_gb'], 1) . 'GB free';
                                    }
                                ?>
                                    <option value="<?php echo htmlspecialchars($storageId); ?>" data-type="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($storageId); ?> (<?php echo htmlspecialchars($type); ?><?php echo $freeLabel; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937;">Boot Disk Size (GB)</label>
                            <input type="number" id="vmDisk" value="50" min="10" max="2000" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                        </div>
                        <input type="hidden" id="vmDiskFormat" value="qcow2">
                    </div>
                    
                    <!-- Additional Disks -->
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <label style="font-weight: 600; color: #1f2937;">Additional Disks</label>
                            <button type="button" class="vmware-btn vmware-btn-sm vmware-btn-secondary" onclick="addExtraDisk()">
                                <i class="fas fa-plus"></i> Add Disk
                            </button>
                        </div>
                        <div id="extraDisksContainer"></div>
                    </div>
                </div>
                
                <!-- Network Section -->
                <div style="margin-bottom: 2rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 1.5rem;">
                    <h3 style="font-size: 1.125rem; font-weight: 700; color: #1f2937; margin: 0 0 1.5rem 0;">
                        <i class="fas fa-network-wired" style="margin-right: 0.5rem; color: #10b981;"></i> Network
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937;">Network Model</label>
                            <select id="vmNetModel" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                                <option value="virtio" selected>VirtIO</option>
                                <option value="e1000">E1000</option>
                                <option value="rtl8139">RTL8139</option>
                            </select>
                        </div>
                        <input type="hidden" id="vmBridge" value="vmbr0">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937;">VLAN ID</label>
                            <input type="number" id="vmVLAN" min="0" max="4094" placeholder="Leave empty for no VLAN" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                        </div>
                    </div>
                    
                    <!-- Additional Network Interfaces -->
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <label style="font-weight: 600; color: #1f2937;">Additional Network Interfaces</label>
                            <button type="button" class="vmware-btn vmware-btn-sm vmware-btn-secondary" onclick="addExtraNetwork()">
                                <i class="fas fa-plus"></i> Add Network
                            </button>
                        </div>
                        <div id="extraNetworksContainer"></div>
                    </div>
                </div>
                
                <!-- OS Settings Section -->
                <div style="margin-bottom: 2rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 1.5rem;">
                    <h3 style="font-size: 1.125rem; font-weight: 700; color: #1f2937; margin: 0 0 1.5rem 0;">
                        <i class="fas fa-compact-disc" style="margin-right: 0.5rem; color: #ec4899;"></i> OS & Media
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937;">OS Type</label>
                            <select id="vmOSType" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                                <option value="l26">Linux 6.x/5.x (l26)</option>
                                <option value="l24">Linux 2.4 (l24)</option>
                                <option value="solaris">Solaris</option>
                                <option value="wxp">Windows XP</option>
                                <option value="w2k">Windows 2000</option>
                                <option value="w2k3">Windows 2003</option>
                                <option value="w2k8">Windows 2008</option>
                                <option value="wvista">Windows Vista</option>
                                <option value="win7">Windows 7</option>
                                <option value="win8">Windows 8</option>
                                <option value="win10">Windows 10</option>
                                <option value="win11">Windows 11</option>
                                <option value="win2012">Windows 2012</option>
                                <option value="win2012r2">Windows 2012 R2</option>
                                <option value="win2016">Windows 2016</option>
                                <option value="win2019">Windows 2019</option>
                                <option value="win2022">Windows 2022</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937;">ISO Image (Optional)</label>
                            <select id="vmISO" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;" onchange="updateISOPath()">
                                <option value="">None - No ISO</option>
                                <option value="custom">Custom Path</option>
                            </select>
                        </div>
                        <div id="vmISOPathContainer" style="display: none;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937;">ISO Path</label>
                            <input type="text" id="vmISOPath" placeholder="e.g., local:iso/ubuntu.iso" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                        </div>
                    </div>
                </div>
                
                <!-- Advanced Section -->
                <div style="margin-bottom: 2rem;">
                    <h3 style="font-size: 1.125rem; font-weight: 700; color: #1f2937; margin: 0 0 1.5rem 0;">
                        <i class="fas fa-sliders-h" style="margin-right: 0.5rem; color: #6366f1;"></i> Advanced
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937;">Description</label>
                            <textarea id="vmDescription" placeholder="VM description" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-family: monospace; resize: vertical; min-height: 100px;"></textarea>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="vmAutostart" style="width: 1rem; height: 1rem;">
                                <span style="font-weight: 500; color: #1f2937;">Autostart</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="vmKVM" style="width: 1rem; height: 1rem;">
                                <span style="font-weight: 500; color: #1f2937;">KVM Enabled</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="vmHugePages" style="width: 1rem; height: 1rem;">
                                <span style="font-weight: 500; color: #1f2937;">HugePages</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div style="display: flex; gap: 1rem; justify-content: flex-end; border-top: 1px solid #e5e7eb; padding-top: 1.5rem;">
                    <button class="vmware-btn vmware-btn-secondary" onclick="switchVMTab('vms-list')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="vmware-btn vmware-btn-primary" onclick="createVMFromForm()">
                        <i class="fas fa-check"></i> Create VM
                    </button>
                </div>
            </div>
        </div>
            </div> <!-- End Create VM Tab -->
        </div>
    </main>
</div>

        <!-- Console Modal -->
        <div id="consoleModal" class="modal">
            <div class="floating-window" id="consoleWindow">
                <div class="modal-header" id="consoleWindowHeader" style="display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #e5e7eb; padding:0.75rem 1rem;">
                    <h3 class="modal-title" style="margin:0; font-weight:700; font-size:1rem;">
                        <i class="fas fa-desktop"></i> Console
                    </h3>
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <button class="btn btn-secondary" id="consoleModalPopout" title="Open in new tab" style="padding:0.25rem 0.5rem; font-size:0.8rem;">
                            <i class="fas fa-external-link-alt"></i>
                        </button>
                        <button class="btn btn-secondary" id="consoleModalFullscreenToggle" title="Toggle Fullscreen" style="padding:0.25rem 0.5rem; font-size:0.8rem;">
                            <i class="fas fa-expand"></i>
                        </button>
                        <button class="modal-close" onclick="closeConsoleModal()">&times;</button>
                    </div>
                </div>
                <div style="flex:1;">
                    <iframe id="consoleFrame" src="" allow="fullscreen" allowfullscreen style="border:0; width:100%; height:100%;"></iframe>
                </div>
            </div>
            <style>
                /* Floating console window */
                #consoleModal.modal.active { display:block; background: transparent !important; }
                #consoleModal { position: fixed; inset: 0; }
                #consoleModal .floating-window {
                    position: fixed; left: 10vw; top: 10vh;
                    width: 80vw; height: 70vh; max-width: 95vw; max-height: 90vh;
                    min-width: 600px; min-height: 400px;
                    background: #0b0f1a; color: #e5e7eb;
                    border: 1px solid #1f2a44; border-radius: 10px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.45), 0 0 0 1px rgba(255,255,255,0.04) inset;
                    display:flex; flex-direction:column; overflow:hidden;
                    resize: both; pointer-events: auto; z-index: 1000;
                }
                #consoleModal .floating-window.fullscreen {
                    left: 0 !important; top: 0 !important;
                    width: 100vw !important; height: 100vh !important;
                    max-width: 100vw !important; max-height: 100vh !important;
                    border-radius: 0; resize: none;
                }
                #consoleModal .modal-header {
                    cursor: move; user-select: none;
                    background: linear-gradient(180deg, #0b1220, #0d1528);
                }
            </style>
        </div>

<!-- Create VM Modal (Hidden - keep for backward compatibility) -->
<div id="createVMModal" class="modal" style="display: none;">
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
    // Start immediately without any confirmation or alerts

    const findRow = () => {
        const rows = Array.from(document.querySelectorAll('table.data-table tbody tr'));
        return rows.find(r => (r.querySelector('td strong')?.textContent || '').trim() === String(vmid));
    };

    const setRowRunning = () => {
        const row = findRow();
        if (!row) return;
        // Update status cell (index 2)
        const statusCell = row.cells[2];
        statusCell.innerHTML = `<span class="badge badge-success" style="display: inline-block; padding: 0.375rem 0.75rem; border-radius: 0.25rem; background-color: #10b981; color: #ffffff; font-size: 0.875rem; font-weight: 500; white-space: nowrap;"><i class=\"fas fa-circle\" style=\"margin-right: 0.375rem;\"></i> Running</span>`;
        // Update actions (index 7): show Stop instead of Start
        const actions = row.cells[7];
        const btns = actions.querySelectorAll('button');
        if (btns.length > 0) {
            // Replace first button with Stop and ensure Restart exists
            btns[0].outerHTML = `<button class=\"btn btn-sm btn-warning\" onclick=\"stopVM('${node}', ${vmid})\" title=\"Stop VM\"><i class=\"fas fa-stop\"></i></button>`;
            // Add/ensure restart button next to stop (if not already there)
            const hasRestart = Array.from(actions.querySelectorAll('button')).some(b => b.title?.toLowerCase().includes('restart'));
            if (!hasRestart) {
                const restartBtn = document.createElement('button');
                restartBtn.className = 'btn btn-sm btn-secondary';
                restartBtn.title = 'Restart VM';
                restartBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
                restartBtn.onclick = () => restartVM(node, vmid);
                // insert after Stop button
                const firstBtn = actions.querySelector('button');
                if (firstBtn && firstBtn.nextSibling) actions.insertBefore(restartBtn, firstBtn.nextSibling);
                else actions.appendChild(restartBtn);
            }
        }
    };

    const setRowStarting = () => {
        const row = findRow();
        if (!row) return;
        const statusCell = row.cells[2];
        statusCell.innerHTML = `<span class="badge" style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.375rem 0.75rem; border-radius:0.25rem; background:#E5E7EB; color:#111827; font-size:0.875rem; font-weight:500;"><i class='fas fa-spinner fa-spin'></i> Starting...</span>`;
        // disable action buttons while starting
        const actions = row.cells[7];
        actions.querySelectorAll('button').forEach(b => b.disabled = true);
    };

    const setRowFailed = (msg) => {
        const row = findRow();
        if (!row) return;
        const statusCell = row.cells[2];
        statusCell.innerHTML = `<span class="badge" title="${(msg||'').replace(/"/g,'&quot;')}" style="display:inline-block; padding:0.375rem 0.75rem; border-radius:0.25rem; background:#FECACA; color:#991B1B; font-size:0.875rem; font-weight:600;">Failed</span>`;
        // re-enable buttons
        const actions = row.cells[7];
        actions.querySelectorAll('button').forEach(b => b.disabled = false);
        // ensure Start button is present
        const btns = actions.querySelectorAll('button');
        if (btns.length > 0) {
            btns[0].outerHTML = `<button class="btn btn-sm btn-success" onclick="startVM('${node}', ${vmid})" title="Start VM"><i class="fas fa-play"></i></button>`;
        }
    };

    const pollTask = async (upid, maxMs = 30000, interval = 1000) => {
        const start = Date.now();
        while (Date.now() - start < maxMs) {
            try {
                const res = await fetch(`/api/v1/nodes/${node}/tasks/${encodeURIComponent(upid)}/status`);
                const data = await res.json();
                if (data.success) {
                    const status = data.data?.status; // running | stopped
                    const exitstatus = data.data?.exitstatus; // OK | error msg
                    if (status === 'stopped') return exitstatus || 'unknown';
                }
            } catch (e) {}
            await new Promise(r => setTimeout(r, interval));
        }
        return null; // timeout
    };

    const pollVmRunning = async (maxMs = 15000, interval = 1000) => {
        const start = Date.now();
        while (Date.now() - start < maxMs) {
            try {
                const res = await fetch(`/api/v1/nodes/${node}/qemu/${vmid}/status/current`);
                const data = await res.json();
                if (data.success && (data.data?.status || '').toLowerCase() === 'running') return true;
            } catch (e) {}
            await new Promise(r => setTimeout(r, interval));
        }
        return false;
    };

    try {
        setRowStarting();
        const response = await fetch(`/api/v1/nodes/${node}/qemu/${vmid}/status/start`, { method: 'POST' });
        const result = await response.json();

        if (!result.success) {
            setRowFailed(result.error || 'Unknown error');
            return;
        }

        // Extract UPID from result.data
        const upid = (result.data || '').toString();
        const upidMatch = upid.startsWith('UPID:') ? upid : null;

        let exit = 'unknown';
        if (upidMatch) {
            const ex = await pollTask(upidMatch);
            if (ex !== null) {
                exit = ex;
            }
        }

        const running = await pollVmRunning();
        if (running) {
            setRowRunning();
            return;
        } else {
            // If task exitstatus indicates error, fetch log and show
            if (upidMatch && exit && exit !== 'OK') {
                try {
                    const logRes = await fetch(`/api/v1/nodes/${node}/tasks/${encodeURIComponent(upidMatch)}/log`);
                    const logData = await logRes.json();
                    const lines = Array.isArray(logData.data) ? logData.data.map(l => l.t || '').filter(Boolean).slice(-10).join('\n') : '';
                    const msg = (lines || exit);
                    // Special handling: KVM not available or CPU model host requires KVM
                    if (/KVM virtualisation configured, but not available/i.test(msg) || /CPU model 'host' requires KVM|HVF/i.test(msg)) {
                        // try to auto-fix: set kvm=0 and cpu=kvm64
                        try {
                            await fetch(`/api/v1/nodes/${node}/qemu/${vmid}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ kvm: 0, cpu: 'kvm64' }) });
                            const retry = await fetch(`/api/v1/nodes/${node}/qemu/${vmid}/status/start`, { method: 'POST' });
                            const retryJson = await retry.json();
                            if (retryJson.success) {
                                const ok2 = await pollVmRunning(20000);
                                if (ok2) { setRowRunning(); return; }
                            }
                        } catch (e) {}
                    }
                    setRowFailed(msg);
                    return;
                } catch (e) {}
            }
            setRowFailed('Start pending or no status update');
        }
    } catch (error) {
        setRowFailed(error.message || 'Error');
    }
}

async function stopVM(node, vmid) {
    // Update row to show immediate feedback and avoid page reload
    const findRow = () => {
        const rows = Array.from(document.querySelectorAll('table.data-table tbody tr'));
        return rows.find(r => (r.querySelector('td strong')?.textContent || '').trim() === String(vmid));
    };
    const setRowStopping = () => {
        const row = findRow();
        if (!row) return;
        const statusCell = row.cells[2];
        statusCell.innerHTML = `<span class=\"badge\" style=\"display:inline-flex; align-items:center; gap:0.5rem; padding:0.375rem 0.75rem; border-radius:0.25rem; background:#FDE68A; color:#7C2D12; font-size:0.875rem; font-weight:600;\">Shutdown</span>`;
        const actions = row.cells[7];
        actions.querySelectorAll('button').forEach(b => b.disabled = true);
    };
    const setRowStopped = () => {
        const row = findRow();
        if (!row) return;
        const statusCell = row.cells[2];
        statusCell.innerHTML = `<span class=\"badge badge-secondary\" style=\"display: inline-block; padding: 0.375rem 0.75rem; border-radius: 0.25rem; background-color: #6b7280; color: #ffffff; font-size: 0.875rem; font-weight: 500; white-space: nowrap;\"><i class=\"fas fa-circle\" style=\"margin-right: 0.375rem;\"></i> Stopped</span>`;
        const actions = row.cells[7];
        actions.querySelectorAll('button').forEach(b => b.disabled = false);
        // Replace Stop with Start and remove Restart if present
        const btns = Array.from(actions.querySelectorAll('button'));
        if (btns.length > 0) {
            btns[0].outerHTML = `<button class=\"btn btn-sm btn-success\" onclick=\"startVM('${node}', ${vmid})\" title=\"Start VM\"><i class=\"fas fa-play\"></i></button>`;
        }
        // Remove any restart button lingering
        Array.from(actions.querySelectorAll('button')).forEach(b => { if ((b.title||'').toLowerCase().includes('restart')) b.remove(); });
    };
    const pollVmStopped = async (maxMs = 20000, interval = 1000) => {
        const start = Date.now();
        while (Date.now() - start < maxMs) {
            try {
                const res = await fetch(`/api/v1/nodes/${node}/qemu/${vmid}/status/current`);
                const data = await res.json();
                if (data.success && (data.data?.status || '').toLowerCase() !== 'running') return true;
            } catch (e) {}
            await new Promise(r => setTimeout(r, interval));
        }
        return false;
    };

    try {
        setRowStopping();
        const response = await fetch(`/api/v1/nodes/${node}/qemu/${vmid}/status/stop`, { method: 'POST' });
        const result = await response.json();
        if (!result.success) throw new Error(result.error || 'Stop failed');
        const ok = await pollVmStopped();
        if (ok) setRowStopped(); else setRowStopped();
    } catch (error) {
        console.error('Stop VM error:', error);
        alert('Error: ' + (error.message || error));
    }
}

async function restartVM(node, vmid) {
    const findRow = () => {
        const rows = Array.from(document.querySelectorAll('table.data-table tbody tr'));
        return rows.find(r => (r.querySelector('td strong')?.textContent || '').trim() === String(vmid));
    };
    const setRowRestarting = () => {
        const row = findRow(); if (!row) return;
        const statusCell = row.cells[2];
        statusCell.innerHTML = `<span class=\"badge\" style=\"display:inline-flex; align-items:center; gap:0.5rem; padding:0.375rem 0.75rem; border-radius:0.25rem; background:#DBEAFE; color:#1E3A8A; font-size:0.875rem; font-weight:600;\"><i class='fas fa-sync-alt fa-spin'></i> Restarting...</span>`;
        const actions = row.cells[7]; actions.querySelectorAll('button').forEach(b => b.disabled = true);
    };
    const setRowRunning2 = () => {
        const row = findRow(); if (!row) return;
        const statusCell = row.cells[2];
        statusCell.innerHTML = `<span class=\"badge badge-success\" style=\"display: inline-block; padding: 0.375rem 0.75rem; border-radius: 0.25rem; background-color: #10b981; color: #ffffff; font-size: 0.875rem; font-weight: 500; white-space: nowrap;\"><i class=\"fas fa-circle\" style=\"margin-right: 0.375rem;\"></i> Running</span>`;
        const actions = row.cells[7]; actions.querySelectorAll('button').forEach(b => b.disabled = false);
        // Ensure Stop + Restart present
        const btns = Array.from(actions.querySelectorAll('button'));
        if (btns.length > 0) btns[0].outerHTML = `<button class=\"btn btn-sm btn-warning\" onclick=\"stopVM('${node}', ${vmid})\" title=\"Stop VM\"><i class=\"fas fa-stop\"></i></button>`;
        const hasRestart = Array.from(actions.querySelectorAll('button')).some(b => (b.title||'').toLowerCase().includes('restart'));
        if (!hasRestart) {
            const restartBtn = document.createElement('button');
            restartBtn.className = 'btn btn-sm btn-secondary';
            restartBtn.title = 'Restart VM';
            restartBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
            restartBtn.onclick = () => restartVM(node, vmid);
            const firstBtn = actions.querySelector('button');
            if (firstBtn && firstBtn.nextSibling) actions.insertBefore(restartBtn, firstBtn.nextSibling);
            else actions.appendChild(restartBtn);
        }
    };
    const waitForCycle = async (maxMs = 45000, interval = 1000) => {
        const start = Date.now(); let wentDown = false;
        while (Date.now() - start < maxMs) {
            try {
                const res = await fetch(`/api/v1/nodes/${node}/qemu/${vmid}/status/current`);
                const data = await res.json();
                if (data.success) {
                    const st = (data.data?.status || '').toLowerCase();
                    if (st !== 'running') wentDown = true;
                    if (wentDown && st === 'running') return true;
                }
            } catch (e) {}
            await new Promise(r => setTimeout(r, interval));
        }
        return false;
    };
    try {
        setRowRestarting();
        const response = await fetch(`/api/v1/nodes/${node}/qemu/${vmid}/status/reboot`, { method: 'POST' });
        const result = await response.json();
        if (!result.success) throw new Error(result.error || 'Reboot failed');
        const ok = await waitForCycle();
        if (ok) setRowRunning2(); else setRowRunning2();
    } catch (err) {
        console.error('Restart VM error:', err);
        alert('Error: ' + (err.message || err));
    }
}

async function deleteVM(node, vmid) {
    try {
        const confirmRes = await Swal.fire({
            title: 'Delete VM?',
            html: `<div style="text-align:left;line-height:1.4">This will permanently delete <strong>VM #${vmid}</strong> and all its data.<br/>This action cannot be undone.</div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#ef4444',
            focusCancel: true,
        });
        if (!confirmRes.isConfirmed) return;

        const response = await fetch(`/api/v1/nodes/${node}/qemu/${vmid}`, { method: 'DELETE' });
        const result = await response.json();
        if (result.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Deleted',
                text: `VM #${vmid} has been deleted.`,
                timer: 1600,
                showConfirmButton: false
            });
            location.reload();
        } else {
            await Swal.fire({
                icon: 'error',
                title: 'Delete failed',
                text: result.error || 'Unknown error'
            });
        }
    } catch (error) {
        await Swal.fire({ icon: 'error', title: 'Error', text: error.message || String(error) });
    }
}

function openConsoleVM(node, vmid) {
    const url = `/console?node=${encodeURIComponent(node)}&vmid=${encodeURIComponent(vmid)}`;
    // Open as in-app popup (modal) without browser URL bar
    const modal = document.getElementById('consoleModal');
    const frame = document.getElementById('consoleFrame');
    const win = document.getElementById('consoleWindow');
    if (frame) frame.src = url;
    if (win) {
        win.classList.remove('fullscreen');
        // default smaller size and position
        win.style.width = '70vw';
        win.style.height = '60vh';
        win.style.left = '12vw';
        win.style.top = '12vh';
    }
    if (modal) modal.classList.add('active');
    // Store for optional "Popout" button
    window.lastConsoleUrl = url;
}

function closeConsoleModal() {
    const modal = document.getElementById('consoleModal');
    const frame = document.getElementById('consoleFrame');
    frame.src = '';
    modal.classList.remove('active');
}

function viewVM(node, vmid) {
    window.location.href = `/nodes/${node}/qemu/${vmid}`;
}

function refreshData() {
    location.reload();
}

// Fullscreen toggle for modal container (CSS-based, not browser API)
document.getElementById('consoleModalFullscreenToggle')?.addEventListener('click', function() {
    const win = document.getElementById('consoleWindow');
    win.classList.toggle('fullscreen');
});

// Pop out to a new browser tab
document.getElementById('consoleModalPopout')?.addEventListener('click', function () {
    const url = window.lastConsoleUrl || document.getElementById('consoleFrame')?.src;
    if (url) {
        window.open(url, '_blank', 'noopener');
    }
});

// Drag support for floating console window
(function setupConsoleDrag(){
    const win = document.getElementById('consoleWindow');
    const header = document.getElementById('consoleWindowHeader');
    if (!win || !header) return;
    let dragging = false; let offX = 0; let offY = 0;
    const clamp = (v, min, max) => Math.max(min, Math.min(v, max));
    function onDown(e){
        if (win.classList.contains('fullscreen')) return;
        dragging = true;
        const rect = win.getBoundingClientRect();
        offX = e.clientX - rect.left; offY = e.clientY - rect.top;
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    }
    function onMove(e){
        if (!dragging) return;
        const vw = window.innerWidth, vh = window.innerHeight;
        let x = e.clientX - offX; let y = e.clientY - offY;
        x = clamp(x, 0, vw - win.offsetWidth);
        y = clamp(y, 0, vh - win.offsetHeight);
        win.style.left = x + 'px';
        win.style.top = y + 'px';
    }
    function onUp(){
        dragging = false;
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onUp);
    }
    header.addEventListener('mousedown', onDown);
    window.addEventListener('resize', () => {
        const rect = win.getBoundingClientRect();
        const maxX = window.innerWidth - rect.width;
        const maxY = window.innerHeight - rect.height;
        win.style.left = clamp(rect.left, 0, Math.max(0, maxX)) + 'px';
        win.style.top = clamp(rect.top, 0, Math.max(0, maxY)) + 'px';
    });
})();

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}

// Tab switching function
function switchVMTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.vm-tab-content').forEach(tab => {
        tab.style.display = 'none';
    });
    
    // Show selected tab
    const selectedTab = document.getElementById(tabName + '-tab');
    if (selectedTab) {
        selectedTab.style.display = 'block';
    }
    
    // Load ISOs when create-vm tab is active
    if (tabName === 'create-vm') {
        loadISOs();
        updateDiskFormat();  // Also update format based on selected storage
    }
    
    // Update active button style
    document.querySelectorAll('.storage-tab-btn').forEach(btn => {
        btn.style.color = '#6b7280';
        btn.style.borderBottomColor = 'transparent';
    });
    
    const activeBtn = event.target.closest('button');
    if (activeBtn) {
        activeBtn.style.color = '#3b82f6';
        activeBtn.style.borderBottomColor = '#3b82f6';
    }
}

// Add extra disk function
function addExtraDisk() {
    const container = document.getElementById('extraDisksContainer');
    const diskCount = container.children.length;
    const diskHTML = `
        <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end; margin-bottom: 1rem; padding: 1rem; background: #f3f4f6; border-radius: 0.375rem;">
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937; font-size: 0.875rem;">Size (GB)</label>
                <input type="number" class="extra-disk-size" value="50" min="10" max="2000" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937; font-size: 0.875rem;">Format</label>
                <select class="extra-disk-format" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    <option value="qcow2">QCOW2</option>
                    <option value="raw">RAW</option>
                </select>
            </div>
            <button type="button" class="vmware-btn vmware-btn-sm vmware-btn-danger" onclick="this.parentElement.remove()">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', diskHTML);
}

// Add extra network function
function addExtraNetwork() {
    const container = document.getElementById('extraNetworksContainer');
    const netCount = container.children.length;
    const netHTML = `
        <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end; margin-bottom: 1rem; padding: 1rem; background: #f3f4f6; border-radius: 0.375rem;">
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937; font-size: 0.875rem;">Model</label>
                <select class="extra-net-model" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    <option value="virtio" selected>VirtIO</option>
                    <option value="e1000">E1000</option>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937; font-size: 0.875rem;">VLAN ID</label>
                <input type="number" class="extra-net-vlan" min="0" max="4094" placeholder="Leave empty for no VLAN" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
            </div>
            <button type="button" class="vmware-btn vmware-btn-sm vmware-btn-danger" onclick="this.parentElement.remove()">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', netHTML);
}

// Update disk format based on storage type
function updateDiskFormat() {
    const storageSelect = document.getElementById('vmStorage');
    const selectedOption = storageSelect.options[storageSelect.selectedIndex];
    const storageType = selectedOption.getAttribute('data-type');
    const diskFormatInput = document.getElementById('vmDiskFormat');
    
    // LVM thin pool and RBD require raw format
    // Directory storage supports qcow2, raw, vmdk
    if (storageType === 'lvmthin' || storageType === 'rbd') {
        diskFormatInput.value = 'raw';
    } else if (storageType === 'dir') {
        diskFormatInput.value = 'qcow2';  // Default for dir storage
    } else {
        diskFormatInput.value = 'raw';  // Conservative default
    }
    
    console.log(`Storage type: ${storageType} → Disk format: ${diskFormatInput.value}`);
}

// Update ISO path visibility
function updateISOPath() {
    const isoSelect = document.getElementById('vmISO');
    const isoPathContainer = document.getElementById('vmISOPathContainer');
    if (isoSelect.value === 'custom') {
        isoPathContainer.style.display = 'block';
    } else {
        isoPathContainer.style.display = 'none';
    }
}

// Load ISO images from storage
async function loadISOs() {
    try {
        const response = await fetch('/api/v1/storage/iso-images');
        const data = await response.json();
        const isoSelect = document.getElementById('vmISO');
        
        if (data.success && data.data && Array.isArray(data.data)) {
            const currentValue = isoSelect.value;
            isoSelect.innerHTML = '<option value="">None - No ISO</option><option value="custom">Custom Path</option>';
            
            data.data.forEach(iso => {
                const option = document.createElement('option');
                // Use Proxmox volid (e.g., 'local:iso/ubuntu.iso') as the value for ide2
                option.value = iso.volid || iso.path || iso.rel_path || iso.name;
                // Store path in data attribute for optional use
                option.setAttribute('data-path', iso.path);
                option.setAttribute('data-volid', iso.volid);
                option.textContent = iso.name || iso.rel_path;
                isoSelect.appendChild(option);
            });
            
            isoSelect.value = currentValue;
        }
    } catch (error) {
        console.log('Could not load ISOs automatically');
    }
}

// Get available node for VM creation
async function getAvailableNode() {
    try {
        const response = await fetch('/api/v1/nodes');
        const data = await response.json();
        if (data.success && data.data && Array.isArray(data.data) && data.data.length > 0) {
            // Return first available node
            return data.data[0].node;
        }
    } catch (error) {
        console.error('Error getting nodes:', error);
    }
    // Fallback to 'localhost' if no nodes found
    return 'localhost';
}

// Create VM from form
function createVMFromForm() {
    const name = document.getElementById('vmName').value;
    const node = document.getElementById('vmNode').value;
    // vmid is auto-generated by backend - no need to read from form
    const cpus = document.getElementById('vmCPU').value;
    // cores not in form - use cpus as default
    const cores = cpus;
    const memoryGB = parseFloat(document.getElementById('vmMemory').value);
    const memoryMB = Math.round(memoryGB * 1024); // Convert GB to MB
    const disk = document.getElementById('vmDisk').value;
    const diskFormat = document.getElementById('vmDiskFormat').value;
    const storage = document.getElementById('vmStorage').value;
    // bios and machine are auto-defaults (not in form)
    const bios = 'seabios'; // Default BIOS
    const machine = 'q35';   // Default machine type
    const ostype = document.getElementById('vmOSType').value;
    const netModel = document.getElementById('vmNetModel').value;
    const bridge = document.getElementById('vmBridge').value;
    const vlan = document.getElementById('vmVLAN').value;
    const iso = document.getElementById('vmISO').value;
    const isoPath = document.getElementById('vmISOPath').value;
    // boot not needed for VM creation
    const description = document.getElementById('vmDescription').value;
    const autostart = document.getElementById('vmAutostart').checked;
    const kvm = document.getElementById('vmKVM').checked;
    const hugePages = document.getElementById('vmHugePages').checked;
    
    if (!name || !node || !storage) {
        Swal.fire({
            title: 'Missing Information',
            text: 'Please fill in VM Name, Node, and Storage',
            icon: 'warning',
            confirmButtonColor: '#3b82f6',
            heightAuto: true
        });
        return;
    }
    
    // Collect extra disks
    const extraDisks = Array.from(document.querySelectorAll('.extra-disk-size')).map((el, idx) => ({
        size: el.value,
        format: document.querySelectorAll('.extra-disk-format')[idx].value
    }));
    
    // Collect extra networks
    const extraNetworks = Array.from(document.querySelectorAll('.extra-net-model')).map((el, idx) => ({
        model: el.value,
        bridge: 'vmbr0', // Auto bridge
        vlan: document.querySelectorAll('.extra-net-vlan')[idx].value || undefined
    }));
    
    const vmConfig = {
        name, node, cpus, cores, memoryMB, disk, diskFormat, storage,
        bios, machine, netModel, bridge, vlan, iso, isoPath,
        description, autostart, kvm, hugePages,
        extraDisks, extraNetworks
    };
    
    Swal.fire({
        title: 'Create VM?',
        html: `<div style="text-align: left; font-size: 0.875rem;">
            <p><strong>${name}</strong> on <strong>${node}</strong></p>
            <p>${cpus} vCPU, ${memoryGB}GB RAM, ${disk}GB disk</p>
            <p>Storage: <strong>${storage}</strong></p>
            ${extraDisks.length > 0 ? `<p>+ ${extraDisks.length} additional disk(s)</p>` : ''}
            ${extraNetworks.length > 0 ? `<p>+ ${extraNetworks.length} additional network(s)</p>` : ''}
        </div>`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Create',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280'
    }).then(async result => {
        if (result.isConfirmed) {
            // Show loading dialog
            Swal.fire({
                title: 'Creating VM...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            try {
                // Prepare data for backend
                // Build disk configuration for Proxmox
                // Format: storage_id:size_in_GB,format=qcow2
                const diskConfig = `${vmConfig.storage}:${vmConfig.disk},format=${vmConfig.diskFormat}`;
                
                const createData = {
                    vmid: Math.floor(Math.random() * 900) + 100, // Generate random vmid 100-999
                    name: vmConfig.name,
                    memory: vmConfig.memoryMB,
                    cores: vmConfig.cores,
                    sockets: 1,
                    cpu: 'host',
                    kvm: vmConfig.kvm ? 1 : 0,
                    scsi0: diskConfig, // Primary disk with format
                    net0: `${vmConfig.netModel},bridge=${vmConfig.bridge}${vmConfig.vlan ? ',tag=' + vmConfig.vlan : ''}`,
                    ostype: ostype, // Use selected OS type from form
                    agent: 1 // QEMU guest agent
                };
                
                // Add ISO if selected
                if (vmConfig.iso && vmConfig.iso !== 'none' && vmConfig.iso !== '') {
                    // ide2 parameter: use Proxmox volid (e.g., 'local:iso/ubuntu.iso')
                    // The frontend dropdown value is the volid from the backend
                    createData.ide2 = `${vmConfig.iso},media=cdrom`;
                }
                
                // Determine the node to use
                const targetNode = vmConfig.node === 'auto' ? await getAvailableNode() : vmConfig.node;
                
                // Call backend API to create VM
                console.log('Sending VM creation request:', createData);
                const response = await fetch(`/api/v1/nodes/${targetNode}/qemu`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(createData)
                });
                
                console.log('Response status:', response.status);
                const responseText = await response.text();
                console.log('Response text:', responseText);
                
                if (!response.ok) {
                    try {
                        const error = JSON.parse(responseText);
                        throw new Error(error.error || error.detail || `Failed to create VM (${response.status})`);
                    } catch (parseError) {
                        throw new Error(`Server error (${response.status}): ${responseText.substring(0, 200)}`);
                    }
                }
                
                const result = JSON.parse(responseText);
                if (!result.success) {
                    throw new Error(result.error || 'Failed to create VM');
                }
                
                // Success - wait a bit for Proxmox to index the VM
                console.log('VM creation successful, waiting for Proxmox to index...');
                
                // Wait 2 seconds for Proxmox to process
                await new Promise(resolve => setTimeout(resolve, 2000));
                
                Swal.fire({
                    title: 'Success!',
                    text: 'VM created successfully',
                    icon: 'success',
                    confirmButtonColor: '#10b981'
                }).then(() => {
                    refreshData();
                    switchVMTab('overview');
                });
            } catch (error) {
                console.error('VM creation error:', error);
                Swal.fire({
                    title: 'Error',
                    text: error.message || 'Failed to create VM',
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            }
        }
    });
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
