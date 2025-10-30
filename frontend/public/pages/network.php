<?php
$config = require __DIR__ . '/../../src/Config/config.php';
$active = 'network';

use Silo\Services\ApiService;
$api = new ApiService();

// Get network interfaces from all nodes
$nodesResponse = $api->get('/nodes');
$nodes = $nodesResponse['data'] ?? [];

$allInterfaces = [];
foreach ($nodes as $node) {
    $networkResponse = $api->get("/nodes/{$node['node']}/network");
    if (isset($networkResponse['data'])) {
        foreach ($networkResponse['data'] as $interface) {
            $interface['node'] = $node['node'];
            $allInterfaces[] = $interface;
        }
    }
}

// Separate by type
$bridges = array_filter($allInterfaces, fn($i) => ($i['type'] ?? '') === 'bridge');
$physical = array_filter($allInterfaces, fn($i) => ($i['type'] ?? '') === 'eth');
$bonds = array_filter($allInterfaces, fn($i) => ($i['type'] ?? '') === 'bond');
$vlans = array_filter($allInterfaces, fn($i) => ($i['type'] ?? '') === 'vlan');
?>

<?php include __DIR__ . '/../components/header.php'; ?>

<div class="main-wrapper">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Network</h1>
            <div class="page-actions">
                <button class="btn btn-secondary" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        
        <!-- Network Overview -->
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 2rem;">
            <div class="dashboard-card">
                <div class="card-icon bg-blue-500">
                    <i class="fas fa-network-wired"></i>
                </div>
                <div class="card-content">
                    <div class="card-value"><?php echo count($allInterfaces); ?></div>
                    <div class="card-label">Total Interfaces</div>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon bg-purple-500">
                    <i class="fas fa-bridge"></i>
                </div>
                <div class="card-content">
                    <div class="card-value"><?php echo count($bridges); ?></div>
                    <div class="card-label">Bridges</div>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon bg-green-500">
                    <i class="fas fa-ethernet"></i>
                </div>
                <div class="card-content">
                    <div class="card-value"><?php echo count($physical); ?></div>
                    <div class="card-label">Physical NICs</div>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon bg-yellow-500">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="card-content">
                    <div class="card-value">
                        <?php echo count(array_filter($allInterfaces, fn($i) => isset($i['active']) && $i['active'])); ?>
                    </div>
                    <div class="card-label">Active</div>
                </div>
            </div>
        </div>
        
        <!-- Bridge Interfaces -->
        <?php if (!empty($bridges)): ?>
        <div class="content-card" style="margin-bottom: 1.5rem;">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-bridge"></i> Bridge Interfaces
                </h2>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Interface</th>
                            <th>Node</th>
                            <th>IP Address</th>
                            <th>Gateway</th>
                            <th>Bridge Ports</th>
                            <th>Status</th>
                            <th>Autostart</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bridges as $iface): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($iface['iface'] ?? 'N/A'); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($iface['node'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if (isset($iface['address'])): ?>
                                        <code style="background: #f3f4f6; padding: 0.25rem 0.5rem; border-radius: 0.25rem;">
                                            <?php echo htmlspecialchars($iface['address']); ?>
                                            <?php if (isset($iface['netmask'])): ?>
                                                /<?php echo htmlspecialchars($iface['netmask']); ?>
                                            <?php endif; ?>
                                        </code>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($iface['gateway'])): ?>
                                        <code style="background: #f3f4f6; padding: 0.25rem 0.5rem; border-radius: 0.25rem;">
                                            <?php echo htmlspecialchars($iface['gateway']); ?>
                                        </code>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 0.875rem;">
                                    <?php echo htmlspecialchars($iface['bridge_ports'] ?? '-'); ?>
                                </td>
                                <td>
                                    <?php if (isset($iface['active']) && $iface['active']): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($iface['autostart']) && $iface['autostart']): ?>
                                        <i class="fas fa-check" style="color: #10b981;"></i>
                                    <?php else: ?>
                                        <i class="fas fa-times" style="color: #ef4444;"></i>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Physical Interfaces -->
        <?php if (!empty($physical)): ?>
        <div class="content-card" style="margin-bottom: 1.5rem;">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-ethernet"></i> Physical Network Interfaces
                </h2>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Interface</th>
                            <th>Node</th>
                            <th>Type</th>
                            <th>Method</th>
                            <th>IP Address</th>
                            <th>Status</th>
                            <th>Alt Names</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($physical as $iface): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($iface['iface'] ?? 'N/A'); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($iface['node'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo strtoupper($iface['type'] ?? 'unknown'); ?>
                                    </span>
                                </td>
                                <td style="font-size: 0.875rem;">
                                    <?php echo htmlspecialchars($iface['method'] ?? 'N/A'); ?>
                                </td>
                                <td>
                                    <?php if (isset($iface['address'])): ?>
                                        <code style="background: #f3f4f6; padding: 0.25rem 0.5rem; border-radius: 0.25rem;">
                                            <?php echo htmlspecialchars($iface['address']); ?>
                                        </code>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($iface['active']) && $iface['active']): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 0.75rem; color: #6b7280;">
                                    <?php 
                                    if (isset($iface['altnames']) && is_array($iface['altnames'])) {
                                        echo htmlspecialchars(implode(', ', $iface['altnames']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- All Interfaces (if no specific types) -->
        <?php if (empty($bridges) && empty($physical)): ?>
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Network Interfaces</h2>
            </div>
            <div class="card-body">
                <?php if (empty($allInterfaces)): ?>
                    <div style="text-align: center; padding: 3rem; color: #9ca3af;">
                        <i class="fas fa-network-wired" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <p style="font-size: 1.125rem;">No network interfaces found</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Interface</th>
                                <th>Node</th>
                                <th>Type</th>
                                <th>IP Address</th>
                                <th>Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allInterfaces as $iface): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($iface['iface'] ?? 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($iface['node'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo strtoupper($iface['type'] ?? 'unknown'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (isset($iface['address'])): ?>
                                            <code style="background: #f3f4f6; padding: 0.25rem 0.5rem; border-radius: 0.25rem;">
                                                <?php echo htmlspecialchars($iface['address']); ?>
                                            </code>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($iface['method'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if (isset($iface['active']) && $iface['active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
    </main>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>

<script>
function refreshData() {
    location.reload();
}
</script>
