<?php
// Load autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

$config = require __DIR__ . '/../../src/Config/config.php';
$active = 'storage';

use Silo\Services\ApiService;
$api = new ApiService();

// Get nodes first
$nodesResponse = $api->get('/nodes');
$nodes = $nodesResponse['data'] ?? [];

// Get storage from backend API endpoint (which has accurate filesystem info)
$storageInfoResponse = $api->get("/storage/info");
$backendStorage = $storageInfoResponse['data'] ?? [];

// Map backend storage to frontend format
$allStorage = [];
foreach ($backendStorage as $storage) {
    $storageData = [
        'storage' => $storage['storage'] ?? '',
        'type' => $storage['type'] ?? '',
        'node' => $storage['node'] ?? '',
        'enabled' => $storage['enabled'] ?? 1,
        'total' => $storage['total'] ?? 0,
        'used' => $storage['used'] ?? 0,
        'avail' => $storage['available'] ?? 0,
        'content' => $storage['content'] ?? '',
    ];
    
    // Add backend-specific fields for accurate sizing
    if (isset($storage['total_gb'])) {
        $storageData['total_gb'] = $storage['total_gb'];
        $storageData['used_gb'] = $storage['used_gb'] ?? 0;
        $storageData['available_gb'] = $storage['available_gb'] ?? 0;
    }
    
    $allStorage[] = $storageData;
}

// Fallback: if backend API not available, use Proxmox API
if (empty($allStorage)) {
    foreach ($nodes as $node) {
        $nodeName = $node['node'] ?? '';
        if ($nodeName) {
            $nodeStorageResponse = $api->get("/nodes/{$nodeName}/storage");
            $nodeStorage = $nodeStorageResponse['data'] ?? [];
            
            // Add node info to each storage
            foreach ($nodeStorage as $storage) {
                // Merge node name into storage array
                $allStorage[] = array_merge($storage, ['node' => $nodeName]);
            }
        }
    }
    
    // If still no nodes or storage from nodes, fallback to cluster storage list
    if (empty($allStorage)) {
        $storageResponse = $api->get("/storage");
        $clusterStorage = $storageResponse['data'] ?? [];
        foreach ($clusterStorage as $storage) {
            $allStorage[] = array_merge($storage, ['node' => 'cluster']);
        }
    }
}
?>

<?php include __DIR__ . '/../components/header.php'; ?>

<div class="main-wrapper">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Storage</h1>
        </div>
        
        <!-- Storage Tabs Navigation -->
        <div style="display: flex; gap: 1rem; padding: 1rem; border-bottom: 2px solid #e5e7eb; background: #ffffff; margin-bottom: 1rem;">
            <button class="storage-tab-btn active" data-tab="summary" onclick="switchStorageTab('summary', this)" style="padding: 0.75rem 1.5rem; background: transparent; border: none; color: #6b7280; cursor: pointer; font-weight: 500; border-bottom: 3px solid transparent; transition: all 0.2s;">
                <i class="fas fa-chart-bar" style="margin-right: 0.5rem;"></i> Summary
            </button>
            <button class="storage-tab-btn" data-tab="datastores" onclick="switchStorageTab('datastores', this)" style="padding: 0.75rem 1.5rem; background: transparent; border: none; color: #6b7280; cursor: pointer; font-weight: 500; border-bottom: 3px solid transparent; transition: all 0.2s;">
                <i class="fas fa-database" style="margin-right: 0.5rem;"></i> Datastores
            </button>
            <button class="storage-tab-btn" data-tab="iso-images" onclick="switchStorageTab('iso-images', this)" style="padding: 0.75rem 1.5rem; background: transparent; border: none; color: #6b7280; cursor: pointer; font-weight: 500; border-bottom: 3px solid transparent; transition: all 0.2s;">
                <i class="fas fa-compact-disc" style="margin-right: 0.5rem;"></i> ISO Images
            </button>
        </div>
        
        <!-- Tab Content -->
        <div id="summary-tab" class="storage-tab-content" style="display: block;">
        <!-- Storage Overview - VMware Style -->
        <div class="vmware-storage-overview">
            <?php 
            // Helper to humanize bytes (GiB/TiB) and return [value, unit]
            if (!function_exists('humanizeBytes')) {
                function humanizeBytes($bytes) {
                    $units = ['B','KB','MB','GB','TB','PB'];
                    $i = 0;
                    $val = max(0, (float)$bytes);
                    while ($val >= 1024 && $i < count($units) - 1) { $val /= 1024; $i++; }
                    return [round($val, 2), $units[$i]];
                }
            }

            $totalUsed = 0;        // bytes
            $totalCapacity = 0;    // bytes
            $isoCount = 0;
            foreach ($allStorage as $storage) {
                // Proxmox API: node storage often uses 'total' (bytes), 'used' (bytes)
                $used = isset($storage['used']) ? (int)$storage['used'] : ((int)($storage['disk'] ?? 0));
                $total = isset($storage['total']) ? (int)$storage['total'] : ((int)($storage['maxdisk'] ?? 0));

                if ($total > 0) {
                    $totalUsed += max(0, min($used, $total));
                    $totalCapacity += $total;
                }

                // Count ISO-capable storage
                if (!empty($storage['content']) && strpos((string)$storage['content'], 'iso') !== false) {
                    $isoCount++;
                }
            }

            $totalPercent = $totalCapacity > 0 ? ($totalUsed / $totalCapacity) * 100 : 0;
            $freeBytes = max(0, $totalCapacity - $totalUsed);

            [$totalVal, $totalUnit] = humanizeBytes($totalCapacity);
            [$usedVal, $usedUnit]   = humanizeBytes($totalUsed);
            [$freeVal, $freeUnit]   = humanizeBytes($freeBytes);
            ?>
            
            <div class="vmware-card storage-summary">
                <div class="vmware-card-icon">
                    <i class="fas fa-server"></i>
                </div>
                <div class="vmware-card-content">
                    <div class="vmware-metric-value"><?php echo count($allStorage); ?></div>
                    <div class="vmware-metric-label">Datastores</div>
                    <div class="vmware-metric-sublabel"><?php echo $isoCount; ?> support ISO images</div>
                </div>
            </div>
            
            <div class="vmware-card storage-capacity">
                <div class="vmware-card-icon capacity">
                    <i class="fas fa-database"></i>
                </div>
                <div class="vmware-card-content">
                    <div class="vmware-metric-value"><?php echo $totalVal; ?> <span class="unit"><?php echo $totalUnit; ?></span></div>
                    <div class="vmware-metric-label">Total Capacity</div>
                    <div class="capacity-breakdown">
                        <span class="used"><?php echo $usedVal . ' ' . $usedUnit; ?> Used</span>
                        <span class="separator">•</span>
                        <span class="free"><?php echo $freeVal . ' ' . $freeUnit; ?> Free</span>
                    </div>
                </div>
            </div>
            
            <div class="vmware-card storage-usage">
                <div class="vmware-card-icon usage">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="vmware-card-content">
                    <div class="vmware-metric-value"><?php echo round($totalPercent); ?><span class="unit">%</span></div>
                    <div class="vmware-metric-label">Storage Utilization</div>
                    <div class="vmware-progress-bar">
                        <div class="vmware-progress-fill <?php 
                            echo $totalPercent > 90 ? 'critical' : 
                                 ($totalPercent > 75 ? 'warning' : 'normal'); 
                        ?>" style="width: <?php echo min($totalPercent, 100); ?>%"></div>
                    </div>
                </div>
            </div>
            
            <div class="vmware-card storage-health">
                <div class="vmware-card-icon health">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <div class="vmware-card-content">
                    <div class="vmware-metric-value">
                        <span class="health-status healthy">
                            <i class="fas fa-check-circle"></i> Healthy
                        </span>
                    </div>
                    <div class="vmware-metric-label">Storage Health</div>
                    <div class="vmware-metric-sublabel">All datastores operational</div>
                </div>
            </div>
        </div>
        </div>


        
        <!-- Datastores Tab -->
        <div id="datastores-tab" class="storage-tab-content" style="display: none;">
        <div class="vmware-table-container">
            <div class="vmware-table-header">
                <h2 class="vmware-section-title">
                    <i class="fas fa-database"></i> Datastores
                </h2>
                <div class="vmware-table-actions">
                    <button class="vmware-btn vmware-btn-primary" onclick="openAddStorageModal()" title="Add Datastore">
                        <i class="fas fa-plus"></i> Add Datastore
                    </button>
                    <button class="vmware-btn vmware-btn-secondary" onclick="refreshData()" title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="vmware-table-body">
                <?php if (empty($allStorage)): ?>
                    <div class="vmware-empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <h3 class="empty-state-title">No Datastores Found</h3>
                        <p class="empty-state-description">Get started by adding your first storage datastore</p>
                        <button class="vmware-btn vmware-btn-primary" onclick="openAddStorageModal()">
                            <i class="fas fa-plus"></i> Add Datastore
                        </button>
                    </div>
                <?php else: ?>
                    <table class="vmware-table">
                        <thead>
                            <tr>
                                <th class="col-checkbox"><input type="checkbox" id="selectAll"></th>
                                <th class="col-status">Status</th>
                                <th class="col-name">Name</th>
                                <th class="col-type">Type</th>
                                <th class="col-node">Node</th>
                                <th class="col-capacity">Total</th>
                                <th class="col-available">Available</th>
                                <th class="col-usage">Used</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allStorage as $storage): ?>
                                <?php
                                $used = $storage['used'] ?? ($storage['disk'] ?? 0);
                                $total = $storage['total'] ?? ($storage['maxdisk'] ?? 0);
                                $available = $storage['avail'] ?? ($total - $used);
                                $percent = $total > 0 ? ($used / $total) * 100 : 0;
                                $isDisabled = isset($storage['disabled']) && $storage['disabled'];
                                $content = $storage['content'] ?? '';
                                $hasISO = strpos($content, 'iso') !== false;
                                ?>
                                <tr class="<?php echo $isDisabled ? 'disabled' : ''; ?>">
                                    <td class="col-checkbox">
                                        <input type="checkbox" class="row-checkbox">
                                    </td>
                                    <td class="col-status">
                                        <?php if ($isDisabled): ?>
                                            <span class="status-indicator offline">
                                                <i class="fas fa-times-circle"></i>
                                            </span>
                                        <?php else: ?>
                                            <span class="status-indicator online">
                                                <i class="fas fa-check-circle"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-name">
                                        <a href="#" onclick="viewStorageContent('<?php echo htmlspecialchars($storage['storage']); ?>', '<?php echo htmlspecialchars($storage['node'] ?? 'cluster'); ?>'); return false;" class="storage-name-link">
                                            <?php echo htmlspecialchars($storage['storage'] ?? 'N/A'); ?>
                                        </a>
                                    </td>
                                    <td class="col-type">
                                        <span class="storage-type-label">
                                            <?php echo $storage['type'] ?? 'dir'; ?>
                                        </span>
                                    </td>
                                    <td class="col-node">
                                        <span class="node-label">
                                            <?php echo htmlspecialchars($storage['node'] ?? 'cluster'); ?>
                                        </span>
                                    </td>
                                    <td class="col-capacity">
                                        <?php echo $total > 0 ? number_format($total / (1024**3), 2) : '0'; ?> GB
                                    </td>
                                    <td class="col-available">
                                        <?php echo $available > 0 ? number_format($available / (1024**3), 2) : '0'; ?> GB
                                    </td>
                                    <td class="col-usage">
                                        <div class="usage-cell">
                                            <span class="usage-value"><?php echo $used > 0 ? number_format($used / (1024**3), 2) : '0'; ?> GB</span>
                                            <div class="usage-bar-mini">
                                                <div class="usage-bar-fill-mini <?php 
                                                    echo $percent > 90 ? 'critical' : 
                                                         ($percent > 75 ? 'warning' : 'normal'); 
                                                ?>" style="width: <?php echo min($percent, 100); ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="col-actions">
                                        <div class="action-buttons-mini">
                                            <button class="btn-icon" onclick="viewStorageContent('<?php echo htmlspecialchars($storage['storage']); ?>', '<?php echo htmlspecialchars($storage['node'] ?? 'cluster'); ?>')" title="Browse">
                                                <i class="fas fa-folder-open"></i>
                                            </button>
                                            <?php if ($hasISO): ?>
                                                <button class="btn-icon primary" onclick="uploadToStorage('<?php echo htmlspecialchars($storage['storage']); ?>', '<?php echo htmlspecialchars($storage['node'] ?? 'cluster'); ?>')" title="Upload">
                                                    <i class="fas fa-upload"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn-icon danger" onclick="deleteStorage('<?php echo htmlspecialchars($storage['storage']); ?>')" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        </div> <!-- End Datastores Tab -->
        
        <!-- ISO Images Tab -->
        <div id="iso-images-tab" class="storage-tab-content" style="display: none;">
        <div class="vmware-table-container">
            <div class="vmware-table-header">
                <h2 class="vmware-section-title">
                    <i class="fas fa-compact-disc"></i> ISO Images
                </h2>
                <div class="vmware-table-actions">
                    <button class="vmware-btn vmware-btn-primary" onclick="openUploadISOModal()" title="Upload ISO">
                        <i class="fas fa-upload"></i> Upload ISO
                    </button>
                </div>
            </div>
            <div class="iso-images-grid" id="isoImagesGrid" style="padding: 2rem;">
                <p style="text-align: center; color: #6b7280;">Loading ISO images...</p>
            </div>
        </div>
        </div> <!-- End ISO Images Tab -->

    </main>
</div>
<!-- IWM Footer: Innovation • Wisdom • Mastery -->
<footer class="iwm-footer" aria-label="IWM footer">
    <div class="iwm-footer-inner">
        <div class="iwm-logo" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M32 2.5l22.516 12.999v32.002L32 60.5 9.484 47.501V15.499L32 2.5z" fill="#E6B325" fill-opacity="0.9"/>
                <path d="M12 18.2c6.2-4.4 17.2-9.2 28 0 6.6 5.5 12.4 6.3 12.4 6.3v6.2s-10.9 2.2-19-3.3c-9.8-6.6-16.2-2.6-21.4 0.6V18.2z" fill="#7CB6F2"/>
                <path d="M12 35.5c6.1-3.9 12.8-6.1 21.5-0.9 7.5 4.6 19 3.3 19 3.3v6.2s-9.9 2.4-17.6-0.5c-8.7-3.3-12.6-8.7-22.9-3.7V35.5z" fill="#CBA23A"/>
                <path d="M32 6.6l19.6 11.3v28.2L32 57.4 12.4 46.1V17.9L32 6.6z" stroke="#fff" stroke-opacity="0.9" stroke-width="2"/>
            </svg>
        </div>
        <div class="iwm-text">
            <span class="iwm-brand">IWM</span>
            <span class="iwm-tag">Innovation • Wisdom • Mastery</span>
        </div>
    </div>
</footer>

<!-- Add Storage Modal -->
<div id="addStorageModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-plus"></i> Add Storage
            </h3>
            <button class="modal-close" onclick="closeAddStorageModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addStorageForm" onsubmit="return submitAddStorage(event)">
                <!-- Step 1: Select Node and Type -->
                <div id="step1">
                    <div class="form-group">
                        <label class="form-label">Node *</label>
                        <select name="node" id="storageNode" class="form-control" required onchange="updateStorageType()">
                            <option value="">-- Select Node --</option>
                            <?php foreach ($nodes as $node): ?>
                                <option value="<?php echo htmlspecialchars($node['node']); ?>">
                                    <?php echo htmlspecialchars($node['node']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Storage Type *</label>
                        <div class="storage-type-selector">
                            <label class="type-radio-label">
                                <input type="radio" name="type" value="dir" class="type-radio" required onchange="showStorageFields()">
                                <div class="type-card">
                                    <i class="fas fa-hdd"></i>
                                    <h3>Local Disk</h3>
                                    <p>Direct storage on local disk</p>
                                </div>
                            </label>
                            <label class="type-radio-label">
                                <input type="radio" name="type" value="rbd" class="type-radio" required onchange="showStorageFields()">
                                <div class="type-card">
                                    <i class="fas fa-server"></i>
                                    <h3>Virtual Storage</h3>
                                    <p>Ceph storage cluster</p>
                                </div>
                            </label>
                            <label class="type-radio-label">
                                <input type="radio" name="type" value="nfs" class="type-radio" required onchange="showStorageFields()">
                                <div class="type-card">
                                    <i class="fas fa-network-wired"></i>
                                    <h3>Network Storage</h3>
                                    <p>NFS share</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    <!-- Scan Button / Create Directory Options -->
                    <div id="scanButton" style="margin-bottom: 1rem;">
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button type="button" class="btn btn-primary" onclick="scanAvailableStorage()" id="scanBtn">
                                <i class="fas fa-search"></i> Scan Disk
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="window.openFileBrowser('/mnt');">
                                <i class="fas fa-folder-open"></i> Browse Folder
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="toggleCreateDirForm()">
                                <i class="fas fa-folder-plus"></i> Create Directory
                            </button>
                        </div>
                    </div>
                    
                    <!-- Create Directory Form -->
                    <div id="createDirForm" style="display: none; margin-bottom: 1.5rem; padding: 1rem; background: rgba(96, 165, 250, 0.1); border-radius: 0.5rem; border: 1px solid rgba(96, 165, 250, 0.3);">
                        <h4 style="font-size: 0.95rem; margin-bottom: 1rem; color: var(--gray-700);">
                            <i class="fas fa-folder-plus"></i> Create New Storage Directory
                        </h4>
                        <div class="form-group">
                            <label class="form-label">Storage Directory Path *</label>
                            <div style="display: flex; gap: 0.5rem;">
                                <input type="text" id="createDirPath" class="form-control" placeholder="/mnt/storage_name" style="flex: 1;">
                                <button type="button" class="btn btn-primary" onclick="createStorageDirectory()" style="white-space: nowrap;">
                                    <i class="fas fa-plus"></i> Create
                                </button>
                            </div>
                            <small class="text-muted">Directory will be created at this path. Must start with /mnt/</small>
                        </div>
                    </div>
                    
                    <!-- Detected Storage List -->
                    <div id="detectedList" style="display: none; margin-bottom: 1.5rem;">
                        <h4 style="font-size: 0.95rem; margin-bottom: 0.75rem; color: var(--gray-700);">
                            <i class="fas fa-server"></i> Available Storage
                        </h4>
                        <div id="detectedItems" class="detected-items"></div>
                    </div>
                </div>
                
                <!-- Step 2: Storage Configuration -->
                <div id="step2" style="display: none;">
                    <hr style="margin: 1.5rem 0; border-color: rgba(148, 163, 184, 0.2);">
                    
                    <div class="form-group">
                        <label class="form-label">Storage Name *</label>
                        <input type="text" name="storageName" id="storageName" class="form-control" placeholder="e.g., Main Storage, Backup Storage">
                        <small class="text-muted">Display name for this storage (alphanumeric and hyphens only)</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Storage ID *</label>
                        <input type="text" name="storage" id="storageId" class="form-control" placeholder="Auto-generated" readonly>
                        <small class="text-muted">Automatically generated from storage name</small>
                    </div>
                    
                    <!-- DIR Fields -->
                    <div id="fields-dir" class="storage-fields" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">Path *</label>
                            <input type="text" name="path" class="form-control" placeholder="/mnt/storage">
                            <small class="text-muted">Mount point directory path</small>
                        </div>
                    </div>
                    
                    <!-- LVM Fields -->
                    <div id="fields-lvm" class="storage-fields" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">Volume Group *</label>
                            <input type="text" name="vgname" class="form-control" placeholder="vg_data">
                            <small class="text-muted">Name of the LVM volume group</small>
                        </div>
                    </div>
                    
                    <!-- LVM-Thin Fields -->
                    <div id="fields-lvmthin" class="storage-fields" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">Volume Group *</label>
                            <input type="text" name="vgname" class="form-control" placeholder="vg_data">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Thin Pool *</label>
                            <input type="text" name="thinpool" class="form-control" placeholder="data">
                        </div>
                    </div>
                    
                    <!-- ZFS Fields -->
                    <div id="fields-zfspool" class="storage-fields" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">ZFS Pool *</label>
                            <input type="text" name="pool" class="form-control" placeholder="tank">
                            <small class="text-muted">Name of the ZFS pool</small>
                        </div>
                    </div>
                    
                    <!-- NFS Fields -->
                    <div id="fields-nfs" class="storage-fields" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">NFS Server *</label>
                            <input type="text" name="server" class="form-control" placeholder="192.168.1.100">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Export Path *</label>
                            <input type="text" name="export" class="form-control" placeholder="/export/storage">
                        </div>
                    </div>
                    
                    <!-- CIFS Fields -->
                    <div id="fields-cifs" class="storage-fields" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">Server *</label>
                            <input type="text" name="server" class="form-control" placeholder="192.168.1.100">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Share *</label>
                            <input type="text" name="share" class="form-control" placeholder="storage">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                    </div>
                    
                    <!-- iSCSI Fields -->
                    <div id="fields-iscsi" class="storage-fields" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">Portal *</label>
                            <input type="text" name="portal" class="form-control" placeholder="192.168.1.100:3260">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Target *</label>
                            <input type="text" name="target" class="form-control" placeholder="iqn.2024-01.com.example:storage">
                        </div>
                    </div>
                    
                    <!-- GlusterFS Fields -->
                    <div id="fields-glusterfs" class="storage-fields" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">Server *</label>
                            <input type="text" name="server" class="form-control" placeholder="192.168.1.100">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Volume *</label>
                            <input type="text" name="volume" class="form-control" placeholder="gv0">
                        </div>
                    </div>
                    
                    <!-- Ceph RBD Fields -->
                    <div id="fields-rbd" class="storage-fields" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">Monitor Hosts *</label>
                            <input type="text" name="monhost" class="form-control" placeholder="192.168.1.101,192.168.1.102">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pool *</label>
                            <input type="text" name="pool" class="form-control" placeholder="rbd">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="admin">
                        </div>
                    </div>
                    
                    <!-- Common Fields -->
                    <div class="form-group">
                        <label class="form-label">Content Types *</label>
                        <select name="content[]" id="storageContent" class="form-control" multiple size="6" style="height: auto;">
                            <option value="images" selected>Disk Images (VM/CT disks)</option>
                            <option value="iso" selected>ISO Images (Installation media)</option>
                            <option value="vztmpl" selected>Container Templates</option>
                            <option value="backup">Backups</option>
                            <option value="rootdir">Container Storage (rootdir)</option>
                            <option value="snippets">Snippets (configuration files)</option>
                        </select>
                        <small class="text-muted" style="display: block; margin-top: 0.5rem;">
                            💡 Hold <kbd>Ctrl</kbd> (or <kbd>Cmd</kbd> on Mac) to select multiple items
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="shared" id="sharedStorage">
                            <span>Shared storage (available to all cluster nodes)</span>
                        </label>
                        <small class="text-muted">Enable this for network storage that's accessible by all nodes</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="enabled" checked>
                            <span>Enable storage immediately</span>
                        </label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddStorageModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Storage
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload ISO Modal -->
<div id="uploadISOModal" class="modal">
    <div class="modal-content modal-upload" style="background: #ffffff; box-shadow: 0 10px 40px rgba(0,0,0,0.2); border-radius: 0.5rem;">
        <div class="modal-header" style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
            <h3 class="modal-title" style="color: #1f2937; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-upload" style="color: #3b82f6;"></i> Upload ISO Image
            </h3>
            <button class="modal-close" onclick="closeUploadISOModal()" style="color: #6b7280; font-size: 1.5rem; cursor: pointer; border: none; background: none; padding: 0;">&times;</button>
        </div>
        <div class="modal-body" style="color: #1f2937; background: #ffffff;">
            <form id="uploadISOForm" onsubmit="return submitUploadISO(event)">
                <div class="form-group">
                    <label class="form-label" style="color: #374151; font-weight: 600; margin-bottom: 0.5rem; display: block;">Node *</label>
                    <select name="node" id="uploadNode" class="form-control" required onchange="updateUploadStorageList()" style="background: #ffffff; border: 1px solid #e5e7eb; color: #1f2937; padding: 0.5rem; border-radius: 0.375rem; width: 100%; font-size: 0.95rem;">
                        <option value="">Select Node</option>
                        <?php foreach ($nodes as $node): ?>
                            <option value="<?php echo htmlspecialchars($node['node']); ?>">
                                <?php echo htmlspecialchars($node['node']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" style="color: #374151; font-weight: 600; margin-bottom: 0.5rem; display: block; margin-top: 1rem;">Storage *</label>
                    <select name="storage" id="uploadStorage" class="form-control" required style="background: #ffffff; border: 1px solid #e5e7eb; color: #1f2937; padding: 0.5rem; border-radius: 0.375rem; width: 100%; font-size: 0.95rem;">
                        <option value="">Select storage that supports ISO</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" style="color: #374151; font-weight: 600; margin-bottom: 0.5rem; display: block; margin-top: 1rem;">ISO File *</label>
                    <input type="file" name="file" id="isoFile" class="form-control file-input" required accept=".iso" style="background: #ffffff; border: 1px solid #e5e7eb; color: #1f2937; padding: 0.5rem; border-radius: 0.375rem; width: 100%; font-size: 0.95rem; cursor: pointer;">
                    <small style="color: #6b7280; font-size: 0.875rem; display: block; margin-top: 0.25rem;">Select an ISO image file from your computer</small>
                </div>
                
                <div id="uploadProgress" style="display: none; margin-top: 1.5rem;">
                    <div class="progress-bar" style="height: 28px; margin-bottom: 1rem; background: #e5e7eb; border-radius: 0.375rem; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);">
                        <div id="uploadProgressBar" class="progress-fill" style="width: 0%; transition: width 0.3s ease; background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%); height: 100%; display: flex; align-items: center; justify-content: flex-end; padding-right: 0.5rem; color: white; font-weight: 600; font-size: 0.875rem;"></div>
                    </div>
                    <p id="uploadStatus" style="text-align: center; color: #6b7280; font-size: 0.95rem; margin: 0.5rem 0; font-weight: 500;">Preparing upload...</p>
                    <p id="uploadFileInfo" style="text-align: center; color: #9ca3af; font-size: 0.875rem; margin: 0; display: none;"></p>
                </div>
            </form>
        </div>
        <div class="modal-footer" style="background: #f9fafb; border-top: 1px solid #e5e7eb; display: flex; gap: 0.5rem; justify-content: flex-end; padding: 1rem; border-radius: 0 0 0.5rem 0.5rem;">
            <button type="button" class="btn btn-secondary" onclick="closeUploadISOModal()" style="background: #e5e7eb; color: #374151; border: none; padding: 0.5rem 1.5rem; border-radius: 0.375rem; cursor: pointer; font-weight: 500; transition: background 0.2s;">Cancel</button>
            <button type="submit" form="uploadISOForm" id="uploadBtn" class="btn btn-primary" style="background: #3b82f6; color: white; border: none; padding: 0.5rem 1.5rem; border-radius: 0.375rem; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; transition: background 0.2s;">
                <i class="fas fa-upload"></i> Upload ISO
            </button>
        </div>
    </div>
</div>

<!-- File Browser Modal -->
<div id="fileBrowserModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-folder-open"></i> File Browser
            </h3>
            <button class="modal-close" onclick="closeFileBrowserModal()">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Breadcrumb Navigation -->
            <div class="breadcrumb-nav" style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <button type="button" class="btn-breadcrumb" onclick="navigateToPath('/mnt')">
                    <i class="fas fa-folder"></i> /mnt
                </button>
                <span id="breadcrumbPath" style="color: var(--gray-600); font-size: 0.95rem;"></span>
            </div>
            
            <!-- File Browser Toolbar -->
            <div class="browser-toolbar" style="margin-bottom: 1rem; display: flex; gap: 0.5rem; align-items: center;">
                <input type="text" id="newDirName" class="form-control" placeholder="New folder name" style="flex: 1; max-width: 250px;">
                <button type="button" class="btn btn-primary" onclick="createNewDirectory()">
                    <i class="fas fa-folder-plus"></i> Create Folder
                </button>
                <button type="button" class="btn btn-secondary" onclick="refreshFileBrowser()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            
            <!-- File List -->
            <div class="file-browser-container" style="border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 0.5rem; background: rgba(15, 23, 42, 0.5); max-height: 400px; overflow-y: auto;">
                <table class="file-browser-table" style="width: 100%; border-collapse: collapse;">
                    <thead style="background: rgba(96, 165, 250, 0.1); position: sticky; top: 0;">
                        <tr>
                            <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid rgba(148, 163, 184, 0.2);">
                                <i class="fas fa-file"></i> Name
                            </th>
                            <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid rgba(148, 163, 184, 0.2); width: 100px;">
                                Type
                            </th>
                            <th style="padding: 0.75rem; text-align: center; border-bottom: 1px solid rgba(148, 163, 184, 0.2); width: 150px;">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody id="fileListBody">
                        <tr>
                            <td colspan="3" style="padding: 1rem; text-align: center; color: var(--gray-500);">
                                <i class="fas fa-spinner fa-spin"></i> Loading...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Selected Path Display -->
            <div style="margin-top: 1rem; padding: 0.75rem; background: rgba(96, 165, 250, 0.1); border-radius: 0.375rem; border-left: 3px solid rgba(96, 165, 250, 0.5);">
                <small style="color: var(--gray-600);">Selected Path:</small>
                <div id="selectedPathDisplay" style="font-family: 'Monaco', 'Courier New', monospace; color: var(--blue-400); font-size: 0.9rem; margin-top: 0.25rem; word-break: break-all;">
                    /mnt
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeFileBrowserModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="selectPathAndCreate()">
                <i class="fas fa-check"></i> Select & Create Storage
            </button>
            <!-- Test button for debugging -->
            <button type="button" class="btn btn-warning" onclick="testFullModalFlow();" id="debugTestButton">
                🧪 TEST: Show Modal with Test Data
            </button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>

<style>
/* ========================================
   SANGFOR-Inspired Storage UI with Silo Theme
   ======================================== */

/* Storage Tabs Navigation */
.storage-tabs {
    display: flex;
    background: rgba(255, 255, 255, 0.05);
    border-bottom: 2px solid rgba(102, 126, 234, 0.3);
    padding: 0;
    margin: 0;
}

.storage-tab {
    padding: 1rem 1.5rem;
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9375rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    border-bottom: 3px solid transparent;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.storage-tab:hover {
    background: rgba(102, 126, 234, 0.1);
    color: rgba(255, 255, 255, 0.9);
}

.storage-tab.active {
    background: rgba(102, 126, 234, 0.15);
    color: white;
    border-bottom-color: #667eea;
}

.storage-tab i {
    font-size: 1rem;
}

/* Tab Content */
.tab-content {
    display: none;
    animation: fadeIn 0.3s ease;
}

.tab-content.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Storage Summary Header */
.storage-summary-header,
.storage-list-header {
    padding: 1.5rem 2rem;
    background: rgba(255, 255, 255, 0.03);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
    margin: 0;
}

.storage-list-title {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.storage-list-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
}

.action-btn {
    padding: 0.625rem 1.25rem;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 6px;
    color: white;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.action-btn:hover {
    background: rgba(102, 126, 234, 0.3);
    border-color: rgba(102, 126, 234, 0.5);
}

.action-btn.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: transparent;
}

.action-btn.btn-primary:hover {
    background: linear-gradient(135deg, #5568d3 0%, #653a8c 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* Storage List Filters */
.storage-list-filters {
    padding: 1rem 2rem;
    background: rgba(255, 255, 255, 0.02);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.filter-search {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.search-input {
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 6px;
    color: white;
    font-size: 0.875rem;
    min-width: 250px;
}

.search-input::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

.search-btn {
    padding: 0.5rem 1rem;
    background: rgba(102, 126, 234, 0.3);
    border: 1px solid rgba(102, 126, 234, 0.5);
    border-radius: 6px;
    color: white;
    cursor: pointer;
    transition: all 0.2s ease;
}

.search-btn:hover {
    background: rgba(102, 126, 234, 0.5);
}

.filter-advanced {
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 6px;
    color: white;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-advanced:hover {
    background: rgba(255, 255, 255, 0.12);
}

/* Remove previous VMware styles padding */
.main-content {
    padding: 0 !important;
}

/* Table Styles - SANGFOR Inspired */
.vmware-table-container {
    background: rgba(255, 255, 255, 0.03);
    border: none;
    border-radius: 0;
    overflow: hidden;
}

.vmware-table-header {
    display: none; /* Hide old header */
}

.vmware-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0 auto;
}

.vmware-table thead {
    background: #f9fafb;
    border-bottom: 2px solid #e5e7eb;
}

.vmware-table th {
    padding: 1rem;
    text-align: center;
    font-size: 0.875rem;
    font-weight: 600;
    color: #111827;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    border-bottom: 2px solid #e5e7eb;
}

.vmware-table td {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    color: #111827;
    font-size: 0.875rem;
    vertical-align: middle;
    text-align: center;
}

.vmware-table tbody tr {
    position: relative;
    transition: background 0.2s ease;
    background: #ffffff;
}

.vmware-table tbody tr:hover {
    background: #f3f4f6;
}

.vmware-table tbody tr::before {
    display: none; /* Remove left border */
}

/* Column Specific Styles - All centered */
.col-checkbox {
    width: 40px;
    text-align: center !important;
}

.col-status {
    text-align: center !important;
}

.col-status > * {
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    width: 100% !important;
}

.col-type {
    text-align: center !important;
}

.col-node {
    text-align: center !important;
}

.col-checkbox input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.col-status {
    width: 120px;
    text-align: center !important;
}

.status-indicator {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: transparent !important;
    background: none !important;
    padding: 0 !important;
    border: none !important;
    box-shadow: none !important;
}

.status-indicator::before,
.status-indicator::after {
    display: none !important;
}

.status-indicator.online {
    color: transparent !important;
    background: none !important;
    box-shadow: none !important;
}

.status-indicator.online i {
    color: #10b981 !important;
    background: none !important;
    box-shadow: none !important;
}

.status-indicator.offline {
    color: transparent !important;
    background: none !important;
    box-shadow: none !important;
}

.status-indicator.offline i {
    color: #ef4444 !important;
    background: none !important;
    box-shadow: none !important;
}

.col-name {
    min-width: 200px;
    text-align: left !important;
}

.storage-name-link {
    color: #2563eb;
    text-decoration: none;
    font-weight: 600;
}

.storage-name-link:hover {
    color: #1d4ed8;
    text-decoration: underline;
    text-decoration: underline;
}

.col-type,
.col-node {
    width: 120px;
}

.storage-type-label,
.node-label {
    color: #111827 !important;
    font-size: 0.875rem;
    font-weight: 500;
}

.col-capacity,
.col-available,
.col-usage {
    width: 120px;
    text-align: center !important;
}

.usage-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    align-items: center;
}

.usage-value {
    font-size: 0.875rem;
    color: #111827 !important;
    font-weight: 600;
}

.usage-bar-mini {
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
    width: 80px;
}

.usage-bar-fill-mini {
    height: 100%;
    transition: width 0.3s ease;
    border-radius: 3px;
}

.usage-bar-fill-mini.normal {
    background: #3b82f6 !important;
}

.usage-bar-fill-mini.warning {
    background: #f59e0b !important;
}

.usage-bar-fill-mini.critical {
    background: #ef4444 !important;
}

/* Override any green colors in progress bars */
.storage-page .usage-bar-fill-mini {
    background: #3b82f6 !important;
}

.storage-page .usage-bar-fill-mini.warning {
    background: #f59e0b !important;
}

.storage-page .usage-bar-fill-mini.critical {
    background: #ef4444 !important;
}

.col-content {
    display: none !important;
}

.content-badges-mini {
    display: none;
}

.content-badge-mini {
    display: none;
}

.content-badge-mini.iso {
    display: none;
}

.col-actions {
    width: 150px;
    text-align: center;
}

.action-buttons-mini {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.btn-icon {
    width: 32px;
    height: 32px;
    padding: 0;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    color: #4b5563;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-icon:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
    color: #111827;
}

.btn-icon.primary {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #ffffff;
}

.btn-icon.primary:hover {
    background: #2563eb;
    border-color: #2563eb;
}

.btn-icon.danger {
    color: #ef4444;
}

.btn-icon.danger:hover {
    background: #fef2f2;
    border-color: #fecaca;
    color: #fca5a5;
}

/* Empty State */
.vmware-empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-state-icon {
    font-size: 4rem;
    color: rgba(255, 255, 255, 0.2);
    margin-bottom: 1.5rem;
}

.empty-state-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: white;
    margin-bottom: 0.75rem;
}

.empty-state-description {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.6);
    margin-bottom: 2rem;
}

/* Text utilities */
.text-center {
    text-align: center;
    padding: 2rem;
    color: rgba(255, 255, 255, 0.6);
}

/* Dropdown */
.dropdown {
    position: relative;
}

/* Remove old VMware heavy styling */
:root {
    --vmware-blue: #0091da;
    --vmware-dark-blue: #0073b1;
    --vmware-light-blue: #e8f4f8;
    --vmware-navy: #003d5c;
    --vmware-gray: #5a6872;
    --vmware-light-gray: #f5f5f5;
    --vmware-border: #d1d5d8;
    --vmware-green: #10b981;
    --vmware-yellow: #f59e0b;
    --vmware-red: #ef4444;
    --vmware-purple: #8b5cf6;
}

/* Remove gaps and make full width */
.main-content {
    padding: 0 !important;
}

/* Page Header - VMware Style */
.vmware-page-header {
    background: white;
    border-bottom: 2px solid var(--vmware-border);
    padding: 1rem 1.5rem;
    margin: 0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.page-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--vmware-navy);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.page-title i {
    color: var(--vmware-blue);
    font-size: 1.75rem;
}

.page-actions {
    display: flex;
    gap: 0.75rem;
}

/* Success button variant */
.vmware-btn-success {
    background: var(--vmware-green);
    border-color: var(--vmware-green);
    color: white;
}

.vmware-btn-success:hover {
    background: #059669;
    border-color: #059669;
}

/* Storage Overview Cards - VMware Style */
.vmware-storage-overview {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0;
    border-bottom: 1px solid var(--vmware-border);
    background: white;
}

@media (max-width: 1400px) {
    .vmware-storage-overview {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .vmware-storage-overview {
        grid-template-columns: 1fr;
    }
}

.vmware-card {
    background: white;
    border-right: 1px solid var(--vmware-border);
    border-radius: 0;
    padding: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    transition: all 0.2s ease;
    position: relative;
}

.vmware-card:last-child {
    border-right: none;
}

.vmware-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: transparent;
    transition: background 0.2s ease;
}

.vmware-card:hover::before {
    background: var(--vmware-blue);
}

.vmware-card:hover {
    background: linear-gradient(to right, rgba(0, 145, 218, 0.03) 0%, white 100%);
}

.vmware-card.storage-summary:hover::before {
    background: var(--vmware-blue);
}

.vmware-card.storage-capacity:hover::before {
    background: var(--vmware-purple);
}

.vmware-card.storage-usage:hover::before {
    background: var(--vmware-yellow);
}

.vmware-card.storage-health:hover::before {
    background: var(--vmware-green);
}

.vmware-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    background: linear-gradient(135deg, var(--vmware-blue) 0%, var(--vmware-dark-blue) 100%);
    color: white;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 145, 218, 0.25);
}
.vmware-card-icon.capacity {
    background: linear-gradient(135deg, var(--vmware-purple) 0%, #7c3aed 100%);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);
}

.vmware-card-icon.usage {
    background: linear-gradient(135deg, var(--vmware-yellow) 0%, #ea580c 100%);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
}

.vmware-card-icon.health {
    background: linear-gradient(135deg, var(--vmware-green) 0%, #059669 100%);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
}

.vmware-card-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.vmware-metric-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--vmware-navy);
    line-height: 1;
    margin-bottom: 0.25rem;
    letter-spacing: -0.3px;
}

.vmware-metric-value .unit {
    font-size: 1.25rem;
    font-weight: 400;
    color: var(--vmware-gray);
    margin-left: 0.25rem;
}

.vmware-metric-label {
    font-size: 0.875rem;
    color: var(--vmware-gray);
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.vmware-metric-sublabel {
    font-size: 0.75rem;
    color: #9ca3af;
}

.capacity-breakdown {
    display: flex;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: var(--vmware-gray);
    margin-top: 0.5rem;
}

.capacity-breakdown .used {
    color: var(--vmware-blue);
}

.capacity-breakdown .free {
    color: var(--vmware-green);
}

.capacity-breakdown .separator {
    color: var(--vmware-border);
}

.vmware-progress-bar {
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
    margin-top: 0.75rem;
}

.vmware-progress-fill {
    height: 100%;
    transition: width 0.3s ease;
    border-radius: 3px;
}

.vmware-progress-fill.normal {
    background: linear-gradient(90deg, var(--vmware-green) 0%, #059669 100%);
}

.vmware-progress-fill.warning {
    background: linear-gradient(90deg, var(--vmware-yellow) 0%, #f97316 100%);
}

.vmware-progress-fill.critical {
    background: linear-gradient(90deg, var(--vmware-red) 0%, #dc2626 100%);
}

.health-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.25rem;
}

.health-status.healthy {
    color: var(--vmware-green);
}

.health-status i {
    font-size: 1.5rem;
}

/* VMware Table Container */
.vmware-table-container {
    background: white;
    border: none;
    border-top: 1px solid var(--vmware-border);
    border-radius: 0;
    overflow: hidden;
    box-shadow: none;
}
.vmware-table-header {
    padding: 1.25rem 1.5rem;
    background: linear-gradient(to bottom, #fafafa 0%, #f5f5f5 100%);
    border-bottom: 2px solid var(--vmware-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.vmware-table-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--vmware-navy);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
}

.vmware-table-title i {
    color: var(--vmware-blue);
}

.vmware-table-actions {
    display: flex;
    gap: 0.5rem;
}

.vmware-table-body {
    padding: 0;
}

/* VMware Table Styles */
.vmware-table {
    width: 100%;
    border-collapse: collapse;
}

.vmware-table thead {
    background: linear-gradient(to bottom, #fafafa 0%, #f5f5f5 100%);
    border-bottom: 2px solid var(--vmware-border);
}

.vmware-table th {
    padding: 1rem 1rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--vmware-gray);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.vmware-table td {
    padding: 1.25rem 1rem;
    border-bottom: 1px solid #f0f0f0;
    color: var(--vmware-navy);
    font-size: 0.875rem;
    vertical-align: middle;
}

.vmware-table tbody tr {
    position: relative;
}

.vmware-table tbody tr::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: transparent;
    transition: background 0.2s ease;
}

.vmware-table tbody tr:hover {
    background: linear-gradient(to right, rgba(0, 145, 218, 0.05) 0%, white 100%);
}

.vmware-table tbody tr:hover::before {
    background: var(--vmware-blue);
}

.vmware-table tbody tr:hover {
    background-color: var(--vmware-light-blue);
}

.vmware-table tbody tr.disabled {
    opacity: 0.5;
    background-color: #fafafa;
}

/* Table Column Widths */
.col-icon {
    width: 50px;
    text-align: center;
}

.col-name {
    min-width: 200px;
}

.col-type {
    width: 120px;
}

.col-node {
    width: 150px;
}

.col-capacity {
    width: 150px;
}

.col-usage {
    width: 180px;
}

.col-content {
    width: 150px;
}

.col-actions {
    width: 150px;
    text-align: right;
}

/* Storage Type Icon */
.storage-type-icon {
    width: 40px;
    height: 40px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    background: linear-gradient(135deg, var(--vmware-blue) 0%, var(--vmware-dark-blue) 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(0, 145, 218, 0.2);
}

.storage-type-icon.lvm,
.storage-type-icon.lvmthin {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    box-shadow: 0 2px 8px rgba(139, 92, 246, 0.2);
}

.storage-type-icon.nfs,
.storage-type-icon.cifs {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
}

.storage-type-icon.zfspool {
    background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.2);
}

/* Storage Name */
.storage-name {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.name-text {
    font-weight: 700;
    color: var(--vmware-navy);
    font-size: 0.9375rem;
}

.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-disabled {
    background: #fee2e2;
    color: #991b1b;
}

/* Storage Type Badge */
.storage-type-badge {
    display: inline-block;
    padding: 0.375rem 0.875rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    background: var(--vmware-light-blue);
    color: var(--vmware-dark-blue);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 1px solid rgba(0, 145, 218, 0.2);
}

.storage-type-badge.lvm,
.storage-type-badge.lvmthin {
    background: #f3e8ff;
    color: #7c3aed;
    border-color: rgba(139, 92, 246, 0.2);
}

.storage-type-badge.nfs,
.storage-type-badge.cifs {
    background: #d1fae5;
    color: #065f46;
    border-color: rgba(16, 185, 129, 0.2);
}

.storage-type-badge.zfspool {
    background: #fef3c7;
    color: #92400e;
    border-color: rgba(245, 158, 11, 0.2);
}

/* Node Badge */
.node-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    border: 1px solid var(--vmware-border);
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--vmware-navy);
}

.node-badge i {
    color: var(--vmware-blue);
    font-size: 0.75rem;
}

/* Capacity Info */
.capacity-info {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.capacity-value {
    font-weight: 700;
    color: var(--vmware-navy);
    font-size: 0.9375rem;
}

.capacity-detail {
    font-size: 0.75rem;
    color: var(--vmware-gray);
    font-weight: 500;
}

/* Usage Info */
.usage-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.usage-bar-container {
    flex: 1;
    height: 10px;
    background: #e5e7eb;
    border-radius: 5px;
    overflow: hidden;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
}

.usage-bar-fill {
    height: 100%;
    transition: width 0.3s ease;
    border-radius: 5px;
}

.usage-bar-fill.normal {
    background: linear-gradient(90deg, var(--vmware-green) 0%, #059669 100%);
}

.usage-bar-fill.warning {
    background: linear-gradient(90deg, var(--vmware-yellow) 0%, #f97316 100%);
}

.usage-bar-fill.critical {
    background: linear-gradient(90deg, var(--vmware-red) 0%, #dc2626 100%);
}

.usage-text {
    font-weight: 700;
    color: var(--vmware-navy);
    min-width: 50px;
    text-align: right;
    font-size: 0.9375rem;
}

/* Content Badges */
.content-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.content-badge {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    color: var(--vmware-gray);
    font-size: 0.9375rem;
    transition: all 0.2s ease;
    cursor: help;
    border: 1px solid transparent;
}

.content-badge:hover {
    background: var(--vmware-light-blue);
    color: var(--vmware-dark-blue);
    transform: scale(1.15);
    border-color: var(--vmware-blue);
    box-shadow: 0 2px 8px rgba(0, 145, 218, 0.2);
}

.content-badge.iso {
    background: var(--vmware-light-blue);
    color: var(--vmware-blue);
    border-color: rgba(0, 145, 218, 0.2);
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.vmware-btn-action {
    width: 36px;
    height: 36px;
    border: 1px solid var(--vmware-border);
    background: white;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: var(--vmware-gray);
    font-size: 0.875rem;
}

.vmware-btn-action:hover {
    background: var(--vmware-light-blue);
    border-color: var(--vmware-blue);
    color: var(--vmware-blue);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 145, 218, 0.15);
}

.vmware-btn-action.primary {
    background: var(--vmware-blue);
    border-color: var(--vmware-blue);
    color: white;
    box-shadow: 0 2px 6px rgba(0, 145, 218, 0.25);
}

.vmware-btn-action.primary:hover {
    background: var(--vmware-dark-blue);
    border-color: var(--vmware-dark-blue);
    box-shadow: 0 4px 12px rgba(0, 145, 218, 0.35);
}

.vmware-btn-action.danger:hover {
    background: #fee2e2;
    border-color: var(--vmware-red);
    color: var(--vmware-red);
    box-shadow: 0 4px 8px rgba(239, 68, 68, 0.15);
}

/* VMware Buttons */
.vmware-btn {
    padding: 0.625rem 1.25rem;
    border: 1px solid var(--vmware-border);
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.vmware-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.vmware-btn:active {
    transform: translateY(0);
}

.vmware-btn-primary {
    background: var(--vmware-blue);
    border-color: var(--vmware-blue);
    color: white;
}

.vmware-btn-primary:hover {
    background: var(--vmware-dark-blue);
    border-color: var(--vmware-dark-blue);
    box-shadow: 0 4px 12px rgba(0, 145, 218, 0.25);
}

.vmware-btn-secondary {
    background: white;
    border-color: var(--vmware-border);
    color: var(--vmware-gray);
}

.vmware-btn-secondary:hover {
    background: var(--vmware-light-gray);
    border-color: var(--vmware-blue);
    color: var(--vmware-blue);
}

/* Empty State */
.vmware-empty-state {
    text-align: center;
    padding: 5rem 2rem;
}

.empty-state-icon {
    font-size: 5rem;
    color: var(--vmware-border);
    margin-bottom: 1.5rem;
    opacity: 0.3;
}

.empty-state-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--vmware-navy);
    margin-bottom: 0.75rem;
}

.empty-state-description {
    font-size: 1rem;
    color: var(--vmware-gray);
    margin-bottom: 2.5rem;
}

/* Storage Content Viewer - Modal Styles */
.storage-content-viewer {
    text-align: left;
}

.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 2px solid var(--vmware-border);
    margin-bottom: 1rem;
}

.storage-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.storage-info i {
    color: var(--vmware-blue);
    font-size: 1.5rem;
}

.storage-info .storage-name {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--vmware-navy);
}

.content-stats {
    font-size: 0.875rem;
    color: var(--vmware-gray);
}

.content-table-wrapper {
    max-height: 500px;
    overflow-y: auto;
}

.content-table {
    width: 100%;
    border-collapse: collapse;
}

.content-table thead {
    position: sticky;
    top: 0;
    background: #fafafa;
    z-index: 1;
}

.content-table th {
    padding: 0.75rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--vmware-gray);
    text-transform: uppercase;
    border-bottom: 2px solid var(--vmware-border);
}

.content-table td {
    padding: 0.75rem;
    border-bottom: 1px solid #f0f0f0;
    font-size: 0.875rem;
}

.content-table tbody tr:hover {
    background: var(--vmware-light-blue);
}

.type-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
}

.type-badge.iso {
    background: var(--vmware-light-blue);
    color: var(--vmware-dark-blue);
}

.type-badge.images {
    background: #f3e8ff;
    color: #7c3aed;
}

.type-badge.vztmpl {
    background: #dbeafe;
    color: #1e40af;
}

.type-badge.backup {
    background: #d1fae5;
    color: #065f46;
}

.storage-content-viewer .action-btn {
    width: 28px;
    height: 28px;
    border: 1px solid var(--vmware-border);
    background: white;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: var(--vmware-gray);
}

.storage-content-viewer .action-btn:hover {
    background: var(--vmware-light-blue);
    border-color: var(--vmware-blue);
    color: var(--vmware-blue);
}

.storage-content-viewer .action-btn.delete:hover {
    background: #fee2e2;
    border-color: var(--vmware-red);
    color: var(--vmware-red);
}

/* SweetAlert2 Custom Styles */
.storage-content-popup {
    border-radius: 4px !important;
}

.storage-content-html {
    padding: 0 !important;
}

/* Original Styles - Keep for compatibility */
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.btn-sm + .btn-sm {
    margin-left: 0.25rem;
}

.dropdown {
    position: relative;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    min-width: 280px;
    margin-top: 0.5rem;
    background: white;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-2xl);
    border: 1px solid rgba(102, 126, 234, 0.1);
    max-height: 400px;
    overflow-y: auto;
}

.dropdown-item {
    display: block;
    padding: 0.75rem 1rem;
    color: var(--gray-700);
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
    border-bottom: 1px solid var(--gray-100);
}

.dropdown-item:last-child {
    border-bottom: none;
}

.dropdown-item:hover {
    background: linear-gradient(90deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.05) 100%);
    color: var(--blue-600);
    padding-left: 1.25rem;
}

.dropdown-item i {
    margin-right: 0.75rem;
    width: 20px;
    text-align: center;
    color: var(--blue-500);
}

.modal-lg {
    max-width: 900px;
}

.detected-storage-section {
    margin: 1.5rem 0;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: var(--border-radius);
    border: 1px solid var(--gray-200);
}

.detected-storage-section h4 {
    margin-bottom: 1rem;
    color: var(--gray-700);
    font-size: 1rem;
    font-weight: 600;
}

.storage-list {
    display: grid;
    gap: 0.75rem;
}

.storage-item {
    padding: 1rem;
    background: white;
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
    border-color: var(--blue-500);
    background: linear-gradient(90deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.05) 100%);
}

.storage-item-info {
    flex: 1;
}

.storage-item-name {
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
}

.storage-item-details {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.detected-items {
    display: grid;
    gap: 0.75rem;
}

.detected-item {
    padding: 1rem;
    border: 2px solid rgba(148, 163, 184, 0.2);
    border-radius: var(--border-radius-lg);
    background: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.detected-item:hover {
    border-color: var(--blue-500);
    background: rgba(102, 126, 234, 0.05);
    transform: translateX(4px);
}

.detected-item.selected {
    border-color: var(--blue-500);
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.12) 0%, rgba(118, 75, 162, 0.08) 100%);
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
}

.detected-item-info {
    flex: 1;
}

.detected-item-name {
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
    font-size: 0.95rem;
}

.detected-item-details {
    font-size: 0.8rem;
    color: var(--gray-600);
}

.detected-item-badge {
    padding: 0.25rem 0.75rem;
    border-radius: var(--border-radius-full);
    font-size: 0.75rem;
    font-weight: 600;
    background: rgba(16, 185, 129, 0.15);
    color: #047857;
}

.storage-item-action {
    margin-left: 1rem;
}

.network-storage-fields {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Progress bar pulse animation */
@keyframes progressPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.progress-pulse {
    animation: progressPulse 1.5s ease-in-out infinite;
}

/* Modal Fixes - Override any conflicting styles */
#addStorageModal,
#uploadISOModal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    z-index: 9999 !important;
}

#addStorageModal.active,
#uploadISOModal.active {
    display: flex !important;
}

#addStorageModal .modal-content,
#uploadISOModal .modal-content {
    position: relative;
    background: white;
    border-radius: var(--border-radius-xl);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    animation: modalSlideIn 0.3s ease;
    z-index: 10000 !important;
}

#addStorageModal .modal-content.modal-lg {
    max-width: 900px;
}

/* Upload ISO Modal - ขนาดพอดี */
#uploadISOModal .modal-content.modal-upload {
    max-width: 600px;
    width: auto;
    min-width: 500px;
}

/* File input styling - text สีดำ */
.file-input,
.file-input::file-selector-button {
    color: #000 !important;
}

input[type="file"].form-control {
    color: #1f2937 !important;
    padding: 0.5rem;
}

input[type="file"].form-control::file-selector-button {
    background: var(--blue-500);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius);
    cursor: pointer;
    margin-right: 1rem;
    font-weight: 500;
}

input[type="file"].form-control::file-selector-button:hover {
    background: var(--blue-600);
}

/* Storage Type Selector - Radio Button Cards */
.storage-type-selector {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.type-radio-label {
    cursor: pointer;
    display: block;
}

.type-radio {
    display: none;
}

.type-card {
    padding: 1.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    text-align: center;
    transition: all 0.3s ease;
    background: #ffffff;
}

.type-card:hover {
    border-color: #d1d5db;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.type-card i {
    font-size: 2.5rem;
    color: #667eea;
    display: block;
    margin-bottom: 0.75rem;
}

.type-card h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
    margin: 0.5rem 0;
}

.type-card p {
    font-size: 0.85rem;
    color: #6b7280;
    margin: 0;
}

.type-radio:checked + .type-card {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.05) 100%);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.type-radio:checked + .type-card i {
    color: #667eea;
    transform: scale(1.1);
}

/* Hide unnecessary storage type fields */
#fields-lvm,
#fields-lvmthin,
#fields-zfspool,
#fields-cifs,
#fields-iscsi,
#fields-glusterfs {
    display: none !important;
}

/* Select multiple styling for Content Types */
select[multiple].form-control {
    padding: 0.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: #ffffff;
    color: #111827;
    font-size: 0.95rem;
}

select[multiple].form-control option {
    padding: 0.75rem 0.5rem;
    border-radius: 4px;
    margin: 2px 0;
    cursor: pointer;
}

select[multiple].form-control option:hover {
    background: #f3f4f6;
}

select[multiple].form-control option:checked {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
}

select[multiple].form-control:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Step visibility transitions */
#step2 {
    transition: all 0.3s ease;
    max-height: 1000px;
    opacity: 1;
}

#step2[style*="display: none"] {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
}

kbd {
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    padding: 2px 6px;
    font-size: 0.85em;
    font-family: monospace;
    color: #374151;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
/* UI overrides: remove left gap and ensure dark text in content areas */
/* With panel removed, ensure main-content spans full width (override theme.css margin-left:280px) */
.storage-page .main-content { 
    margin-left: 0 !important; 
    margin-right: 0 !important;
    border-left: none; 
    box-shadow: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    padding-top: 0 !important;
    margin-top: 0 !important;
}
.storage-tabs { 
    background: #ffffff; 
    border-bottom: 1px solid #e5e7eb;
    width: 100%;
    max-width: 1680px;
    margin-left: auto;
    margin-right: auto;
    margin-top: 0;
    padding-top: 1rem;
}
.storage-tab { color: #111827; font-weight: 600; padding: 0.5rem 0.75rem; font-size: 0.85rem; }
.storage-tab:hover { background: #f3f4f6; color: #111827; }
.storage-tab.active { background: #ffffff; color: #111827; border-bottom-color: #667eea; }
.storage-tab i { color: #667eea; }
.storage-summary-header, .storage-list-header, .storage-list-filters { 
    padding-left: 0.75rem; 
    padding-right: 0.75rem; 
    background: #ffffff; 
    width: 100%;
    max-width: 1680px;
    margin-left: auto;
    margin-right: auto;
}
.tab-content { 
    background: #ffffff; 
    color: #111827; 
    padding-left: 0.75rem; 
    padding-right: 0.75rem; 
    padding-top: 0.75rem;
    width: 100%;
    max-width: 1680px;
    margin-left: auto;
    margin-right: auto;
}
.vmware-storage-overview, .vmware-table-container { 
    background: #ffffff;
    width: 100%;
    max-width: 1680px;
    margin-left: auto;
    margin-right: auto;
}
.vmware-card-content, .vmware-card .vmware-metric-label, .vmware-card .vmware-metric-sublabel { color: #374151; }
.vmware-metric-value { color: #111827; }
.section-title { color: #111827; }
/* Table text colors - Force black text everywhere */
.vmware-table thead { background: #f9fafb; }
.vmware-table th, .vmware-table td { color: #111827 !important; }
.vmware-table th *, .vmware-table td * { color: #111827 !important; }
.storage-page .vmware-table-header .vmware-table-title { color: #111827 !important; }
.storage-page .vmware-table a.storage-name-link { color: #2563eb !important; font-weight: 600; }
.storage-page .vmware-table .usage-value { color: #111827 !important; }
.storage-page .vmware-table .btn-icon { color: #4b5563 !important; }
.storage-page .vmware-table .storage-type-label { color: #111827 !important; }
.storage-page .vmware-table .node-label { color: #111827 !important; }
.storage-page .vmware-table .status-indicator { color: #111827 !important; }
.vmware-table tbody tr:hover { background: #f3f4f6; }
.vmware-table tbody tr { background: #ffffff; }
/* Status indicators: black text, colored icons */
.status-indicator { color: #111827; }
.status-indicator.online i { color: #10b981; }
.status-indicator.offline i { color: #ef4444; }
/* Links */
.storage-name-link { color: #111827; }
.storage-name-link:hover { color: #000000; }
/* Inputs and buttons in filter bar */
.storage-list-filters { background: #ffffff; border-bottom: 1px solid #e5e7eb; }
.filter-search .search-input { background: #ffffff; border: 1px solid #e5e7eb; color: #111827; }
.filter-search .search-input::placeholder { color: #9ca3af; }
.filter-search .search-btn { background: #111827; border-color: #111827; color: #ffffff; }
/* Action buttons */
.action-btn { background: #ffffff; border: 1px solid #e5e7eb; color: #111827; }
.action-btn:hover { background: #f3f4f6; border-color: #cbd5e1; }
.action-btn.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; border-color: transparent; }
/* Icon buttons in table */
.btn-icon { background: #ffffff; border: 1px solid #e5e7eb; color: #111827; }
.btn-icon:hover { background: #f3f4f6; border-color: #cbd5e1; color: #111827; }
/* Minimize left internal offsets */
.vmware-table, .vmware-storage-overview { margin-left: 0; }
/* Beautify blocks (cards and containers) */
.vmware-storage-overview { gap: 1rem; border-bottom: none; background: transparent; margin: 1rem 0; }
.vmware-card { border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); border-right: none; }
.vmware-table-container { padding: 1rem 1.25rem; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); margin: 1rem 0; }
.storage-page .section-title { font-size: 1.32rem; }
.storage-page .vmware-storage-overview { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; }
.storage-page .vmware-card { padding: 0.75rem; min-height: 108px; }
.storage-page .vmware-metric-value { font-size: 1.5rem; }
.storage-page .vmware-card .vmware-metric-label, .storage-page .vmware-card .vmware-metric-sublabel { font-size: 1.14rem; }
.storage-page .vmware-table th, .storage-page .vmware-table td { 
    padding: 0.8rem; 
    font-size: 1.14rem; 
    line-height: 1.45;
    color: #111827 !important;
    text-align: center !important;
}
.storage-page .vmware-table .col-name { text-align: left !important; }
.storage-page .vmware-table tbody tr { height: 46px; }
/* Force all text inside table to be black */
.storage-page .vmware-table th *,
.storage-page .vmware-table td *,
.storage-page .vmware-table span,
.storage-page .vmware-table div {
    color: #111827 !important;
}
.storage-page .vmware-table .status-indicator.online i { color: #10b981 !important; }
.storage-page .vmware-table .status-indicator.offline i { color: #ef4444 !important; }
.storage-page .vmware-table a.storage-name-link { color: #2563eb !important; }
.storage-page .vmware-table .btn-icon { color: #4b5563 !important; }
.storage-page .vmware-table .btn-icon.primary { color: #ffffff !important; }
.storage-page .vmware-table .btn-icon.danger { color: #ef4444 !important; }
/* Smaller icons in cards and buttons when scaled down */
.storage-page .vmware-card-icon { width: 44px; height: 44px; font-size: 1.35rem; }
.storage-page .vmware-table .btn-icon { padding: 0.25rem 0.35rem; font-size: 0.8rem; }
/* Constrain content width for cleaner look */
.storage-page .tab-content, .storage-page .storage-summary-header { max-width: 1680px; margin-left: auto; margin-right: auto; }
/* Storage-only compact sizing to avoid overflow */
.storage-page .control-panel { display: none; }
.storage-page .storage-tab { padding: 0.68rem 1rem; font-size: 1.2rem; position: relative; border-bottom-width: 3px; }
.storage-page .tab-content { padding-left: 0.75rem; padding-right: 0.75rem; padding-top: 0.75rem; }
.storage-page .tab-content { min-height: calc(100vh - 160px); }
/* Make Datastores block stretch closer to footer on this page */
.storage-page #tab-datastores { min-height: calc(100vh - 40px); }
.storage-page .vmware-storage-overview { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; }
.storage-page .vmware-card { padding: 0.75rem; min-height: 108px; }
.storage-page .vmware-table-container { padding: 1rem 1rem; overflow-x: auto; }
/* Storage panel compact typography and spacing */
.storage-page .control-panel .panel-header { padding: 0.5rem 0.6rem; }
.storage-page .control-panel .panel-title { font-size: 0.95rem; }
.storage-page .control-panel .panel-content { padding: 0.5rem; }
.storage-page .control-panel .panel-section { padding: 0.5rem; margin-bottom: 0.5rem; }
.storage-page .control-panel .section-title { font-size: 0.85rem; margin: 0 0 0.4rem; }
.storage-page .control-panel .status-item { padding: 0.25rem 0; font-size: 0.85rem; }
.storage-page .control-panel .status-label { font-size: 0.85rem; }
.storage-page .control-panel .status-value { font-size: 0.85rem; }
.storage-page .control-panel .metric-item { margin: 0.35rem 0; font-size: 0.85rem; }
.storage-page .control-panel .metric-header .metric-label { font-size: 0.85rem; }
.storage-page .control-panel .metric-header .metric-value { font-size: 0.95rem; }
.storage-page .control-panel .metric-bar { height: 6px; border-radius: 4px; }
.storage-page .control-panel .network-stats .stat-item { gap: 0.5rem; }
.storage-page .control-panel .network-stats .stat-label,
.storage-page .control-panel .network-stats .stat-value { font-size: 0.85rem; }
.storage-page .control-panel .quick-actions { gap: 0.4rem; }
.storage-page .control-panel .quick-actions .action-btn { padding: 0.35rem 0.5rem; font-size: 0.8rem; gap: 0.4rem; }
.storage-page .control-panel .quick-actions .action-btn i { font-size: 0.85rem; }
/* Global scale: increase by 20% more (0.678 × 1.2 = 0.8136) */
.storage-page .main-wrapper { zoom: 0.976; }
@supports not (zoom: 1) {
    .storage-page .main-wrapper { 
        transform: scale(0.976); 
        transform-origin: top left; 
        width: calc(100% / 0.976);
    }
}
/* Topnav: increase height and normalize active menu styling */
.storage-page .top-nav { 
    height: 48px; 
    border-bottom: none; 
    box-shadow: none; 
    backdrop-filter: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
}
body.has-topnav.storage-page { padding-top: 60px !important; }
body.has-topnav.storage-page { padding-bottom: 56px !important; }
.storage-page .main-wrapper {
    margin-top: 60px !important;
}
.storage-page .main-content { margin-bottom: 0; background: #ffffff !important; }
.storage-page .nav-container { max-width: 100%; margin: 0; padding: 0 1rem; border-bottom: 1px solid #e5e7eb; height: 48px; display: flex; align-items: center; }
.storage-page .nav-logo { height: 28px; }
.storage-page .nav-brand-text { font-size: 1.1rem; }
.storage-page .brand-subtitle { display: none; }
.storage-page .nav-menu { gap: 0.25rem; }
.storage-page .nav-link { padding: 0 0.75rem; font-size: 0.95rem; height: 48px; line-height: 48px; border-radius: 0; box-shadow: none !important; }
.storage-page .nav-link i { font-size: 0.85rem; }
.storage-page .nav-icon-btn { width: 24px; height: 24px; }
.storage-page .nav-actions { gap: 0.75rem; }
.storage-page .nav-user { padding: 0 0.75rem; height: 48px; display: inline-flex; align-items: center; }
.storage-page .nav-avatar { width: 24px; height: 24px; }
.storage-page .nav-username { font-size: 0.9rem; }
/* Remove all active menu special styling - make it same as other menus */
.storage-page .nav-link.active,
.storage-page .nav-link.is-active {
    background: transparent !important;
    height: 48px; 
    line-height: 48px;
    padding-top: 0; 
    padding-bottom: 0;
    border-radius: 0 !important;
    box-shadow: none !important;
}
.storage-page .nav-item.active { 
    background: transparent !important; 
    border-color: transparent !important; 
    box-shadow: none !important; 
}
.storage-page .nav-item.active::before { 
    display: none !important; 
}
.storage-page .nav-link.active::before,
.storage-page .nav-link.active::after { display: none !important; }
.storage-page .top-nav::before,
.storage-page .top-nav::after {
    display: none !important; /* hide any theme decorative glows that extend downward */
}
/* Remove blue gradient background from body */
body.storage-page {
    background: #ffffff !important;
}
body.storage-page::before,
body.storage-page::after {
    display: none !important; /* hide gradient blobs */
}
.storage-page .main-wrapper {
    background: #ffffff !important;
}

/* Force all table text to be black - Ultimate override */
.storage-page .vmware-table,
.storage-page .vmware-table *:not(i):not(.usage-bar-fill-mini) {
    color: #111827 !important;
}

.storage-page .vmware-table a {
    color: #2563eb !important;
}

.storage-page .vmware-table .btn-icon {
    color: #4b5563 !important;
}

.storage-page .vmware-table .btn-icon.primary {
    color: #ffffff !important;
    background: #3b82f6 !important;
}

.storage-page .vmware-table .status-indicator {
    background: none !important;
    border: none !important;
    padding: 0 !important;
    box-shadow: none !important;
    color: transparent !important;
}

.storage-page .vmware-table .status-indicator *:not(i) {
    display: none !important;
}

.storage-page .vmware-table .status-indicator::before,
.storage-page .vmware-table .status-indicator::after {
    display: none !important;
}

.storage-page .vmware-table .status-indicator.online,
.storage-page .vmware-table .status-indicator.offline {
    background: none !important;
    border: none !important;
    box-shadow: none !important;
}

.storage-page .vmware-table .status-indicator i {
    background: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    margin: 0 !important;
}

.storage-page .vmware-table .status-indicator.online i {
    color: #10b981 !important;
}

.storage-page .vmware-table .status-indicator.offline i {
    color: #ef4444 !important;
}

.storage-page .vmware-table .btn-icon.danger {
    color: #ef4444 !important;
}

/* Center all columns except Name */
.storage-page .vmware-table th,
.storage-page .vmware-table td {
    text-align: center !important;
}

.storage-page .vmware-table .col-name,
.storage-page .vmware-table th.col-name {
    text-align: left !important;
}

/* NOTE: Avoid overriding global layout (.main-wrapper, .control-panel) here to prevent impacting other pages */
/* Compact summary cards and responsive columns */
.vmware-storage-overview { grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
.vmware-card { padding: 1rem; min-height: 120px; }
.vmware-card .vmware-metric-value { font-size: 1.25rem; }
.section-title { font-size: 1.125rem; }

/* Align tabs block with content width and improve active underline */
.storage-page .storage-tabs {
    max-width: 1680px;
    margin-left: auto;
    margin-right: auto;
    padding-left: 0.75rem;
    padding-right: 0.75rem;
    gap: 0.5rem;
    border-bottom: 2px solid #e5e7eb;
}
.storage-page .storage-tab.active::after {
    content: '';
    position: absolute;
    left: 0; right: 0; bottom: -1px;
    height: 3px;
    background: #667eea;
    border-radius: 2px;
}

/* IWM Footer */
.storage-page .iwm-footer {
    position: fixed;
    left: 0; right: 0; bottom: 0;
    width: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    padding: 0.75rem 0.75rem;
    z-index: 1000;
    box-shadow: 0 -2px 8px rgba(0,0,0,0.1);
}
.storage-page .iwm-footer-inner {
    max-width: 1680px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
}
.storage-page .iwm-text { display: inline-flex; align-items: baseline; gap: 0.5rem; }
.storage-page .iwm-brand { font-weight: 800; letter-spacing: 0.5px; color: #ffffff; font-size: 0.95rem; }
.storage-page .iwm-tag { color: rgba(255, 255, 255, 0.9); font-size: 0.85rem; }
.storage-page .iwm-logo svg path { fill-opacity: 1; stroke: rgba(255, 255, 255, 0.95); }

/* Modal Styling */
.modal {
    display: none !important;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
}

.modal.active {
    display: flex !important;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 41, 59, 0.95) 100%);
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 0.75rem;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
}

.modal-lg {
    max-width: 900px;
}

.modal-upload {
    max-width: 600px;
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 1.3rem;
    color: #ffffff;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.modal-close {
    background: none;
    border: none;
    color: var(--gray-400);
    font-size: 2rem;
    cursor: pointer;
    transition: color 0.2s ease;
    padding: 0;
    line-height: 1;
}

.modal-close:hover {
    color: var(--red-400);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1.5rem;
    border-top: 1px solid rgba(148, 163, 184, 0.2);
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

/* File Browser Table */
.file-browser-table {
    width: 100%;
    border-collapse: collapse;
}

.file-browser-table thead {
    background: rgba(96, 165, 250, 0.1);
    position: sticky;
    top: 0;
}

.file-browser-table th {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
    color: var(--blue-300);
    font-weight: 600;
    font-size: 0.9rem;
}

.file-browser-table tbody tr {
    border-bottom: 1px solid rgba(148, 163, 184, 0.1);
    transition: background 0.2s ease;
}

.file-browser-table tbody tr:hover {
    background: rgba(96, 165, 250, 0.05);
}

.file-browser-table td {
    padding: 0.75rem;
}

.file-browser-table a {
    color: var(--blue-400);
    text-decoration: none;
    transition: color 0.2s ease;
}

.file-browser-table a:hover {
    color: var(--blue-300);
    text-decoration: underline;
}

.btn-breadcrumb {
    background: none;
    border: none;
    color: var(--blue-400);
    cursor: pointer;
    padding: 0;
    font-size: 0.95rem;
    transition: color 0.2s ease;
}

.btn-breadcrumb:hover {
    color: var(--blue-300);
}

.btn-sm {
    padding: 0.4rem 0.75rem;
    font-size: 0.85rem;
}

.btn-danger {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: var(--red-400);
    transition: all 0.2s ease;
}

.btn-danger:hover {
    background: rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.5);
}

/* Storage Tab Navigation Styles */
.storage-tab-btn.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}

.storage-tab-btn:hover {
    color: #3b82f6;
}

.storage-tab-content {
    animation: fadeIn 0.2s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
</style>

<script>
// Fix API URL - use browser's origin instead of Docker internal hostname
const CONFIGURED_API_URL = '<?php echo "https://{$config['api']['host']}:{$config['api']['port']}{$config['api']['prefix']}"; ?>';
const API_URL = CONFIGURED_API_URL.includes('backend') ? window.location.origin + '/api/v1' : CONFIGURED_API_URL;
const storageData = <?php echo json_encode($allStorage); ?>;
const PROXMOX_NODE = '<?php echo $nodeName ?? "silo1"; ?>';

console.log('Configured API_URL:', CONFIGURED_API_URL);
console.log('Actual API_URL used:', API_URL);
console.log('Proxmox Node:', PROXMOX_NODE);

// Use the same API URL for uploads
const UPLOAD_API_URL = API_URL;
console.log('Upload API URL:', UPLOAD_API_URL);

// Function to load all ISO images from all storage (including subdirectories)
async function loadAllISOImages() {
    const isoContainer = document.getElementById('isoImagesGrid');
    
    if (!isoContainer) return;
    
    console.log('loadAllISOImages called');
    console.log('storageData:', storageData);
    
    // Show loading
    isoContainer.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #3b82f6;"></i> Loading ISO images...</div>';
    
    try {
        // Get all storage from PHP (storageData)
        if (!storageData || storageData.length === 0) {
            console.warn('No storage data available');
            isoContainer.innerHTML = '<p style="text-align: center; padding: 2rem; color: #6b7280;">No storage available</p>';
            return;
        }
        
        let allISOs = [];
        
        // Helper function to recursively search for ISO files
        async function searchForISOs(storage, currentPath = '') {
            try {
                const pathParam = currentPath ? `?path=${encodeURIComponent(currentPath)}` : '';
                const url = `${API_URL}/nodes/${storage.node}/storage/${storage.storage}/content${pathParam}`;
                console.log(`Searching: ${url}`);
                
                const response = await fetch(url, { credentials: 'include' });
                const result = await response.json();
                
                if (result.success && result.data) {
                    for (const item of result.data) {
                        // If it's an ISO file, add it
                        if (item.content === 'iso' || (item.name && item.name.toLowerCase().endsWith('.iso'))) {
                            allISOs.push({
                                ...item,
                                storage_name: storage.storage,
                                storage_node: storage.node,
                                storage_type: storage.type
                            });
                            console.log(`Found ISO: ${item.name}`);
                        }
                        
                        // If it's a directory, recursively search
                        if (item.is_dir) {
                            const subPath = item.rel_path || item.name;
                            await searchForISOs(storage, subPath);
                        }
                    }
                }
            } catch (error) {
                console.error(`Error searching in ${currentPath || 'root'}:`, error);
            }
        }
        
        // Search all storages
        for (const storage of storageData) {
            console.log(`Searching storage: ${storage.storage}`);
            await searchForISOs(storage);
        }
        
        if (allISOs.length === 0) {
            isoContainer.innerHTML = '<p style="text-align: center; padding: 2rem; color: #6b7280;">No ISO images found</p>';
            return;
        }
        
        console.log(`Total ISOs found: ${allISOs.length}`, allISOs);
        
        // Display ISOs in table format
        let tableHTML = `
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
                    <thead>
                        <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                            <th style="padding: 1rem; text-align: left; color: #374151; font-weight: 600; border-right: 1px solid #e5e7eb;">
                                <i class="fas fa-compact-disc" style="margin-right: 0.5rem; color: #8b5cf6;"></i> ISO Name
                            </th>
                            <th style="padding: 1rem; text-align: left; color: #374151; font-weight: 600; border-right: 1px solid #e5e7eb;">
                                <i class="fas fa-database" style="margin-right: 0.5rem; color: #3b82f6;"></i> Storage
                            </th>
                            <th style="padding: 1rem; text-align: center; color: #374151; font-weight: 600; border-right: 1px solid #e5e7eb;">
                                <i class="fas fa-server" style="margin-right: 0.5rem; color: #10b981;"></i> Node
                            </th>
                            <th style="padding: 1rem; text-align: center; color: #374151; font-weight: 600; border-right: 1px solid #e5e7eb;">
                                <i class="fas fa-file-archive" style="margin-right: 0.5rem; color: #f59e0b;"></i> Size
                            </th>
                            <th style="padding: 1rem; text-align: center; color: #374151; font-weight: 600;">
                                <i class="fas fa-cogs" style="margin-right: 0.5rem;"></i> Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        allISOs.forEach((iso, index) => {
            const fileName = iso.name || iso.volid || 'Unknown';
            const fileSize = iso.size ? humanizeBytes(iso.size) : 'N/A';
            const rowColor = index % 2 === 0 ? '#ffffff' : '#f9fafb';
            
            tableHTML += `
                <tr style="background: ${rowColor}; border-bottom: 1px solid #e5e7eb; transition: background 0.2s;" onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background='${rowColor}'">
                    <td style="padding: 1rem; border-right: 1px solid #e5e7eb; color: #1f2937; font-weight: 500;">
                        <i class="fas fa-compact-disc" style="margin-right: 0.5rem; color: #8b5cf6;"></i>
                        <span title="${fileName}">${fileName.length > 40 ? fileName.substring(0, 37) + '...' : fileName}</span>
                    </td>
                    <td style="padding: 1rem; border-right: 1px solid #e5e7eb; color: #1f2937;">
                        <span style="background: #e0e7ff; color: #4338ca; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.85rem; font-weight: 500;">${iso.storage_name}</span>
                    </td>
                    <td style="padding: 1rem; border-right: 1px solid #e5e7eb; text-align: center; color: #1f2937;">
                        <span style="background: #dbeafe; color: #1e40af; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.85rem; font-weight: 500;">${iso.storage_node}</span>
                    </td>
                    <td style="padding: 1rem; border-right: 1px solid #e5e7eb; text-align: right; color: #1f2937; font-weight: 500;">
                        ${fileSize}
                    </td>
                    <td style="padding: 1rem; text-align: center;">
                        <button class="vmware-btn vmware-btn-sm vmware-btn-danger delete-iso-btn" data-storage="${iso.storage_name}" data-node="${iso.storage_node}" data-filepath="${iso.rel_path || iso.name}" title="Delete ISO">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `;
        });
        
        tableHTML += `
                    </tbody>
                </table>
            </div>
        `;
        
        isoContainer.innerHTML = tableHTML;
        
        // Attach delete button handlers
        attachDeleteISOHandlers();
        
    } catch (error) {
        console.error('Error loading ISO images:', error);
        isoContainer.innerHTML = '<p style="text-align: center; padding: 2rem; color: #ef4444;">Error loading ISO images</p>';
    }
}

// Delete ISO file function
function deleteISO(storage, node, filePath) {
    Swal.fire({
        title: 'Delete ISO?',
        text: `Are you sure you want to delete this ISO file? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        heightAuto: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Call backend API to delete ISO
            fetch('/api/v1/nodes/' + node + '/storage/' + storage + '/delete-file', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    path: filePath
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'ISO file has been deleted successfully.',
                        icon: 'success',
                        confirmButtonColor: '#10b981',
                        heightAuto: true
                    }).then(() => {
                        // Reload ISO list
                        loadAllISOImages();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Failed to delete ISO file.',
                        icon: 'error',
                        confirmButtonColor: '#ef4444',
                        heightAuto: true
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to delete ISO file: ' + error.message,
                    icon: 'error',
                    confirmButtonColor: '#ef4444',
                    heightAuto: true
                });
            });
        }
    });
}

// Add event listener to delete ISO buttons after they're loaded
function attachDeleteISOHandlers() {
    document.querySelectorAll('.delete-iso-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const storage = this.getAttribute('data-storage');
            const node = this.getAttribute('data-node');
            const filepath = this.getAttribute('data-filepath');
            deleteISO(storage, node, filepath);
        });
    });
}

// Helper function to humanize bytes
function humanizeBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Tab Switching
document.querySelectorAll('.storage-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const tabName = this.dataset.tab;
        
        // Remove active class from all tabs and contents
        document.querySelectorAll('.storage-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        // Add active class to clicked tab and corresponding content
        this.classList.add('active');
        document.getElementById('tab-' + tabName).classList.add('active');
        
        // Load ISO images when switching to iso-images tab
        if (tabName === 'iso-images') {
            loadAllISOImages();
        }
    });
});

// Support deep-linking via URL hash to open a specific tab (e.g., /storage#datastores)
function activateTabFromHash() {
    const map = {
        '#summary': 'summary',
        '#datastores': 'datastores',
        '#iso': 'iso-images',
        '#iso-images': 'iso-images',
        '#content': 'content',
        '#content-browser': 'content'
    };
    const key = window.location.hash;
    const tab = map[key];
    if (tab) {
        const btn = document.querySelector(`.storage-tab[data-tab="${tab}"]`);
        if (btn) btn.click();
    }
}
window.addEventListener('hashchange', activateTabFromHash);
window.addEventListener('DOMContentLoaded', () => {
    activateTabFromHash();
    // Load ISO images on page load
    setTimeout(() => {
        loadAllISOImages();
    }, 500);
});

// Search functionality
const searchInput = document.getElementById('storageSearch');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('.vmware-table tbody tr');
        
        rows.forEach(row => {
            const name = row.querySelector('.storage-name-link')?.textContent.toLowerCase() || '';
            if (name.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}

// Select All checkbox
const selectAll = document.getElementById('selectAll');
if (selectAll) {
    selectAll.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
}

function scanForResources() {
    Swal.fire({
        title: 'Scan for Resources',
        text: 'This will scan all nodes for available storage resources.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Scan Now',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Scanning...', 'Please wait while we scan for resources.', 'info');
            // Implement actual scanning logic here
            setTimeout(() => {
                Swal.fire('Scan Complete', 'No new resources found.', 'success');
            }, 2000);
        }
    });
}

function openAddStorageModal() {
    console.log('Opening Add Storage modal...');
    const modal = document.getElementById('addStorageModal');
    console.log('Modal element:', modal);
    
    if (modal) {
        // Reset form completely
        const form = document.getElementById('addStorageForm');
        if (form) form.reset();
        
        // Add event listener for Storage Name auto-generation of ID
        const storageNameInput = document.getElementById('storageName');
        if (storageNameInput) {
            storageNameInput.addEventListener('input', function() {
                // Generate storage ID from name: remove special chars, convert to lowercase
                const storageId = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')  // Remove special characters
                    .replace(/\s+/g, '-')            // Replace spaces with hyphens
                    .replace(/-+/g, '-')             // Replace multiple hyphens with single
                    .replace(/^-+|-+$/g, '');         // Remove leading/trailing hyphens
                
                document.getElementById('storageId').value = storageId || 'storage-id';
                console.log('Generated storage ID:', storageId);
            });
        }
        
        // Reset storage type selector
        document.querySelectorAll('input[name="type"]').forEach(radio => {
            radio.checked = false;
        });
        
        // Hide step 2 and all storage fields with null checks
        const step2 = document.getElementById('step2');
        if (step2) step2.style.display = 'none';
        
        const fieldsDir = document.getElementById('fields-dir');
        if (fieldsDir) fieldsDir.style.display = 'none';
        
        const fieldsNfs = document.getElementById('fields-nfs');
        if (fieldsNfs) fieldsNfs.style.display = 'none';
        
        const fieldsRbd = document.getElementById('fields-rbd');
        if (fieldsRbd) fieldsRbd.style.display = 'none';
        
        const detectedList = document.getElementById('detectedList');
        if (detectedList) detectedList.style.display = 'none';
        
        // Disable scan button
        const scanBtn = document.getElementById('scanBtn');
        if (scanBtn) scanBtn.disabled = true;
        
        // Show modal
        modal.classList.add('active');
        modal.style.display = 'flex';
        modal.style.zIndex = '9999';
        
        console.log('Modal classes:', modal.className);
        console.log('Modal display:', window.getComputedStyle(modal).display);
    } else {
        console.error('Add Storage modal not found!');
    }
}

function closeAddStorageModal() {
    console.log('Closing Add Storage modal...');
    const modal = document.getElementById('addStorageModal');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
    }
    document.getElementById('addStorageForm').reset();
}

function toggleCreateDirForm() {
    const form = document.getElementById('createDirForm');
    if (form) {
        if (form.style.display === 'none') {
            form.style.display = 'block';
            document.getElementById('createDirPath').focus();
        } else {
            form.style.display = 'none';
        }
    }
}

async function createStorageDirectory() {
    const path = document.getElementById('createDirPath').value.trim();
    
    if (!path) {
        alert('Please enter a directory path');
        return;
    }
    
    if (!path.startsWith('/mnt/')) {
        alert('Path must start with /mnt/');
        return;
    }
    
    try {
        Swal.fire({
            title: 'Creating Directory...',
            html: `Creating directory at <b>${path}</b>`,
            allowOutsideClick: false,
            didOpen: async () => {
                Swal.showLoading();
                
                const response = await fetch(`${API_URL}/nodes/${PROXMOX_NODE}/create-directory`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ path: path })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Directory Created',
                        text: `Successfully created at ${path}`,
                        timer: 2000
                    });
                    
                    // Fill in the path field
                    document.querySelector('input[name="path"]').value = path;
                    
                    // Auto-generate storage ID from path
                    const storageId = path.replace(/\//g, '-').replace(/^-+|-+$/g, '').toLowerCase();
                    document.getElementById('storageId').value = storageId;
                    
                    // Show step 2
                    document.getElementById('step2').style.display = 'block';
                    
                    // Hide create dir form
                    document.getElementById('createDirForm').style.display = 'none';
                    document.getElementById('createDirPath').value = '';
                    
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.error || 'Failed to create directory'
                    });
                }
            }
        });
    } catch (error) {
        console.error('Error creating directory:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to create directory: ' + error.message
        });
    }
}

function updateStorageType() {
    const node = document.getElementById('storageNode').value;
    const typeRadio = document.querySelector('input[name="type"]:checked');
    const type = typeRadio ? typeRadio.value : null;
    
    console.log('updateStorageType: node=', node, 'type=', type);
    
    // Enable/disable scan button
    const scanBtn = document.getElementById('scanBtn');
    if (scanBtn) {
        if (node && type) {
            scanBtn.disabled = false;
            console.log('Scan button enabled');
        } else {
            scanBtn.disabled = true;
            console.log('Scan button disabled');
        }
    }
    
    // Hide detected list when changing type
    const detectedList = document.getElementById('detectedList');
    if (detectedList) {
        detectedList.style.display = 'none';
    }
}

function showStorageFields() {
    // Get selected storage type from radio buttons
    const typeRadio = document.querySelector('input[name="type"]:checked');
    const type = typeRadio ? typeRadio.value : null;
    const node = document.getElementById('storageNode').value;
    
    console.log('showStorageFields: type=', type, 'node=', node);
    
    // Hide all storage fields first
    const fieldsDir = document.getElementById('fields-dir');
    if (fieldsDir) fieldsDir.style.display = 'none';
    
    const fieldsNfs = document.getElementById('fields-nfs');
    if (fieldsNfs) fieldsNfs.style.display = 'none';
    
    const fieldsRbd = document.getElementById('fields-rbd');
    if (fieldsRbd) fieldsRbd.style.display = 'none';
    
    // Show fields for selected type
    if (type === 'dir' && fieldsDir) {
        fieldsDir.style.display = 'block';
    } else if (type === 'nfs' && fieldsNfs) {
        fieldsNfs.style.display = 'block';
    } else if (type === 'rbd' && fieldsRbd) {
        fieldsRbd.style.display = 'block';
    }
    
    // Reset detected list
    const detectedList = document.getElementById('detectedList');
    if (detectedList) detectedList.style.display = 'none';
    
    // Enable/disable scan button
    updateStorageType();
}

async function scanAvailableStorage() {
    const node = document.getElementById('storageNode').value;
    const typeRadio = document.querySelector('input[name="type"]:checked');
    const type = typeRadio ? typeRadio.value : null;
    
    if (!node || !type) {
        alert('Please select both node and storage type first');
        return;
    }
    
    const scanBtn = document.getElementById('scanBtn');
    const originalHTML = scanBtn.innerHTML;
    scanBtn.disabled = true;
    scanBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning...';
    
    try {
        let endpoint = '';
        let params = '';
        
        // Map storage types to scan endpoints
        if (type === 'dir') {
            endpoint = `${API_URL}/nodes/${node}/scan/dir`;
        } else if (type === 'rbd') {
            endpoint = `${API_URL}/nodes/${node}/scan/ceph`;
        } else if (type === 'nfs') {
            endpoint = `${API_URL}/nodes/${node}/scan/nfs`;
            
            // NFS scan requires server parameter from user
            const server = prompt('Enter NFS server IP or hostname:\n(e.g., 192.168.1.100)');
            if (!server) {
                scanBtn.disabled = false;
                scanBtn.innerHTML = originalHTML;
                return;
            }
            params = `?server=${encodeURIComponent(server)}`;
        }
        
        if (!endpoint) {
            alert('Scanning not supported for this storage type');
            scanBtn.disabled = false;
            scanBtn.innerHTML = originalHTML;
            return;
        }
        
        console.log('Scanning storage:', endpoint + params, 'type:', type);
        
        const response = await fetch(endpoint + params, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success || result.data) {
            const data = result.data || result;
            displayDetectedStorage(data, type);
        } else {
            alert('No storage found or scan failed');
        }
    } catch (error) {
        console.error('Scan error:', error);
        alert('Error scanning storage: ' + error.message);
    } finally {
        scanBtn.disabled = false;
        scanBtn.innerHTML = originalHTML;
    }
}

function displayDetectedStorage(data, type) {
    const detectedList = document.getElementById('detectedList');
    const detectedItems = document.getElementById('detectedItems');
    
    detectedItems.innerHTML = '';
    
    if (!data || (Array.isArray(data) && data.length === 0)) {
        detectedItems.innerHTML = '<p style="text-align: center; color: var(--gray-500); padding: 1rem;">No available storage found</p>';
        detectedList.style.display = 'block';
        return;
    }
    
    const items = Array.isArray(data) ? data : [data];
    
    items.forEach((item, index) => {
        const div = document.createElement('div');
        div.className = 'detected-item';
        div.onclick = () => selectDetectedStorage(item, type, div);
        
        let name = '';
        let details = '';
        
        if (type === 'dir') {
            name = item.path || item.name || 'Directory';
            details = `${item.size || 'Unknown size'} • ${item.mounted ? 'Mounted' : 'Unmounted'}`;
        } else if (type === 'rbd') {
            name = item.pool || item.name || 'Ceph Pool';
            details = `${item.size || 'Unknown size'} • Status: ${item.status || 'Active'}`;
        } else if (type === 'nfs') {
            name = item.server || item.name || 'NFS Server';
            details = `${item.export || 'Export'} • ${item.size || 'Unknown size'}`;
        }
        
        div.innerHTML = `
            <div class="detected-item-info">
                <div class="detected-item-name">${name}</div>
                <div class="detected-item-details">${details}</div>
            </div>
            <div class="detected-item-badge">Available</div>
        `;
        
        detectedItems.appendChild(div);
    });
    
    detectedList.style.display = 'block';
    
    // Show step 2 after detection
    document.getElementById('step2').style.display = 'block';
}

function selectDetectedStorage(item, type, element) {
    // Remove previous selection
    document.querySelectorAll('.detected-item').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    
    // Auto-fill form fields based on storage type
    if (type === 'dir') {
        // For local disk - check if it's an unmounted device
        if (item.device && !item.mounted) {
            // This is an unmounted disk - offer to mount it
            const mountPath = item.suggested_mount || '/mnt/storage';
            const confirmMount = confirm(`Mount ${item.name} to ${mountPath}?\n\nThis will format and mount the disk automatically.`);
            
            if (confirmMount) {
                // Call mount API
                mountDisk(item.name, mountPath, 'ext4', function(success, result) {
                    if (success) {
                        alert(`Successfully mounted ${item.name} to ${mountPath}`);
                        document.querySelector('input[name="path"]').value = mountPath;
                        // Generate storage ID from mount path, removing leading/trailing hyphens
                        const storageId = mountPath.replace(/\//g, '-').replace(/^-+|-+$/g, '').toLowerCase();
                        document.getElementById('storageId').value = storageId;
                        document.getElementById('step2').style.display = 'block';
                    } else {
                        alert(`Error mounting disk: ${result.error}`);
                    }
                });
            }
        } else {
            // This is a directory or already mounted
            const pathInput = document.querySelector('input[name="path"]');
            if (pathInput) {
                pathInput.value = item.path || item.name || item.suggested_mount || '';
            }
            document.getElementById('storageId').value = (item.name || 'local').toLowerCase().replace(/[^a-z0-9-]/g, '-');
            document.getElementById('step2').style.display = 'block';
        }
    } else if (type === 'rbd') {
        const monhostInput = document.querySelector('input[name="monhost"]');
        const poolInput = document.querySelector('input[name="pool"]');
        if (monhostInput) {
            monhostInput.value = item.server || item.monhost || '';
        }
        if (poolInput) {
            poolInput.value = item.pool || item.name || 'rbd';
        }
        document.getElementById('storageId').value = `ceph-${(item.pool || 'rbd').toLowerCase()}`;
        document.getElementById('step2').style.display = 'block';
    } else if (type === 'nfs') {
        const serverInput = document.querySelector('input[name="server"]');
        const exportInput = document.querySelector('input[name="export"]');
        if (serverInput) {
            serverInput.value = item.server || item.ip || '';
        }
        if (exportInput) {
            exportInput.value = item.export || item.path || '';
        }
        document.getElementById('storageId').value = `nfs-${(item.server || 'storage').toLowerCase()}`;
        document.getElementById('step2').style.display = 'block';
    }
    
    // Show success message
    const badge = element.querySelector('.detected-item-badge');
    badge.textContent = 'Selected';
    badge.style.background = 'rgba(102, 126, 234, 0.15)';
    badge.style.color = 'var(--blue-600)';
}

async function mountDisk(device, mountPath, filesystem = 'ext4', callback) {
    try {
        console.log(`Mounting ${device} to ${mountPath} as ${filesystem}...`);
        
        const node = document.getElementById('storageNode').value;
        if (!node) {
            callback(false, { error: 'Node not selected' });
            return;
        }
        
        const response = await fetch(`${API_URL}/nodes/${node}/mount-disk`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                device: device,
                mount_path: mountPath,
                filesystem: filesystem
            })
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            console.log('Mount successful:', result);
            callback(true, result);
        } else {
            console.error('Mount failed:', result);
            callback(false, result || { error: 'Mount failed' });
        }
    } catch (error) {
        console.error('Mount error:', error);
        callback(false, { error: error.message });
    }
}

// The following functions are for future network storage scanning features
// Currently disabled as they reference DOM elements not in the current modal implementation
/*
async function scanISCSI() {
    const portal = document.getElementById('iscsiPortal').value;
    if (!portal) {
        alert('Please enter iSCSI portal IP');
        return;
    }
    
    try {
        const response = await fetch(`${API_URL}/nodes/${currentNode}/scan/iscsi?portal=${portal}`);
        const result = await response.json();
        
        if (result.success && result.data && result.data.length > 0) {
            displayISCSITargets(result.data, portal);
        } else {
            alert('No iSCSI targets found on ' + portal);
        }
    } catch (error) {
        alert('Error scanning iSCSI: ' + error.message);
    }
}

function displayISCSITargets(targets, portal) {
    const container = document.getElementById('iscsiTargets');
    container.style.display = 'block';
    container.innerHTML = '<h4 style="margin: 1rem 0;">Detected iSCSI Targets</h4>';
    
    targets.forEach(target => {
        const div = document.createElement('div');
        div.className = 'storage-item';
        div.onclick = () => selectISCSITarget(target, portal);
        
        div.innerHTML = `
            <div class="storage-item-info">
                <div class="storage-item-name">${target.target || target}</div>
                <div class="storage-item-details">Portal: ${portal}</div>
            </div>
            <div class="storage-item-action">
                <i class="fas fa-chevron-right"></i>
            </div>
        `;
        
        container.appendChild(div);
    });
}

function selectISCSITarget(target, portal) {
    document.getElementById('manualForm').style.display = 'block';
    document.getElementById('submitBtn').style.display = 'block';
    
    const targetName = target.target || target;
    document.getElementById('storageId').value = targetName.split(':').pop();
    document.getElementById('dynamicFields').innerHTML = `
        <div class="form-group">
            <label class="form-label">Portal</label>
            <input type="text" name="portal" class="form-control" value="${portal}" readonly>
        </div>
        <div class="form-group">
            <label class="form-label">Target</label>
            <input type="text" name="target" class="form-control" value="${targetName}" readonly>
        </div>
    `;
    
    document.getElementById('manualForm').scrollIntoView({ behavior: 'smooth' });
}

async function scanNFS() {
    const server = document.getElementById('nfsServer').value;
    if (!server) {
        alert('Please enter NFS server IP');
        return;
    }
    
    try {
        const response = await fetch(`${API_URL}/nodes/${currentNode}/scan/nfs?server=${server}`);
        const result = await response.json();
        
        if (result.success && result.data && result.data.length > 0) {
            displayNFSExports(result.data, server);
        } else {
            alert('No NFS exports found on ' + server);
        }
    } catch (error) {
        alert('Error scanning NFS: ' + error.message);
    }
}

function displayNFSExports(exports, server) {
    const container = document.getElementById('nfsExports');
    container.style.display = 'block';
    container.innerHTML = '<h4 style="margin: 1rem 0;">Detected NFS Exports</h4>';
    
    exports.forEach(exp => {
        const div = document.createElement('div');
        div.className = 'storage-item';
        div.onclick = () => selectNFSExport(exp, server);
        
        const exportPath = exp.path || exp;
        div.innerHTML = `
            <div class="storage-item-info">
                <div class="storage-item-name">${exportPath}</div>
                <div class="storage-item-details">Server: ${server}</div>
            </div>
            <div class="storage-item-action">
                <i class="fas fa-chevron-right"></i>
            </div>
        `;
        
        container.appendChild(div);
    });
}

function selectNFSExport(exportData, server) {
    document.getElementById('manualForm').style.display = 'block';
    document.getElementById('submitBtn').style.display = 'block';
    
    const exportPath = exportData.path || exportData;
    document.getElementById('storageId').value = 'nfs-' + exportPath.split('/').pop();
    document.getElementById('dynamicFields').innerHTML = `
        <div class="form-group">
            <label class="form-label">Server</label>
            <input type="text" name="server" class="form-control" value="${server}" readonly>
        </div>
        <div class="form-group">
            <label class="form-label">Export Path</label>
            <input type="text" name="export" class="form-control" value="${exportPath}" readonly>
        </div>
        <div class="form-group">
            <label class="form-label">NFS Version</label>
            <select name="options" class="form-control">
                <option value="">Auto (Default)</option>
                <option value="vers=3">NFSv3</option>
                <option value="vers=4">NFSv4</option>
                <option value="vers=4.1">NFSv4.1</option>
                <option value="vers=4.2">NFSv4.2</option>
            </select>
        </div>
    `;
    
    document.getElementById('manualForm').scrollIntoView({ behavior: 'smooth' });
}

async function scanGlusterFS() {
    const server = document.getElementById('glusterfsServer').value;
    if (!server) {
        alert('Please enter GlusterFS server IP');
        return;
    }
    
    try {
        const response = await fetch(`${API_URL}/nodes/${currentNode}/scan/glusterfs?server=${server}`);
        const result = await response.json();
        
        if (result.success && result.data && result.data.length > 0) {
            displayGlusterFSVolumes(result.data, server);
        } else {
            alert('No GlusterFS volumes found on ' + server);
        }
    } catch (error) {
        alert('Error scanning GlusterFS: ' + error.message);
    }
}

function displayGlusterFSVolumes(volumes, server) {
    const container = document.getElementById('glusterfsVolumes');
    container.style.display = 'block';
    container.innerHTML = '<h4 style="margin: 1rem 0;">Detected GlusterFS Volumes</h4>';
    
    volumes.forEach(vol => {
        const div = document.createElement('div');
        div.className = 'storage-item';
        div.onclick = () => selectGlusterFSVolume(vol, server);
        
        const volName = vol.name || vol;
        div.innerHTML = `
            <div class="storage-item-info">
                <div class="storage-item-name">${volName}</div>
                <div class="storage-item-details">Server: ${server}</div>
            </div>
            <div class="storage-item-action">
                <i class="fas fa-chevron-right"></i>
            </div>
        `;
        
        container.appendChild(div);
    });
}

function selectGlusterFSVolume(volume, server) {
    document.getElementById('manualForm').style.display = 'block';
    document.getElementById('submitBtn').style.display = 'block';
    
    const volName = volume.name || volume;
    document.getElementById('storageId').value = 'gluster-' + volName;
    document.getElementById('dynamicFields').innerHTML = `
        <div class="form-group">
            <label class="form-label">Server</label>
            <input type="text" name="server" class="form-control" value="${server}" readonly>
        </div>
        <div class="form-group">
            <label class="form-label">Volume</label>
            <input type="text" name="volume" class="form-control" value="${volName}" readonly>
        </div>
    `;
    
    document.getElementById('manualForm').scrollIntoView({ behavior: 'smooth' });
}
*/

async function submitAddStorage(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const node = formData.get('node');
    const type = formData.get('type');
    const storage = formData.get('storage');
    
    // Build content types array from select multiple
    const contentSelect = document.getElementById('storageContent');
    const selectedOptions = Array.from(contentSelect.selectedOptions);
    const contentTypes = selectedOptions.map(option => option.value);
    
    console.log('Selected content types:', contentTypes);
    
    // Validate required fields
    if (!node || !type || !storage) {
        alert('Please fill in all required fields (Node, Storage Type, Storage ID)');
        return false;
    }
    
    if (contentTypes.length === 0) {
        alert('Please select at least one content type');
        return false;
    }
    
    // Build storage data object based on type
    const data = {
        storage: storage,
        type: type,
        nodes: node, // Specify which node to add storage to
        content: contentTypes.join(','),
        shared: formData.get('shared') ? 1 : 0,
        disable: formData.get('enabled') ? 0 : 1
    };
    
    // Add type-specific fields
    switch(type) {
        case 'dir':
            data.path = formData.get('path');
            break;
        case 'lvm':
            data.vgname = formData.get('vgname');
            break;
        case 'lvmthin':
            data.vgname = formData.get('vgname');
            data.thinpool = formData.get('thinpool');
            break;
        case 'zfspool':
            data.pool = formData.get('pool');
            break;
        case 'nfs':
            data.server = formData.get('server');
            data.export = formData.get('export');
            break;
        case 'cifs':
            data.server = formData.get('server');
            data.share = formData.get('share');
            if (formData.get('username')) data.username = formData.get('username');
            if (formData.get('password')) data.password = formData.get('password');
            break;
        case 'iscsi':
            data.portal = formData.get('portal');
            data.target = formData.get('target');
            break;
        case 'glusterfs':
            data.server = formData.get('server');
            data.volume = formData.get('volume');
            break;
        case 'rbd':
            data.monhost = formData.get('monhost');
            data.pool = formData.get('pool');
            if (formData.get('username')) data.username = formData.get('username');
            break;
    }
    
    console.log('Submitting storage data:', data);
    console.log('Node:', node);
    
    // Validate required fields based on type
    let missingFields = [];
    switch(type) {
        case 'dir':
            if (!data.path) missingFields.push('path');
            break;
        case 'lvm':
        case 'lvmthin':
            if (!data.vgname) missingFields.push('vgname');
            if (type === 'lvmthin' && !data.thinpool) missingFields.push('thinpool');
            break;
        case 'zfspool':
            if (!data.pool) missingFields.push('pool');
            break;
        case 'nfs':
            if (!data.server) missingFields.push('server');
            if (!data.export) missingFields.push('export');
            break;
    }
    
    if (missingFields.length > 0) {
        alert(`Missing required fields: ${missingFields.join(', ')}`);
        return false;
    }
    
    try {
        const endpoint = `${API_URL}/storage`;
        console.log('POST to:', endpoint);
        
        const response = await fetch(endpoint, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Error response:', errorText);
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }
        
        const result = await response.json();
        console.log('Response data:', result);
        
        if (result.success) {
            alert('✓ Storage added successfully!');
            closeAddStorageModal();
            // Full page reload after a short delay to ensure Proxmox updates
            setTimeout(() => location.reload(), 1500);
        } else {
            alert('Error adding storage: ' + (result.error || result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    }
    
    return false;
}

// Upload ISO Functions
function openUploadISOModal() {
    console.log('Opening Upload ISO modal...');
    const modal = document.getElementById('uploadISOModal');
    console.log('Modal element:', modal);
    
    if (modal) {
        // Reset form
        document.getElementById('uploadISOForm').reset();
        document.getElementById('uploadProgress').style.display = 'none';
        document.getElementById('uploadProgressBar').style.width = '0%';
        
        // Show modal
        modal.classList.add('active');
        modal.style.display = 'flex';
        modal.style.zIndex = '9999';
        
        console.log('Modal classes:', modal.className);
        console.log('Modal display:', window.getComputedStyle(modal).display);
    } else {
        console.error('Upload ISO modal not found!');
    }
}

function closeUploadISOModal() {
    console.log('Closing Upload ISO modal...');
    const modal = document.getElementById('uploadISOModal');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
    }
    document.getElementById('uploadISOForm').reset();
    document.getElementById('uploadProgress').style.display = 'none';
    document.getElementById('uploadProgressBar').style.width = '0%';
}

// ========== File Browser Functions ==========

let currentBrowserPath = '/mnt';

function getSelectedNode() {
    // Get from form select if available, otherwise use first node from dropdown or default
    const nodeSelect = document.getElementById('storageNode');
    if (nodeSelect && nodeSelect.value) {
        return nodeSelect.value;
    }
    // Fallback to trying to extract from storage data or use default
    if (storageData && storageData.length > 0 && storageData[0].node) {
        return storageData[0].node;
    }
    return 'silo1'; // Last resort default
}

function openFileBrowser(startPath = '/mnt') {
    console.clear(); // Clear console for better visibility
    console.log('%c🔍 openFileBrowser STARTED', 'color: green; font-size: 14px; font-weight: bold;');
    console.log('Requested path:', startPath);
    
    currentBrowserPath = startPath;
    const modal = document.getElementById('fileBrowserModal');
    console.log('✓ Looking for modal element with id="fileBrowserModal"');
    console.log('  Result:', modal ? 'FOUND ✅' : 'NOT FOUND ❌');
    
    if (modal) {
        console.log('%c➕ Adding .active class', 'color: blue;');
        modal.classList.add('active');
        
        console.log('%c🎨 Setting inline styles', 'color: blue;');
        modal.style.display = 'flex';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        
        console.log('✓ Checking computed styles:');
        const computedStyle = window.getComputedStyle(modal);
        console.log('  - display:', computedStyle.display);
        console.log('  - visibility:', computedStyle.visibility);
        console.log('  - opacity:', computedStyle.opacity);
        console.log('  - z-index:', computedStyle.zIndex);
        console.log('  - position:', computedStyle.position);
        console.log('  - width:', computedStyle.width);
        console.log('  - height:', computedStyle.height);
        
        console.log('%c📂 Calling loadDirectoryContents("' + startPath + '")', 'color: green;');
    } else {
        console.error('%c❌ FAILED: fileBrowserModal not found in DOM!', 'color: red; font-size: 12px;');
        return;
    }
    loadDirectoryContents(startPath);
}

function closeFileBrowserModal() {
    const modal = document.getElementById('fileBrowserModal');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
    }
}

async function loadDirectoryContents(path) {
    try {
        console.log('%c📂 loadDirectoryContents STARTED', 'color: green; font-size: 14px; font-weight: bold;');
        console.log('Path:', path);
        console.log('API_URL:', API_URL);
        
        if (!API_URL) {
            console.error('%c❌ API_URL is undefined!', 'color: red;');
            alert('ERROR: API_URL not configured');
            return;
        }
        
        currentBrowserPath = path;
        
        const displayEl = document.getElementById('selectedPathDisplay');
        if (displayEl) {
            displayEl.textContent = path;
        }
        
        // Update breadcrumb
        const parts = path.split('/').filter(p => p);
        let breadcrumb = '';
        let currentPath = '';
        for (let part of parts) {
            currentPath += '/' + part;
            if (part !== 'mnt') {
                breadcrumb += ` / <button class="btn-breadcrumb" onclick="navigateToPath('${currentPath}')" style="padding: 0; border: none; background: none; color: var(--blue-400); cursor: pointer; text-decoration: underline;">${part}</button>`;
            }
        }
        const breadcrumbEl = document.getElementById('breadcrumbPath');
        if (breadcrumbEl) {
            breadcrumbEl.innerHTML = breadcrumb;
        }
        
        // Fetch directory contents
        const node = getSelectedNode();
        const fetchURL = `${API_URL}/nodes/${node}/browse-directory`;
        console.log('%c🌐 FETCHING API', 'color: blue; font-weight: bold;');
        console.log('  Node:', node);
        console.log('  Full URL:', fetchURL);
        console.log('  Path:', path);
        
        let response;
        try {
            console.log('  → Sending fetch request...');
            response = await fetch(fetchURL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ path: path })
            });
            console.log('%c✅ FETCH SUCCEEDED', 'color: green;');
            console.log('  Status:', response.status, response.statusText);
            console.log('  OK:', response.ok);
            console.log('  Headers:', {
                'content-type': response.headers.get('content-type'),
                'content-length': response.headers.get('content-length')
            });
        } catch (fetchError) {
            console.error('%c❌ FETCH FAILED', 'color: red; font-weight: bold;');
            console.error('  Type:', fetchError.name);
            console.error('  Message:', fetchError.message);
            console.error('  Stack:', fetchError.stack);
            alert('Network error: ' + fetchError.message);
            return;
        }
        
        let result;
        try {
            console.log('  → Parsing JSON response...');
            result = await response.json();
            console.log('%c✅ JSON PARSED', 'color: green;');
        } catch (parseError) {
            console.error('%c❌ JSON PARSE ERROR', 'color: red; font-weight: bold;');
            console.error('  Error:', parseError.message);
            console.error('  Response status:', response.status);
            alert('Response parsing error: ' + parseError.message);
            return;
        }
        
        console.log('%c📨 API RESPONSE', 'color: blue; font-weight: bold;');
        console.log('  Full response:', result);
        console.log('  Success:', result.success);
        console.log('  Data type:', typeof result.data);
        console.log('  Data is array:', Array.isArray(result.data));
        console.log('  Data length:', result.data ? result.data.length : 'N/A');
        
        if (!result.success) {
            console.error('%c❌ API RETURNED ERROR', 'color: red; font-weight: bold;');
            console.error('  Error:', result.error);
            alert('API Error: ' + (result.error || 'Unknown error'));
            return;
        }
        
        const items = result.data || [];
        console.log('%c📋 PROCESSING ITEMS', 'color: blue;');
        console.log('  Count:', items.length);
        console.log('  Items:', items);
            
        const tbody = document.getElementById('fileListBody');
        console.log('  tbody element found:', !!tbody);
        
        if (!tbody) {
            console.error('%c❌ fileListBody NOT FOUND', 'color: red; font-weight: bold;');
            alert('Error: fileListBody not found in DOM');
            return;
        }
        
        console.log('%c🎨 RENDERING TABLE', 'color: purple;');
        if (items.length === 0) {
            console.log('  → Empty directory');
            tbody.innerHTML = '<tr><td colspan="3" style="padding: 2rem; text-align: center; color: var(--gray-500);">📁 Empty directory</td></tr>';
        } else {
            console.log('  → Rendering', items.length, 'rows');
            let html = '';
            items.forEach((item, idx) => {
                console.log(`  [${idx}] ${item.name}`);
                const icon = item.is_dir ? '📁' : '📄';
                const type = item.is_dir ? 'Folder' : 'File';
                const deleteBtn = item.is_dir ? `<button class="btn btn-sm btn-danger" onclick="deleteFolder('${item.path}')"><i class="fas fa-trash"></i> Delete</button>` : '';
                html += `
                    <tr style="border-bottom: 1px solid rgba(148, 163, 184, 0.1);">
                        <td style="padding: 0.75rem;">
                            <span onclick="navigateToPath('${item.path}')" style="cursor:pointer; color: var(--blue-400); text-decoration: underline;">
                                ${icon} ${item.name}
                            </span>
                        </td>
                        <td style="padding: 0.75rem; font-size: 0.85rem; color: var(--gray-500);">${type}</td>
                        <td style="padding: 0.75rem; text-align: center;">${deleteBtn}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
            console.log('%c✅ TABLE RENDERED', 'color: green; font-weight: bold;');
            console.log('  Rows added:', items.length);
        }
    } catch (error) {
        console.error('%c❌ EXCEPTION IN loadDirectoryContents', 'color: red; font-weight: bold;');
        console.error('  Error:', error);
        console.error('  Stack:', error.stack);
        alert('Failed to load directory: ' + error.message);
    }
}

function navigateToPath(path) {
    if (path.startsWith('/mnt')) {
        loadDirectoryContents(path);
    } else {
        alert('Can only browse /mnt directory');
    }
}

function refreshFileBrowser() {
    loadDirectoryContents(currentBrowserPath);
}

// Debug: Test if table can be populated
function testFullModalFlow() {
    console.log('%c🧪 TEST: testFullModalFlow() - Full test', 'color: purple; font-weight: bold; font-size: 14px;');
    
    // Step 1: Show modal
    const modal = document.getElementById('fileBrowserModal');
    if (!modal) {
        console.error('❌ Modal not found');
        return;
    }
    console.log('✓ Step 1: Found modal');
    
    modal.classList.add('active');
    modal.style.display = 'flex';
    console.log('✓ Step 2: Modal displayed');
    
    // Step 2: Populate tbody with test data
    const tbody = document.getElementById('fileListBody');
    if (!tbody) {
        console.error('❌ tbody not found');
        return;
    }
    console.log('✓ Step 3: Found tbody');
    
    tbody.innerHTML = `
        <tr><td colspan="3" style="padding: 1rem;">
            <strong>🧪 TEST DATA - Modal is working!</strong>
        </td></tr>
        <tr>
            <td style="padding: 0.75rem;">📁 <span style="color: blue; text-decoration: underline; cursor: pointer;">sdb</span></td>
            <td style="padding: 0.75rem;">Folder</td>
            <td style="padding: 0.75rem; text-align: center;"><button>Delete</button></td>
        </tr>
        <tr>
            <td style="padding: 0.75rem;">📁 <span style="color: blue; text-decoration: underline; cursor: pointer;">storage</span></td>
            <td style="padding: 0.75rem;">Folder</td>
            <td style="padding: 0.75rem; text-align: center;"><button>Delete</button></td>
        </tr>
    `;
    console.log('✓ Step 4: Tbody populated with test data');
    console.log('✅ TEST COMPLETE - Modal should show 2 folders above');
}

function testTablePopulation() {
    console.log('%c🧪 TEST: testTablePopulation() called', 'color: purple; font-weight: bold;');
    const tbody = document.getElementById('fileListBody');
    console.log('tbody element:', tbody);
    
    if (!tbody) {
        console.error('❌ fileListBody not found!');
        return;
    }
    
    // Test 1: Simple text
    console.log('Test 1: Setting simple text...');
    tbody.innerHTML = '<tr><td colspan="3">TEST ROW 1</td></tr>';
    console.log('✓ After setting HTML:', tbody.innerHTML);
    
    // Test 2: With HTML
    setTimeout(() => {
        console.log('Test 2: Setting HTML table row...');
        tbody.innerHTML = '<tr><td>📁 test_folder</td><td>Folder</td><td><button>Delete</button></td></tr>';
        console.log('✓ After setting HTML:', tbody.innerHTML);
    }, 1000);
}

async function createNewDirectory() {
    const folderName = document.getElementById('newDirName').value.trim();
    
    if (!folderName) {
        alert('Please enter a folder name');
        return;
    }
    
    // Validate folder name
    if (!/^[a-zA-Z0-9_-]+$/.test(folderName)) {
        alert('Folder name can only contain letters, numbers, hyphens and underscores');
        return;
    }
    
    const fullPath = currentBrowserPath.endsWith('/') 
        ? currentBrowserPath + folderName 
        : currentBrowserPath + '/' + folderName;
    
    try {
        const node = getSelectedNode();
        const response = await fetch(`${API_URL}/nodes/${node}/create-directory`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ path: fullPath })
        });
        
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('newDirName').value = '';
            Swal.fire({
                icon: 'success',
                title: 'Folder Created',
                text: `Created "${folderName}" successfully`,
                timer: 1500
            });
            loadDirectoryContents(currentBrowserPath);
        } else {
            alert('Error creating folder: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error creating folder:', error);
        alert('Failed to create folder: ' + error.message);
    }
}

async function deleteFolder(path) {
    const folderName = path.split('/').pop();
    
    const confirmed = await Swal.fire({
        icon: 'warning',
        title: 'Delete Folder?',
        text: `Delete "${folderName}"? This action cannot be undone.`,
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#ef4444',
        cancelButtonText: 'Cancel'
    });
    
    if (!confirmed.isConfirmed) return;
    
    try {
        const node = getSelectedNode();
        const response = await fetch(`${API_URL}/nodes/${node}/delete-directory`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ path: path })
        });
        
        const result = await response.json();
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Folder Deleted',
                text: `"${folderName}" has been deleted`,
                timer: 1500
            });
            loadDirectoryContents(currentBrowserPath);
        } else {
            alert('Error deleting folder: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error deleting folder:', error);
        alert('Failed to delete folder: ' + error.message);
    }
}

function selectPathAndCreate() {
    // Fill path in Add Storage modal
    document.querySelector('input[name="path"]').value = currentBrowserPath;
    
    // Auto-generate storage ID
    const storageId = currentBrowserPath.replace(/\//g, '-').replace(/^-+|-+$/g, '').toLowerCase();
    document.getElementById('storageId').value = storageId;
    
    // Show step 2
    document.getElementById('step2').style.display = 'block';
    
    // Close file browser
    closeFileBrowserModal();
}

// ========== End File Browser Functions ==========

function updateUploadStorageList() {
    const node = document.getElementById('uploadNode').value;
    const storageSelect = document.getElementById('uploadStorage');
    
    // Clear current options
    storageSelect.innerHTML = '<option value="">Select storage that supports ISO</option>';
    
    if (!node) return;
    
    // Filter storage that supports ISO content
    const isoStorage = storageData.filter(s => 
        s.node === node && 
        s.content && 
        s.content.includes('iso') &&
        (!s.disabled || s.disabled === 0)
    );
    
    if (isoStorage.length === 0) {
        storageSelect.innerHTML = '<option value="">No ISO storage available on this node</option>';
        return;
    }
    
    isoStorage.forEach(storage => {
        const option = document.createElement('option');
        option.value = storage.storage;
        option.textContent = `${storage.storage} (${storage.type})`;
        storageSelect.appendChild(option);
    });
}

async function submitUploadISO(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const node = formData.get('node');
    const storage = formData.get('storage');
    const file = formData.get('file');
    
    if (!file || !file.name) {
        Swal.fire({
            icon: 'error',
            title: 'No File Selected',
            text: 'Please select an ISO file to upload',
            confirmButtonColor: '#ef4444'
        });
        return false;
    }
    
    const filename = file.name;
    const fileSize = (file.size / (1024 * 1024)).toFixed(2); // MB
    
    const uploadBtn = document.getElementById('uploadBtn');
    const originalBtnHTML = uploadBtn.innerHTML;
    
    // Disable button และป้องกันการคลิกซ้ำ
    uploadBtn.disabled = true;
    uploadBtn.style.background = '#9ca3af';
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    
    // Show progress
    document.getElementById('uploadProgress').style.display = 'block';
    document.getElementById('uploadStatus').textContent = `Starting upload of ${filename}...`;
    document.getElementById('uploadFileInfo').textContent = `File size: ${fileSize} MB`;
    document.getElementById('uploadFileInfo').style.display = 'block';
    
    // Reset progress bar
    document.getElementById('uploadProgressBar').style.width = '0%';
    document.getElementById('uploadProgressBar').style.textContent = '';
    
    try {
        // Create FormData for upload
        const uploadData = new FormData();
        uploadData.append('content', 'iso');
        uploadData.append('file', file, filename);
        
        const xhr = new XMLHttpRequest();
        let uploadTimeout;
        
        // Progress event
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                const uploaded = (e.loaded / (1024 * 1024)).toFixed(2);
                
                document.getElementById('uploadProgressBar').style.width = percent + '%';
                
                if (percent < 100) {
                    const displayPercent = Math.min(percent, 99); // Cap at 99% for progress bar
                    document.getElementById('uploadProgressBar').style.width = displayPercent + '%';
                    document.getElementById('uploadProgressBar').textContent = percent + '%';
                    document.getElementById('uploadStatus').textContent = `Uploading... ${percent}% (${uploaded}/${fileSize} MB)`;
                } else {
                    // เมื่อ upload ครบ 100% แล้วรอ Proxmox process
                    document.getElementById('uploadProgressBar').style.width = '100%';
                    document.getElementById('uploadProgressBar').textContent = '100%';
                    document.getElementById('uploadStatus').innerHTML = `
                        <i class="fas fa-spinner fa-spin"></i> Upload complete - Processing file...
                    `;
                }
            }
        });
        
        // Load event
        xhr.addEventListener('load', () => {
            clearTimeout(uploadTimeout);
            
            // ไฟล์ upload เสร็จแล้ว
            if (xhr.status === 200 || xhr.status === 201) {
                try {
                    const result = JSON.parse(xhr.responseText);
                    if (result.success) {
                        document.getElementById('uploadProgressBar').style.width = '100%';
                        document.getElementById('uploadStatus').innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> Upload successful!';
                        
                        // Auto close modal หลังจาก 1 วินาที
                        setTimeout(() => {
                            closeUploadISOModal();
                            
                            // Show success notification
                            Swal.fire({
                                icon: 'success',
                                title: 'Upload Complete!',
                                html: `<p>ISO file <b>${filename}</b> has been uploaded successfully.</p><p style="color: #6b7280; font-size: 0.9em; margin-top: 1em;">It will appear in your storage shortly.</p>`,
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#3b82f6',
                                timer: 4000,
                                timerProgressBar: true
                            }).then(() => {
                                // Refresh storage view
                                viewStorageContent(storage, node);
                            });
                        }, 1000);
                    } else {
                        document.getElementById('uploadBtn').disabled = false;
                        uploadBtn.style.background = '#3b82f6';
                        uploadBtn.innerHTML = originalBtnHTML;
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Failed',
                            text: result.error || result.message || 'Unknown error occurred',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                    // Status 200 but parse failed - still consider as success
                    document.getElementById('uploadProgressBar').style.width = '100%';
                    document.getElementById('uploadStatus').innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> Upload successful!';
                    
                    setTimeout(() => {
                        closeUploadISOModal();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Upload Complete!',
                            html: `<p>ISO file <b>${filename}</b> has been uploaded successfully.</p>`,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#3b82f6',
                            timer: 4000,
                            timerProgressBar: true
                        }).then(() => {
                            viewStorageContent(storage, node);
                        });
                    }, 1000);
                }
            } else {
                document.getElementById('uploadBtn').disabled = false;
                uploadBtn.style.background = '#3b82f6';
                uploadBtn.innerHTML = originalBtnHTML;
                
                Swal.fire({
                    icon: 'error',
                    title: 'Upload Failed',
                    text: `Server error: ${xhr.status}`,
                    confirmButtonColor: '#ef4444'
                });
            }
        });
        
        // Error event
        xhr.addEventListener('error', () => {
            clearTimeout(uploadTimeout);
            document.getElementById('uploadBtn').disabled = false;
            uploadBtn.style.background = '#3b82f6';
            uploadBtn.innerHTML = originalBtnHTML;
            
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Unable to connect to server. Please check your connection.',
                confirmButtonColor: '#ef4444'
            });
        });
        
        // Abort event
        xhr.addEventListener('abort', () => {
            clearTimeout(uploadTimeout);
            document.getElementById('uploadBtn').disabled = false;
            uploadBtn.style.background = '#3b82f6';
            uploadBtn.innerHTML = originalBtnHTML;
        });
        
        const uploadUrl = `${API_URL}/nodes/${node}/storage/${storage}/upload`;
        console.log('Uploading to:', uploadUrl);
        
        xhr.open('POST', uploadUrl);
        xhr.timeout = 300000; // 5 minutes timeout for large files
        xhr.send(uploadData);
        
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('uploadBtn').disabled = false;
        uploadBtn.style.background = '#3b82f6';
        uploadBtn.innerHTML = originalBtnHTML;
        
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            confirmButtonColor: '#ef4444'
        });
    }
    
    return false;
}

// View storage content - ESXi Datastore Browser Style
function viewStorageContent(storageId, node) {
    console.log('Viewing storage:', storageId, 'on node:', node);
    
    // Show loading
    Swal.fire({
        title: 'Loading Storage Content...',
        html: `Fetching content from <b>${storageId}</b>...`,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Fetch storage content
    fetch(`${API_URL}/nodes/${node}/storage/${storageId}/content`, {
        credentials: 'include'
    })
    .then(response => response.json())
    .then(result => {
        if (result.success && result.data) {
            const content = result.data;
            
            // Filter content to show only ISO, files (other), and directories
            const filteredContent = content.filter(item => {
                const itemType = item.content;
                // Only show: iso, directories, and other files
                return itemType === 'iso' || itemType === 'directory' || itemType === 'file' || (item.is_dir);
            });
            
            if (filteredContent.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Storage Empty',
                    text: `No ISO files or folders found in storage "${storageId}"`,
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }
            
            // Organize content by type
            const organized = {
                'iso': { label: 'ISO Images', icon: 'fa-compact-disc', color: '#8b5cf6', items: [] },
                'directory': { label: 'Folders', icon: 'fa-folder', color: '#f59e0b', items: [] },
                'file': { label: 'Other Files', icon: 'fa-file', color: '#6b7280', items: [] },
                'other': { label: 'Other', icon: 'fa-file', color: '#6b7280', items: [] }
            };
            
            // Sort content into categories
            // Filter to show ONLY directories at root level (simplified UI)
            const rootFolders = filteredContent.filter(item => (item.is_dir || item.content === 'directory'));
            
            // Build HTML - Simplified with root folders expanded inline
            let html = `
                <div class="datastore-browser" style="min-width: 700px; max-height: 750px; overflow-y: auto; background: #ffffff; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <!-- Header with toolbar -->
                    <div style="padding: 1rem; background: #f9fafb; border-bottom: 2px solid #e5e7eb; border-radius: 0.5rem 0.5rem 0 0; position: sticky; top: 0; z-index: 10;">
                        <div style="margin-bottom: 0.75rem;">
                            <div style="font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem; color: #1f2937;">
                                <i class="fas fa-database" style="color: #3b82f6; font-size: 1.25rem;"></i>
                                <span>${storageId}</span>
                            </div>
                            <div style="font-size: 0.9rem; color: #6b7280; display: flex; gap: 2rem; margin-top: 0.5rem;">
                                <div><i class="fas fa-server" style="margin-right: 0.5rem;"></i><strong>Node:</strong> ${node}</div>
                                <div><i class="fas fa-folder" style="margin-right: 0.5rem;"></i><strong>Folders:</strong> ${rootFolders.length}</div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div style="display: flex; gap: 0.5rem; margin-top: 1rem; flex-wrap: wrap;">
                            <button onclick="promptCreateDirectory('${storageId}', '${node}')" style="background: #10b981; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.375rem; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; transition: background 0.2s; font-size: 0.9rem;">
                                <i class="fas fa-folder-plus"></i> Create Directory
                            </button>
                            <button onclick="openUploadISOModal()" style="background: #3b82f6; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.375rem; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; transition: background 0.2s; font-size: 0.9rem;">
                                <i class="fas fa-upload"></i> Upload ISO
                            </button>
                            <button onclick="location.reload()" style="background: #6b7280; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.375rem; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; transition: background 0.2s; font-size: 0.9rem;">
                                <i class="fas fa-sync"></i> Refresh
                            </button>
                        </div>
                    </div>
                    
                    <!-- Content Area - Root Folders with Expandable Contents -->
                    <div style="overflow-x: auto; padding: 0;">
            `;
            
            // Build sections for each root folder with their contents
            rootFolders.forEach((folder, folderIdx) => {
                const folderName = folder.name || (folder.volid ? folder.volid.split('/').pop() : 'Unknown');
                const relPath = folder.rel_path || folder.name;
                const folderId = `folder-${folderIdx}`;
                
                html += `
                    <div style="border-bottom: 2px solid #e5e7eb; background: ${folderIdx % 2 === 0 ? '#ffffff' : '#f9fafb'};" data-storage="${storageId}" data-node="${node}" data-folder-path="${relPath}" data-folder-name="${folderName}">
                        <!-- Folder Header with Toggle Button -->
                        <div class="folder-header" style="padding: 1rem; display: flex; align-items: center; gap: 1rem; background: linear-gradient(to right, #f3f4f6 0%, #ffffff 100%); cursor: pointer; transition: background 0.2s; justify-content: space-between;" data-folder-id="${folderId}" onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background='linear-gradient(to right, #f3f4f6 0%, #ffffff 100%)'">
                            <div style="display: flex; align-items: center; gap: 0.75rem; min-width: 0;">
                                <i class="fas fa-folder" style="color: #f59e0b; font-size: 1.3rem; flex-shrink: 0;"></i>
                                <span style="font-weight: 600; color: #1f2937; font-size: 1rem;">📁 ${folderName}</span>
                                <i id="${folderId}-toggle" class="fas fa-chevron-right" style="color: #6b7280; transition: transform 0.2s; transform: rotate(90deg); flex-shrink: 0;"></i>
                            </div>
                            <div style="display: flex; gap: 0.5rem; flex-shrink: 0;">
                                <button class="browse-btn" data-folder-id="${folderId}" data-storage="${storageId}" data-node="${node}" data-path="${relPath}" title="Browse folder" style="background: #3b82f6; color: white; border: none; padding: 0.375rem 0.75rem; border-radius: 0.375rem; cursor: pointer; font-size: 0.75rem; font-weight: 500; transition: background 0.2s;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
                                    <i class="fas fa-folder-open"></i> Browse
                                </button>
                                <button class="delete-btn" data-folder-id="${folderId}" data-storage="${storageId}" data-node="${node}" data-name="${folderName}" title="Delete folder" style="background: #ef4444; color: white; border: none; padding: 0.375rem 0.75rem; border-radius: 0.375rem; cursor: pointer; font-size: 0.75rem; font-weight: 500; transition: background 0.2s;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                        <!-- Folder Contents (Expandable) -->
                        <div id="${folderId}-contents" style="display: none; padding: 1rem; background: #fafafa; border-top: 1px solid #e5e7eb;">
                            <div id="${folderId}-loading" style="text-align: center; padding: 1rem; color: #6b7280;">
                                <i class="fas fa-spinner fa-spin"></i> Loading contents...
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
            
            // Store first folder info for later loading
            const firstFolderPath = rootFolders.length > 0 ? (rootFolders[0].rel_path || rootFolders[0].name) : null;
            const savedStorageId = storageId;
            const savedNode = node;
            
            Swal.fire({
                title: 'Datastore Browser',
                html: html,
                width: '1100px',
                heightAuto: false,
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    popup: 'datastore-browser-popup',
                    htmlContainer: 'datastore-browser-html'
                },
                didOpen: () => {
                    // Attach event listeners to folder headers and buttons
                    document.querySelectorAll('.folder-header').forEach(header => {
                        header.addEventListener('click', function(e) {
                            if (e.target.closest('.browse-btn') || e.target.closest('.delete-btn')) return;
                            const folderId = this.getAttribute('data-folder-id');
                            toggleFolderContents(folderId);
                        });
                    });
                    
                    document.querySelectorAll('.browse-btn').forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const storage = this.getAttribute('data-storage');
                            const node = this.getAttribute('data-node');
                            const path = this.getAttribute('data-path');
                            const folderId = this.getAttribute('data-folder-id');
                            expandDirectory(storage, node, path, folderId);
                        });
                    });
                    
                    document.querySelectorAll('.delete-btn').forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const storage = this.getAttribute('data-storage');
                            const node = this.getAttribute('data-node');
                            const name = this.getAttribute('data-name');
                            promptDeleteDirectory(storage, node, name);
                        });
                    });
                    
                    // Add toggle functionality to folder headers (chevron click)
                    document.querySelectorAll('.folder-header').forEach(header => {
                        header.addEventListener('click', function(e) {
                            if (!e.target.closest('button')) {
                                const folderId = this.getAttribute('data-folder-id');
                                const contentsDiv = document.getElementById(`${folderId}-contents`);
                                if (contentsDiv) {
                                    contentsDiv.style.display = contentsDiv.style.display === 'none' ? 'block' : 'none';
                                    const toggle = document.getElementById(`${folderId}-toggle`);
                                    if (toggle) {
                                        const currentRotation = parseInt(toggle.style.transform.match(/\d+/)?.[0] || 0);
                                        toggle.style.transform = currentRotation === 0 ? 'rotate(90deg)' : 'rotate(0deg)';
                                    }
                                }
                            }
                        });
                    });
                    
                    // Auto-load ISO images
                    loadAllISOImages();
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Failed to Load Content',
                text: result.error || 'Unable to fetch storage content',
                confirmButtonColor: '#ef4444'
            });
        }
    })
    .catch(error => {
        console.error('Error fetching storage content:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load storage content: ' + error.message,
            confirmButtonColor: '#ef4444'
        });
    });
}

// Function to prompt for directory name and create
async function promptCreateDirectory(storageId, node) {
    const { value: dirName } = await Swal.fire({
        title: 'Create New Directory',
        input: 'text',
        inputLabel: 'Directory Name',
        inputPlaceholder: 'Enter directory name (e.g., my-folder)',
        showCancelButton: true,
        confirmButtonText: 'Create',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        inputValidator: (value) => {
            if (!value) {
                return 'Directory name is required';
            }
            if (!/^[a-zA-Z0-9_\-\.]+$/.test(value)) {
                return 'Only alphanumeric characters, dashes, dots, and underscores allowed';
            }
        }
    });
    
    if (dirName) {
        await createDirectory(storageId, node, dirName);
    }
}

// Function to create directory via API
async function createDirectory(storageId, node, dirName) {
    try {
        const response = await fetch(`${API_URL}/nodes/${node}/storage/${storageId}/create-directory`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({ name: dirName })
        });
        
        const result = await response.json();
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Directory Created',
                text: `Directory "${dirName}" has been created successfully.`,
                confirmButtonColor: '#10b981',
                timer: 3000,
                timerProgressBar: true
            }).then(() => {
                // Refresh the datastore browser
                viewStorageContent(storageId, node);
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Creation Failed',
                text: result.error || 'Unable to create directory',
                confirmButtonColor: '#ef4444'
            });
        }
    } catch (error) {
        console.error('Error creating directory:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to create directory: ' + error.message,
            confirmButtonColor: '#ef4444'
        });
    }
}

// Helper function to convert hex color to RGB
function getColorRGB(hexColor) {
    const hex = hexColor.replace('#', '');
    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);
    return `${r}, ${g}, ${b}`;
}

// Delete content from storage
async function deleteContent(node, storage, volid) {
    const filename = volid.split('/').pop();
    
    const result = await Swal.fire({
        title: 'Delete Content?',
        html: `Are you sure you want to delete <b>${filename}</b>?<br><small>This action cannot be undone.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280'
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const response = await fetch(`${UPLOAD_API_URL}/nodes/${node}/storage/${storage}/content/${encodeURIComponent(volid)}`, {
            method: 'DELETE',
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: `${filename} has been deleted.`,
                confirmButtonColor: '#10b981'
            }).then(() => {
                // Refresh the content view
                viewStorageContent(storage, node);
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Delete Failed',
                text: data.error || 'Unable to delete content',
                confirmButtonColor: '#ef4444'
            });
        }
    } catch (error) {
        console.error('Error deleting content:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to delete content: ' + error.message,
            confirmButtonColor: '#ef4444'
        });
    }
}

// Upload to specific storage (quick action)
function uploadToStorage(storageId, node) {
    console.log('Quick upload to:', storageId, 'on node:', node);
    
    // Open upload modal with pre-selected values
    openUploadISOModal();
    
    // Wait for modal to open, then set values
    setTimeout(() => {
        const nodeSelect = document.getElementById('uploadNode');
        const storageSelect = document.getElementById('uploadStorage');
        
        if (nodeSelect && storageSelect) {
            nodeSelect.value = node;
            updateUploadStorageList();
            
            setTimeout(() => {
                storageSelect.value = storageId;
            }, 100);
        }
    }, 100);
}

// Legacy function for backward compatibility
function viewStorage(storageId) {
    const storage = storageData.find(s => s.storage === storageId);
    if (storage) {
        viewStorageContent(storageId, storage.node || 'cluster');
    }
}

async function deleteStorage(storageId) {
    if (!confirm(`Are you sure you want to delete storage "${storageId}"?`)) {
        return;
    }
    
    try {
        const response = await fetch(`/api/v1/storage/${storageId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Storage deleted successfully!');
            location.reload();
        } else {
            alert('Error deleting storage: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

function refreshData() {
    location.reload();
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.id === 'addStorageModal' || event.target.id === 'uploadISOModal') {
        console.log('Clicked outside modal, closing...');
        event.target.classList.remove('active');
        event.target.style.display = 'none';
    }
}

// Close modal on ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const addModal = document.getElementById('addStorageModal');
        const uploadModal = document.getElementById('uploadISOModal');
        
        if (addModal && addModal.classList.contains('active')) {
            closeAddStorageModal();
        }
        if (uploadModal && uploadModal.classList.contains('active')) {
            closeUploadISOModal();
        }
    }
});

// Refresh storage with actual size data from backend
async function refreshStorageWithActualSize() {
    try {
        // Fetch storage info from backend API
        const response = await fetch(`${API_URL}/storage/info`, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            console.error('Failed to fetch storage info');
            location.reload(); // Fallback to full reload
            return;
        }
        
        const result = await response.json();
        console.log('Storage info:', result);
        
        if (result.success && result.data) {
            // Update table rows with actual storage size data
            const storageData = result.data;
            
            storageData.forEach(storage => {
                const storageId = storage.storage;
                // Find table row for this storage
                const tableRows = document.querySelectorAll('table tbody tr');
                
                tableRows.forEach(row => {
                    const nameCell = row.querySelector('td:nth-child(3)'); // Name column
                    if (nameCell && nameCell.textContent.trim() === storageId) {
                        // Update size columns
                        const totalCell = row.querySelector('td:nth-child(5)'); // TOTAL column
                        const availCell = row.querySelector('td:nth-child(6)'); // AVAILABLE column
                        const usedCell = row.querySelector('td:nth-child(7)'); // USED column
                        
                        if (totalCell && storage.total_gb) {
                            totalCell.textContent = storage.total_gb.toFixed(2) + ' GB';
                        }
                        if (availCell && storage.available_gb) {
                            availCell.textContent = storage.available_gb.toFixed(2) + ' GB';
                        }
                        if (usedCell && storage.used_gb) {
                            usedCell.textContent = storage.used_gb.toFixed(2) + ' GB';
                        }
                    }
                });
            });
            
            console.log('Storage table updated with actual size data');
        }
    } catch (error) {
        console.error('Error refreshing storage:', error);
        location.reload(); // Fallback to full reload
    }
}

// Function to toggle folder contents visibility
function toggleFolderContents(folderId) {
    const contents = document.getElementById(`${folderId}-contents`);
    const toggle = document.getElementById(`${folderId}-toggle`);
    if (contents) {
        contents.style.display = contents.style.display === 'none' ? 'block' : 'none';
        if (toggle) {
            toggle.style.transform = contents.style.display === 'none' ? 'rotate(0deg)' : 'rotate(90deg)';
        }
    }
}

// Function to load folder contents inline (for tree view in datastore browser)
function loadFolderContents(storageId, node, relPath, folderId) {
    const pathParam = new URLSearchParams({ path: relPath }).toString();
    const loadingDiv = document.getElementById(`${folderId}-loading`);
    const contentsDiv = document.getElementById(`${folderId}-contents`);
    
    if (!contentsDiv) return;
    
    // Show loading
    if (loadingDiv) loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 0.5rem;"></i> Loading contents...';
    
    const apiUrl = `${API_URL}/nodes/${node}/storage/${storageId}/content?${pathParam}`;
    console.log('Loading folder contents from:', apiUrl);
    console.log('Params:', { storageId, node, relPath, folderId, API_URL });
    
    // Create abort controller with timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
    
    fetch(apiUrl, {
        credentials: 'include',
        signal: controller.signal
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(result => {
        console.log('Load folder result:', result);
        if (result.success && result.data) {
            const items = result.data;
            
            if (items.length === 0) {
                contentsDiv.innerHTML = '<div style="padding: 1.5rem; text-align: center; color: #6b7280;"><i class="fas fa-folder-open"></i> Folder is empty</div>';
                return;
            }
            
            // Organize items by type
            const organized = {
                'iso': { label: 'ISO Files', icon: 'fa-compact-disc', color: '#8b5cf6', items: [] },
                'directory': { label: 'Sub Folders', icon: 'fa-folder', color: '#f59e0b', items: [] },
                'file': { label: 'Files', icon: 'fa-file', color: '#6b7280', items: [] }
            };
            
            items.forEach(item => {
                const itemType = (item.is_dir || item.content === 'directory') ? 'directory' : (item.content || 'file');
                if (organized[itemType]) {
                    organized[itemType].items.push(item);
                } else {
                    organized['file'].items.push(item);
                }
            });
            
            // Build inline content
            let contentHTML = '';
            Object.entries(organized).forEach(([key, category]) => {
                if (category.items.length > 0) {
                    contentHTML += `
                        <div style="border-top: 1px solid #e5e7eb;">
                            <!-- Category Header -->
                            <div style="padding: 0.75rem 1.5rem; background: #f3f4f6; display: flex; align-items: center; gap: 0.5rem; border-left: 3px solid ${category.color};">
                                <i class="fas ${category.icon}" style="color: ${category.color}; font-size: 0.9rem;"></i>
                                <span style="font-weight: 600; color: #374151; font-size: 0.9rem; flex: 1;">${category.label}</span>
                                <span style="background: ${category.color}; color: white; padding: 0.15rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600;">${category.items.length}</span>
                            </div>
                            
                            <!-- Category Items -->
                            <table style="width: 100%; border-collapse: collapse;">
                                <tbody>
                    `;
                    
                    category.items.forEach((item, idx) => {
                        const itemName = item.name || 'Unknown';
                        let sizeText = '—';
                        if (item.size && !item.is_dir) {
                            if (item.size > 1024 * 1024 * 1024) {
                                sizeText = (item.size / (1024**3)).toFixed(2) + ' GB';
                            } else if (item.size > 1024 * 1024) {
                                sizeText = (item.size / (1024**2)).toFixed(2) + ' MB';
                            } else if (item.size > 1024) {
                                sizeText = (item.size / 1024).toFixed(2) + ' KB';
                            }
                        }
                        
                        const rowBg = idx % 2 === 0 ? '#ffffff' : '#fafafa';
                        contentHTML += `
                            <tr style="background: ${rowBg}; border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 0.75rem 1.5rem; width: 2rem; text-align: center;">
                                    <i class="fas ${category.icon}" style="color: ${category.color}; font-size: 0.9rem;"></i>
                                </td>
                                <td style="padding: 0.75rem 1.5rem; color: #1f2937; font-family: 'Monaco', monospace; font-size: 0.85rem; word-break: break-word;">
                                    ${itemName}
                                </td>
                                <td style="padding: 0.75rem 1.5rem; text-align: right; color: #6b7280; font-size: 0.85rem; width: 100px;">
                                    ${sizeText}
                                </td>
                            </tr>
                        `;
                    });
                    
                    contentHTML += `
                                </tbody>
                            </table>
                        </div>
                    `;
                }
            });
            
            contentsDiv.innerHTML = contentHTML;
            clearTimeout(timeoutId);
        } else {
            console.error('API error:', result.error);
            contentsDiv.innerHTML = `<div style="padding: 1.5rem; text-align: center; color: #ef4444;"><i class="fas fa-exclamation-circle"></i> <strong>Failed to load contents</strong><br><small>${result.error || 'Unknown error'}</small></div>`;
            clearTimeout(timeoutId);
        }
    })
    .catch(error => {
        console.error('Error loading folder contents:', error);
        clearTimeout(timeoutId);
        const errorMsg = error.name === 'AbortError' ? 'Request timeout (10s)' : error.message;
        contentsDiv.innerHTML = `<div style="padding: 1.5rem; text-align: center; color: #ef4444;"><i class="fas fa-exclamation-circle"></i> <strong>Error loading contents</strong><br><small>${errorMsg}</small></div>`;
    });
}

// Page initialization test
// Function to expand/browse a directory - shows in new tab/panel
function expandDirectory(storageId, node, relPath, folderId) {
    console.log('Expanding directory:', storageId, node, relPath, folderId);
    
    // Toggle contents visibility and load if needed
    const contentsDiv = document.getElementById(`${folderId}-contents`);
    if (!contentsDiv) {
        console.error('Contents div not found:', `${folderId}-contents`);
        return;
    }
    
    // If already expanded, just toggle
    if (contentsDiv.style.display !== 'none') {
        contentsDiv.style.display = 'none';
        const toggle = document.getElementById(`${folderId}-toggle`);
        if (toggle) toggle.style.transform = 'rotate(0deg)';
        return;
    }
    
    // Expand and load contents
    contentsDiv.style.display = 'block';
    const toggle = document.getElementById(`${folderId}-toggle`);
    if (toggle) toggle.style.transform = 'rotate(90deg)';
    
    loadFolderContents(storageId, node, relPath, folderId);
}

// Function to prompt and delete directory
async function promptDeleteDirectory(storageId, node, dirName) {
    const result = await Swal.fire({
        title: 'Delete Directory?',
        html: `<p>Are you sure you want to delete <b>${dirName}</b> and all its contents?</p><p style="color: #ef4444; font-size: 0.9em; margin-top: 1em;"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280'
    });
    
    if (result.isConfirmed) {
        await deleteDirectory(storageId, node, dirName);
    }
}

// Function to delete directory via API
async function deleteDirectory(storageId, node, dirName) {
    try {
        const response = await fetch(`${API_URL}/nodes/${node}/storage/${storageId}/delete-directory`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({ name: dirName })
        });
        
        const result = await response.json();
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Directory Deleted',
                text: `Directory "${dirName}" and its contents have been deleted.`,
                confirmButtonColor: '#10b981',
                timer: 3000,
                timerProgressBar: true
            }).then(() => {
                // Refresh the datastore browser
                viewStorageContent(storageId, node);
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Deletion Failed',
                text: result.error || 'Unable to delete directory',
                confirmButtonColor: '#ef4444'
            });
        }
    } catch (error) {
        console.error('Error deleting directory:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to delete directory: ' + error.message,
            confirmButtonColor: '#ef4444'
        });
    }
}

console.log('%c✅ PAGE LOADED - ALL FUNCTIONS DEFINED', 'color: green; font-weight: bold; font-size: 12px;');
console.log('Available functions:');
console.log('  - openFileBrowser(path)');
console.log('  - loadDirectoryContents(path)');
console.log('  - testFullModalFlow()  ← TEST THIS FIRST!');
console.log('  - testTablePopulation()');
console.log('');
console.log('📋 Quick tests:');
console.log('  1. testFullModalFlow() - Shows modal with test data');
console.log('  2. testTablePopulation() - Tests table population');
console.log('  3. openFileBrowser("/mnt") - Main function to test');

// Storage Tab Navigation Function
function switchStorageTab(tabName, buttonElement) {
    // Hide all tab content
    const allTabs = document.querySelectorAll('.storage-tab-content');
    allTabs.forEach(tab => {
        tab.style.display = 'none';
    });
    
    // Show selected tab
    const selectedTab = document.getElementById(tabName + '-tab');
    if (selectedTab) {
        selectedTab.style.display = 'block';
    }
    
    // Update button active state
    const allButtons = document.querySelectorAll('.storage-tab-btn');
    allButtons.forEach(btn => {
        btn.classList.remove('active');
    });
    
    if (buttonElement) {
        buttonElement.classList.add('active');
    }
    
    // Load ISO images if switching to iso-images tab
    if (tabName === 'iso-images') {
        loadAllISOImages();
    }
}
</script>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
</body>
</html>