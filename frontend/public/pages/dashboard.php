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
        
        <!-- Nodes Table -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Cluster Nodes</h2>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Node</th>
                            <th>Status</th>
                            <th>CPU</th>
                            <th>Memory</th>
                            <th>Uptime</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($nodes['data'])): ?>
                            <?php foreach ($nodes['data'] as $node): ?>
                                <tr>
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
                                    <td>
                                        <?php echo isset($node['uptime']) ? formatUptime($node['uptime']) : 'N/A'; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="viewNode('<?php echo $node['node']; ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No nodes found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
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

<script>
function refreshDashboard() {
    location.reload();
}

function viewNode(node) {
    window.location.href = '/nodes/' + node;
}
</script>
