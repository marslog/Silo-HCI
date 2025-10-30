<?php
$config = require __DIR__ . '/../../src/Config/config.php';
$active = 'monitoring';

use Silo\Services\ApiService;
$api = new ApiService();

// Get monitoring data with defaults
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
            <h1 class="page-title">Monitoring</h1>
            <div class="page-actions">
                <button class="btn btn-secondary" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="dashboard-grid">
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
        
        <!-- Monitoring Details -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Real-time Monitoring</h2>
            </div>
            <div class="card-body">
                <div style="text-align: center; padding: 2rem; color: #6b7280;">
                    <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p style="font-size: 1.125rem;">Advanced monitoring charts and graphs coming soon!</p>
                    <p style="font-size: 0.875rem; margin-top: 0.5rem;">CPU, Memory, Disk I/O, Network traffic visualization</p>
                </div>
            </div>
        </div>
        
    </main>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>

<script>
function refreshData() {
    location.reload();
}
</script>
