<nav class="top-nav">
    <div class="nav-container">
        <div class="nav-brand">
            <img src="/assets/images/silo-logo.svg" alt="Silo HCI" class="nav-logo">
            <span class="nav-brand-text">SILO <span class="brand-subtitle">HCI PLATFORM</span></span>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item nav-dashboard <?php echo ($active ?? '') === 'dashboard' ? 'active' : ''; ?>">
                <a href="/dashboard" class="nav-link">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item nav-nodes <?php echo ($active ?? '') === 'nodes' ? 'active' : ''; ?>">
                <a href="/nodes" class="nav-link">
                    <i class="fas fa-server"></i>
                    <span>Nodes</span>
                </a>
            </li>
            <li class="nav-item nav-vms <?php echo ($active ?? '') === 'vms' ? 'active' : ''; ?>">
                <a href="/vms" class="nav-link">
                    <i class="fas fa-desktop"></i>
                    <span>VMs</span>
                </a>
            </li>
            <li class="nav-item nav-lxc <?php echo ($active ?? '') === 'containers' ? 'active' : ''; ?>">
                <a href="/containers" class="nav-link">
                    <i class="fas fa-cube"></i>
                    <span>LXC</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($active ?? '') === 'storage' ? 'active' : ''; ?> nav-storage">
                <a href="/storage" class="nav-link">
                    <i class="fas fa-hdd"></i>
                    <span>Storage</span>
                </a>
                <div class="nav-sub-dropdown">
                    <a href="/storage#summary" class="dropdown-item"><i class="fas fa-chart-bar"></i> Summary</a>
                    <a href="/storage#datastores" class="dropdown-item"><i class="fas fa-database"></i> Datastores</a>
                    <a href="/storage#iso-images" class="dropdown-item"><i class="fas fa-compact-disc"></i> ISO Images</a>
                    <a href="/storage#content" class="dropdown-item"><i class="fas fa-folder-open"></i> Content Browser</a>
                </div>
            </li>
            <!-- Group less-used items into a More dropdown to reduce top-level menus -->
            <li class="nav-item nav-more">
                <div class="nav-link nav-more-trigger">
                    <i class="fas fa-ellipsis-h"></i>
                    <span>More</span>
                </div>
                <div class="nav-user-dropdown nav-more-dropdown">
                    <a href="/network" class="dropdown-item">
                        <i class="fas fa-network-wired"></i> Network
                    </a>
                    <a href="/backup" class="dropdown-item">
                        <i class="fas fa-save"></i> Backup
                    </a>
                </div>
            </li>
        </ul>
        
        <div class="nav-actions">
            <button class="nav-icon-btn" title="Notifications">
                <i class="fas fa-bell"></i>
                <span class="badge">3</span>
            </button>
            <div class="nav-user">
                <img src="/assets/images/default-avatar.svg" alt="User" class="nav-avatar">
                <span class="nav-username"><?php echo $_SESSION['username'] ?? 'Admin'; ?></span>
                <div class="nav-user-dropdown">
                    <a href="/settings" class="dropdown-item">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <a href="/logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>
