    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="/dashboard" class="sidebar-logo">
                <img src="/assets/images/silo-logo.svg" alt="Silo Platform Logo" class="sidebar-logo-img">
                <div class="sidebar-logo-text">
                    <span>SILO</span>
                    <span>HCI Platform</span>
                </div>
            </a>
        </div>
        
        <!-- User Profile Section -->
        <?php 
            use Silo\Utils\Session;
            Session::start();
            $username = Session::getUsername() ?? 'User';
            $userRole = Session::getUserRole() ?? 'user';
        ?>
        <div class="sidebar-user-profile">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                <div class="user-role"><?php echo ucfirst($userRole); ?></div>
            </div>
            <button class="profile-toggle" onclick="toggleProfileMenu()">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            
            <!-- Profile Dropdown Menu -->
            <div class="profile-dropdown" id="profileDropdown">
                <a href="/settings" class="profile-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="/security/2fa" class="profile-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Security</span>
                </a>
                <div class="profile-divider"></div>
                <a href="#" class="profile-item logout-item" onclick="handleLogout(event)">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-label">Menu</div>
                
                <a href="/dashboard" class="nav-item <?php echo $active === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>
                <a href="/nodes" class="nav-item <?php echo $active === 'nodes' ? 'active' : ''; ?>">
                    <i class="fas fa-server"></i>
                    <span>Nodes</span>
                </a>
                <a href="/vms" class="nav-item <?php echo ($active === 'vms' || $active === 'vm') ? 'active' : ''; ?>">
                    <i class="fas fa-desktop"></i>
                    <span>Virtual Machines</span>
                </a>
                <a href="/containers" class="nav-item <?php echo ($active === 'containers' || $active === 'lxc') ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i>
                    <span>Containers</span>
                </a>
                <a href="/storage" class="nav-item <?php echo $active === 'storage' ? 'active' : ''; ?>">
                    <i class="fas fa-hdd"></i>
                    <span>Storage</span>
                </a>
                <a href="/network" class="nav-item <?php echo $active === 'network' ? 'active' : ''; ?>">
                    <i class="fas fa-network-wired"></i>
                    <span>Network</span>
                </a>
                <a href="/backup" class="nav-item <?php echo $active === 'backup' ? 'active' : ''; ?>">
                    <i class="fas fa-save"></i>
                    <span>Backup</span>
                </a>
                <a href="/monitoring" class="nav-item <?php echo $active === 'monitoring' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Monitoring</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-label">Administration</div>
                
                <div class="nav-group">
                    <button class="nav-item nav-group-title" onclick="toggleSystemMenu()">
                        <i class="fas fa-cog"></i>
                        <span>System</span>
                        <i class="fas fa-chevron-down nav-chevron"></i>
                    </button>
                    
                    <div class="nav-submenu" id="systemMenu">
                        <a href="/system/generate" class="nav-subitem <?php echo $active === 'system-generate' ? 'active' : ''; ?>">
                            <i class="fas fa-sliders-h"></i>
                            <span>Generate Settings</span>
                        </a>
                        <a href="/system/license" class="nav-subitem <?php echo $active === 'system-license' ? 'active' : ''; ?>">
                            <i class="fas fa-certificate"></i>
                            <span>License</span>
                        </a>
                        <a href="/system/account" class="nav-subitem <?php echo $active === 'system-account' ? 'active' : ''; ?>">
                            <i class="fas fa-users-cog"></i>
                            <span>Account Management</span>
                        </a>
                    </div>
                </div>
            </div>
        </nav>
        <script>
            function toggleSystemMenu() {
                const menu = document.getElementById('systemMenu');
                const btn = document.querySelector('.nav-group-title');
                if (!menu || !btn) {
                    console.warn('System menu toggle unavailable.');
                    return;
                }
                menu.classList.toggle('active');
                const chevron = btn.querySelector('.nav-chevron');
                if (chevron) {
                    chevron.style.transform = menu.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            }
            
            function toggleProfileMenu() {
                const menu = document.getElementById('profileDropdown');
                menu.classList.toggle('active');
            }
            
            // Close profile menu when clicking outside
            document.addEventListener('click', function(event) {
                const profile = document.querySelector('.sidebar-user-profile');
                const dropdown = document.getElementById('profileDropdown');
                if (profile && !profile.contains(event.target) && dropdown) {
                    dropdown.classList.remove('active');
                }
            });
            
            // Logout handler
            async function handleLogout(event) {
                event.preventDefault();
                
                try {
                    const response = await fetch('/api/v1/auth/logout', {
                        method: 'POST',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    // Always clear PHP session
                    try {
                        await fetch('/logout', {
                            method: 'POST',
                            credentials: 'same-origin'
                        });
                    } catch (sessionError) {
                        console.warn('Session clean-up failed:', sessionError);
                    }

                    if (response.ok) {
                        // Redirect to login
                        window.location.href = '/login?message=Logged out successfully';
                    } else {
                        console.error('Logout failed');
                        // Fallback logout
                        window.location.href = '/login';
                    }
                } catch (error) {
                    console.error('Logout error:', error);
                    window.location.href = '/login';
                }
            }
            
            // Auto-show system menu if active
            const systemMenu = document.getElementById('systemMenu');
            if (systemMenu) {
                const systemItems = systemMenu.querySelectorAll('.nav-subitem.active');
                if (systemItems.length > 0) {
                    systemMenu.classList.add('active');
                    const chevron = document.querySelector('.nav-chevron');
                    if (chevron) {
                        chevron.style.transform = 'rotate(180deg)';
                    }
                }
            }
        </script>
    </aside>

