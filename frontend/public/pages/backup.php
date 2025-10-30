<?php
$config = require __DIR__ . '/../../src/Config/config.php';
$active = 'backup';
?>

<?php include __DIR__ . '/../components/header.php'; ?>

<div class="main-wrapper">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Backup</h1>
            <div class="page-actions">
                <button class="btn btn-primary" onclick="alert('Create backup feature coming soon!')">
                    <i class="fas fa-plus"></i> New Backup
                </button>
                <button class="btn btn-secondary" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        
        <div class="content-card">
            <div class="card-body" style="text-align: center; padding: 3rem;">
                <i class="fas fa-save" style="font-size: 4rem; color: #10b981; margin-bottom: 1.5rem;"></i>
                <h2 style="font-size: 1.5rem; margin-bottom: 1rem;">Backup Management</h2>
                <p style="color: #6b7280;">Backup scheduling and management features coming soon!</p>
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
