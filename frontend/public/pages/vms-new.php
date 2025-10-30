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

// Calculate statistics
$totalVMs = count($allVMs);
$runningVMs = count(array_filter($allVMs, fn($vm) => ($vm['status'] ?? '') === 'running'));
$stoppedVMs = $totalVMs - $runningVMs;
$totalCPUs = array_sum(array_column($allVMs, 'cpus'));
$totalMemory = array_sum(array_column($allVMs, 'maxmem')) / (1024**3);
?>

<?php include __DIR__ . '/../components/header.php'; ?>

<body class="has-topnav vms-page">

<?php include __DIR__ . '/../components/topnav.php'; ?>

<div class="main-wrapper vms-fullwidth">
    <main class="main-content">
        <!-- VMs Tabs Navigation -->
        <div class="storage-tabs">
            <button class="storage-tab active" data-tab="summary">
                <i class="fas fa-chart-bar"></i> Summary
            </button>
            <button class="storage-tab" data-tab="vms-list">
                <i class="fas fa-desktop"></i> Virtual Machines
            </button>
            <button class="storage-tab" data-tab="create-vm">
                <i class="fas fa-plus-circle"></i> Create VM
            </button>
        </div>

        <!-- Tab 1: Summary -->
        <div class="tab-content active" id="tab-summary">
            <div class="storage-list-header">
                <h2 class="section-title">VMs Overview</h2>
                <button class="action-btn btn-primary" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>

            <!-- Statistics Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <!-- Total VMs Card -->
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 0.5rem; padding: 1.5rem; color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="font-size: 2.5rem; opacity: 0.8;"><i class="fas fa-desktop"></i></div>
                        <div>
                            <div style="font-size: 0.85rem; opacity: 0.9;">Total VMs</div>
                            <div style="font-size: 2rem; font-weight: 700;"><?php echo $totalVMs; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Running VMs Card -->
                <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 0.5rem; padding: 1.5rem; color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="font-size: 2.5rem; opacity: 0.8;"><i class="fas fa-play-circle"></i></div>
                        <div>
                            <div style="font-size: 0.85rem; opacity: 0.9;">Running</div>
                            <div style="font-size: 2rem; font-weight: 700;"><?php echo $runningVMs; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Stopped VMs Card -->
                <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 0.5rem; padding: 1.5rem; color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="font-size: 2.5rem; opacity: 0.8;"><i class="fas fa-stop-circle"></i></div>
                        <div>
                            <div style="font-size: 0.85rem; opacity: 0.9;">Stopped</div>
                            <div style="font-size: 2rem; font-weight: 700;"><?php echo $stoppedVMs; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Total CPUs Card -->
                <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 0.5rem; padding: 1.5rem; color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="font-size: 2.5rem; opacity: 0.8;"><i class="fas fa-microchip"></i></div>
                        <div>
                            <div style="font-size: 0.85rem; opacity: 0.9;">Total vCPUs</div>
                            <div style="font-size: 2rem; font-weight: 700;"><?php echo $totalCPUs; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Total Memory Card -->
                <div style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); border-radius: 0.5rem; padding: 1.5rem; color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="font-size: 2.5rem; opacity: 0.8;"><i class="fas fa-memory"></i></div>
                        <div>
                            <div style="font-size: 0.85rem; opacity: 0.9;">Total Memory</div>
                            <div style="font-size: 2rem; font-weight: 700;"><?php echo round($totalMemory, 1); ?> GB</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VMs Status Chart -->
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem; margin-top: 1.5rem;">
                <h3 style="font-weight: 600; margin-bottom: 1rem; color: #1f2937;">Status Distribution</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div style="text-align: center;">
                        <div style="width: 150px; height: 150px; border-radius: 50%; background: conic-gradient(#10b981 0deg <?php echo $runningVMs > 0 ? (360 * $runningVMs / $totalVMs) : 0; ?>deg, #f59e0b <?php echo (360 * $runningVMs / $totalVMs); ?>deg); margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                            <div style="width: 130px; height: 130px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;"><?php echo $totalVMs > 0 ? round(100 * $runningVMs / $totalVMs) : 0; ?>%</div>
                                <div style="font-size: 0.75rem; color: #6b7280;">Running</div>
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; justify-content: center; gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 20px; height: 20px; border-radius: 3px; background: #10b981;"></div>
                            <div>
                                <div style="font-weight: 600; color: #1f2937;">Running: <?php echo $runningVMs; ?></div>
                                <div style="font-size: 0.875rem; color: #6b7280;"><?php echo $totalVMs > 0 ? round(100 * $runningVMs / $totalVMs, 1) : 0; ?>%</div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 20px; height: 20px; border-radius: 3px; background: #f59e0b;"></div>
                            <div>
                                <div style="font-weight: 600; color: #1f2937;">Stopped: <?php echo $stoppedVMs; ?></div>
                                <div style="font-size: 0.875rem; color: #6b7280;"><?php echo $totalVMs > 0 ? round(100 * $stoppedVMs / $totalVMs, 1) : 0; ?>%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: VMs List -->
        <div class="tab-content" id="tab-vms-list">
            <div class="storage-list-header">
                <h2 class="section-title">Virtual Machines</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <button class="action-btn btn-primary" onclick="openCreateVMModal()">
                        <i class="fas fa-plus"></i> New VM
                    </button>
                    <button class="action-btn btn-secondary" onclick="refreshData()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>

            <div id="vmsList">
                <?php if (empty($allVMs)): ?>
                    <div style="text-align: center; padding: 2rem; background: white; border-radius: 0.5rem; border: 1px solid #e5e7eb;">
                        <i class="fas fa-desktop" style="font-size: 2rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                        <p style="color: #6b7280; margin-bottom: 1rem;">No virtual machines found</p>
                        <button class="action-btn btn-primary" onclick="openCreateVMModal()">
                            <i class="fas fa-plus"></i> Create Your First VM
                        </button>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
                            <thead>
                                <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                                    <th style="padding: 1rem; text-align: left; color: #374151; font-weight: 600; border-right: 1px solid #e5e7eb;">
                                        <i class="fas fa-tag" style="margin-right: 0.5rem;"></i> ID
                                    </th>
                                    <th style="padding: 1rem; text-align: left; color: #374151; font-weight: 600; border-right: 1px solid #e5e7eb;">
                                        <i class="fas fa-laptop" style="margin-right: 0.5rem;"></i> Name
                                    </th>
                                    <th style="padding: 1rem; text-align: center; color: #374151; font-weight: 600; border-right: 1px solid #e5e7eb;">
                                        <i class="fas fa-power-off" style="margin-right: 0.5rem;"></i> Status
                                    </th>
                                    <th style="padding: 1rem; text-align: center; color: #374151; font-weight: 600; border-right: 1px solid #e5e7eb;">
                                        <i class="fas fa-server" style="margin-right: 0.5rem;"></i> Node
                                    </th>
                                    <th style="padding: 1rem; text-align: center; color: #374151; font-weight: 600; border-right: 1px solid #e5e7eb;">
                                        <i class="fas fa-microchip" style="margin-right: 0.5rem;"></i> CPU
                                    </th>
                                    <th style="padding: 1rem; text-align: center; color: #374151; font-weight: 600; border-right: 1px solid #e5e7eb;">
                                        <i class="fas fa-memory" style="margin-right: 0.5rem;"></i> Memory
                                    </th>
                                    <th style="padding: 1rem; text-align: center; color: #374151; font-weight: 600;">
                                        <i class="fas fa-cogs" style="margin-right: 0.5rem;"></i> Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allVMs as $idx => $vm): ?>
                                    <?php
                                    $status = $vm['status'] ?? 'unknown';
                                    $rowColor = $idx % 2 === 0 ? '#ffffff' : '#f9fafb';
                                    ?>
                                    <tr style="background: <?php echo $rowColor; ?>; border-bottom: 1px solid #e5e7eb; transition: background 0.2s;" onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background='<?php echo $rowColor; ?>'">
                                        <td style="padding: 1rem; border-right: 1px solid #e5e7eb; color: #1f2937; font-weight: 600;">
                                            <?php echo $vm['vmid'] ?? 'N/A'; ?>
                                        </td>
                                        <td style="padding: 1rem; border-right: 1px solid #e5e7eb; color: #1f2937; font-weight: 500;">
                                            <?php echo htmlspecialchars($vm['name'] ?? 'N/A'); ?>
                                        </td>
                                        <td style="padding: 1rem; border-right: 1px solid #e5e7eb; text-align: center;">
                                            <?php if ($status === 'running'): ?>
                                                <span style="background: #dcfce7; color: #166534; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.85rem; font-weight: 600;">
                                                    <i class="fas fa-circle" style="margin-right: 0.25rem; color: #10b981;"></i> Running
                                                </span>
                                            <?php else: ?>
                                                <span style="background: #fef3c7; color: #92400e; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.85rem; font-weight: 600;">
                                                    <i class="fas fa-circle" style="margin-right: 0.25rem; color: #f59e0b;"></i> Stopped
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 1rem; border-right: 1px solid #e5e7eb; text-align: center; color: #1f2937;">
                                            <span style="background: #dbeafe; color: #1e40af; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.85rem; font-weight: 500;">
                                                <?php echo htmlspecialchars($vm['node'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1rem; border-right: 1px solid #e5e7eb; text-align: center; color: #1f2937; font-weight: 500;">
                                            <?php echo $vm['cpus'] ?? 0; ?> cores
                                        </td>
                                        <td style="padding: 1rem; border-right: 1px solid #e5e7eb; text-align: center; color: #1f2937; font-weight: 500;">
                                            <?php echo round(($vm['maxmem'] ?? 0) / (1024**3), 1); ?> GB
                                        </td>
                                        <td style="padding: 1rem; text-align: center;">
                                            <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                                <?php if ($status === 'running'): ?>
                                                    <button class="action-btn btn-warning" title="Stop VM" style="padding: 0.5rem; width: 2.5rem;" onclick="stopVM('<?php echo htmlspecialchars($vm['node'] ?? ''); ?>', <?php echo $vm['vmid'] ?? 0; ?>)">
                                                        <i class="fas fa-stop"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="action-btn btn-success" title="Start VM" style="padding: 0.5rem; width: 2.5rem;" onclick="startVM('<?php echo htmlspecialchars($vm['node'] ?? ''); ?>', <?php echo $vm['vmid'] ?? 0; ?>)">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="action-btn btn-info" title="View Details" style="padding: 0.5rem; width: 2.5rem;" onclick="viewVM('<?php echo htmlspecialchars($vm['node'] ?? ''); ?>', <?php echo $vm['vmid'] ?? 0; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="action-btn btn-danger" title="Delete VM" style="padding: 0.5rem; width: 2.5rem;" onclick="deleteVM('<?php echo htmlspecialchars($vm['node'] ?? ''); ?>', <?php echo $vm['vmid'] ?? 0; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab 3: Create VM -->
        <div class="tab-content" id="tab-create-vm">
            <div class="storage-list-header">
                <h2 class="section-title">Create New Virtual Machine</h2>
            </div>
            
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem; max-width: 800px;">
                <div style="background: #dbeafe; border-left: 4px solid #3b82f6; padding: 1rem; border-radius: 0.25rem; margin-bottom: 1.5rem;">
                    <i class="fas fa-info-circle" style="color: #1e40af; margin-right: 0.5rem;"></i>
                    <span style="color: #1e40af;">Configure your VM settings below. All fields are required.</span>
                </div>

                <form id="createVMForm" onsubmit="return submitCreateVM(event)">
                    <!-- VM Name -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">
                            <i class="fas fa-laptop" style="margin-right: 0.5rem; color: #3b82f6;"></i> VM Name
                        </label>
                        <input type="text" name="vmName" placeholder="e.g., web-server-01" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 1rem;" required>
                    </div>

                    <!-- Node Selection -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">
                            <i class="fas fa-server" style="margin-right: 0.5rem; color: #3b82f6;"></i> Node
                        </label>
                        <select name="node" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 1rem;" required>
                            <option value="">Select a node...</option>
                            <?php foreach ($nodes as $node): ?>
                                <option value="<?php echo htmlspecialchars($node['node'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($node['node'] ?? ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- CPU Cores -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">
                            <i class="fas fa-microchip" style="margin-right: 0.5rem; color: #3b82f6;"></i> CPU Cores
                        </label>
                        <input type="number" name="cpus" value="2" min="1" max="128" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 1rem;" required>
                    </div>

                    <!-- Memory (GB) -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">
                            <i class="fas fa-memory" style="margin-right: 0.5rem; color: #3b82f6;"></i> Memory (GB)
                        </label>
                        <input type="number" name="memory" value="4" min="1" max="1024" step="0.5" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 1rem;" required>
                    </div>

                    <!-- Storage -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">
                            <i class="fas fa-database" style="margin-right: 0.5rem; color: #3b82f6;"></i> Storage
                        </label>
                        <select name="storage" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 1rem;" required>
                            <option value="">Select storage...</option>
                            <?php foreach ($allStorage as $storage): ?>
                                <option value="<?php echo htmlspecialchars($storage['storage'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($storage['storage'] ?? ''); ?> (<?php echo $storage['type'] ?? 'unknown'; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Form Actions -->
                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="action-btn btn-success" style="flex: 1;">
                            <i class="fas fa-check"></i> Create Virtual Machine
                        </button>
                        <button type="reset" class="action-btn btn-secondary" style="flex: 1;">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>

<script>
// API Configuration
const CONFIGURED_API_URL = '<?php echo "https://{$config['api']['host']}:{$config['api']['port']}{$config['api']['prefix']}"; ?>';
const API_URL = CONFIGURED_API_URL.includes('backend') ? window.location.origin + '/api/v1' : CONFIGURED_API_URL;
const vmsData = <?php echo json_encode($allVMs); ?>;

console.log('VMs page loaded with API_URL:', API_URL);

// Tab switching functionality
document.querySelectorAll('.storage-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const tabName = this.getAttribute('data-tab');
        
        // Remove active class from all tabs and contents
        document.querySelectorAll('.storage-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        // Add active class to clicked tab and corresponding content
        this.classList.add('active');
        document.getElementById('tab-' + tabName).classList.add('active');
    });
});

// VM Management Functions
function startVM(node, vmid) {
    Swal.fire({
        title: 'Start VM?',
        text: `Are you sure you want to start VM ${vmid}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Start',
        confirmButtonColor: '#10b981'
    }).then(result => {
        if (result.isConfirmed) {
            executeVMAction(node, vmid, 'start');
        }
    });
}

function stopVM(node, vmid) {
    Swal.fire({
        title: 'Stop VM?',
        text: `Are you sure you want to stop VM ${vmid}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Stop',
        confirmButtonColor: '#f59e0b'
    }).then(result => {
        if (result.isConfirmed) {
            executeVMAction(node, vmid, 'stop');
        }
    });
}

function deleteVM(node, vmid) {
    Swal.fire({
        title: 'Delete VM?',
        text: `This will permanently delete VM ${vmid} and all its data. This cannot be undone.`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        confirmButtonColor: '#ef4444'
    }).then(result => {
        if (result.isConfirmed) {
            executeVMAction(node, vmid, 'destroy');
        }
    });
}

function executeVMAction(node, vmid, action) {
    fetch(`${API_URL}/nodes/${node}/vms/${vmid}/${action}`, {
        method: 'POST',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: `VM ${action} completed successfully`,
                timer: 3000,
                timerProgressBar: true
            }).then(() => refreshData());
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.error || 'Action failed'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message
        });
    });
}

function viewVM(node, vmid) {
    Swal.fire({
        title: `VM Details #${vmid}`,
        html: `<p>Detailed view not yet implemented</p>`,
        icon: 'info'
    });
}

function openCreateVMModal() {
    const tab = document.querySelector('[data-tab="create-vm"]');
    if (tab) tab.click();
}

function submitCreateVM(event) {
    event.preventDefault();
    Swal.fire({
        icon: 'info',
        title: 'Feature Coming Soon',
        text: 'VM creation will be available soon'
    });
    return false;
}

function refreshData() {
    location.reload();
}

// Initialize
console.log('VMs page ready');
</script>

<style>
.vms-page {
    background: #f9fafb;
}

.vms-fullwidth {
    display: flex;
    flex-direction: column;
}

.storage-tabs {
    display: flex;
    gap: 1rem;
    padding: 0 2rem;
    border-bottom: 2px solid #e5e7eb;
    background: white;
    overflow-x: auto;
}

.storage-tab {
    padding: 1rem 1.5rem;
    border: none;
    background: none;
    color: #6b7280;
    cursor: pointer;
    font-weight: 500;
    font-size: 1rem;
    transition: all 0.2s;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    white-space: nowrap;
}

.storage-tab:hover {
    color: #3b82f6;
}

.storage-tab.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}

.tab-content {
    display: none;
    padding: 2rem;
}

.tab-content.active {
    display: block;
}

.storage-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.action-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    font-weight: 500;
    font-size: 0.95rem;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
}

.btn-warning {
    background: #f59e0b;
    color: white;
}

.btn-warning:hover {
    background: #d97706;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

.btn-info {
    background: #06b6d4;
    color: white;
}

.btn-info:hover {
    background: #0891b2;
}

@media (max-width: 768px) {
    .storage-list-header {
        flex-direction: column;
        gap: 1rem;
    }

    .tab-content {
        padding: 1rem;
    }
}
</style>

</body>
</html>
