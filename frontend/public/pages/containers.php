<?php
$config = require __DIR__ . '/../../src/Config/config.php';
$active = 'lxc';

use Silo\Services\ApiService;
$api = new ApiService();

// Get containers from all nodes
$nodesResponse = $api->get('/nodes');
$nodes = $nodesResponse['data'] ?? [];

$allContainers = [];
foreach ($nodes as $node) {
    $lxcResponse = $api->get("/nodes/{$node['node']}/lxc");
    if (isset($lxcResponse['data'])) {
        foreach ($lxcResponse['data'] as $container) {
            $container['node'] = $node['node'];
            $allContainers[] = $container;
        }
    }
}
?>

<?php include __DIR__ . '/../components/header.php'; ?>

<div class="main-wrapper">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">LXC Containers</h1>
            <div class="page-actions">
                <button class="btn btn-primary" onclick="alert('Create container feature coming soon!')">
                    <i class="fas fa-plus"></i> Create Container
                </button>
                <button class="btn btn-secondary" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        
        <!-- Containers Overview -->
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 2rem;">
            <div class="dashboard-card">
                <div class="card-icon bg-purple-500">
                    <i class="fas fa-box"></i>
                </div>
                <div class="card-content">
                    <div class="card-value"><?php echo count($allContainers); ?></div>
                    <div class="card-label">Total Containers</div>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon bg-green-500">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="card-content">
                    <div class="card-value">
                        <?php echo count(array_filter($allContainers, function($ct) { return ($ct['status'] ?? '') === 'running'; })); ?>
                    </div>
                    <div class="card-label">Running</div>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon bg-yellow-500">
                    <i class="fas fa-stop-circle"></i>
                </div>
                <div class="card-content">
                    <div class="card-value">
                        <?php echo count(array_filter($allContainers, function($ct) { return ($ct['status'] ?? '') === 'stopped'; })); ?>
                    </div>
                    <div class="card-label">Stopped</div>
                </div>
            </div>
        </div>
        
        <!-- Containers Table -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Containers List</h2>
            </div>
            <div class="card-body">
                <?php if (empty($allContainers)): ?>
                    <div style="text-align: center; padding: 3rem; color: #9ca3af;">
                        <i class="fas fa-box" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <p style="font-size: 1.125rem;">No containers found</p>
                        <p style="font-size: 0.875rem; margin-top: 0.5rem;">Create your first container to get started</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Node</th>
                                <th>Status</th>
                                <th>CPU</th>
                                <th>Memory</th>
                                <th>Disk</th>
                                <th>Uptime</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allContainers as $container): ?>
                                <tr>
                                    <td><strong><?php echo $container['vmid'] ?? 'N/A'; ?></strong></td>
                                    <td><?php echo htmlspecialchars($container['name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($container['node'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo ($container['status'] ?? '') === 'running' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($container['status'] ?? 'unknown'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo isset($container['cpu']) ? number_format($container['cpu'] * 100, 1) . '%' : 'N/A'; ?></td>
                                    <td><?php echo isset($container['mem'], $container['maxmem']) ? round($container['mem'] / 1024 / 1024 / 1024, 2) . ' / ' . round($container['maxmem'] / 1024 / 1024 / 1024, 2) . ' GB' : 'N/A'; ?></td>
                                    <td><?php echo isset($container['disk']) ? round($container['disk'] / 1024 / 1024 / 1024, 2) . ' GB' : 'N/A'; ?></td>
                                    <td><?php echo isset($container['uptime']) ? floor($container['uptime'] / 3600) . 'h' : 'N/A'; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="viewContainer(<?php echo $container['vmid']; ?>)">
                                            <i class="fas fa-eye"></i>
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

<?php include __DIR__ . '/../components/footer.php'; ?>

<script>
function refreshData() {
    location.reload();
}

function viewContainer(vmid) {
    alert('Container details for ID: ' + vmid + ' - Coming soon!');
}
</script>
