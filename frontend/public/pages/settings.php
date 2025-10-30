<?php
/**
 * User Settings Page
 */

$config = require __DIR__ . '/../../src/Config/config.php';

use Silo\Utils\Session;
use Silo\Services\ApiService;

Session::requireLogin();

$active = 'settings';
$api = new ApiService();

// Get current user
$currentUser = $api->get('/auth/me');
$user = $currentUser['user'] ?? [];
?>

<?php include __DIR__ . '/../components/header.php'; ?>

<div class="main-wrapper">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Settings</h1>
            <p class="page-subtitle">Manage your account settings</p>
        </div>
        
        <div class="settings-container">
            <!-- Profile Card -->
            <div class="settings-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-user"></i> Profile Information
                    </h2>
                </div>
                
                <div class="card-body">
                    <div class="profile-info">
                        <div class="info-item">
                            <label>Username:</label>
                            <span><?php echo htmlspecialchars($user['username'] ?? ''); ?></span>
                        </div>
                        
                        <div class="info-item">
                            <label>Email:</label>
                            <span><?php echo htmlspecialchars($user['email'] ?? ''); ?></span>
                        </div>
                        
                        <div class="info-item">
                            <label>Full Name:</label>
                            <span><?php echo htmlspecialchars($user['full_name'] ?? 'Not set'); ?></span>
                        </div>
                        
                        <div class="info-item">
                            <label>Role:</label>
                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <label>Account Status:</label>
                            <span class="status-badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Disabled'; ?>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <label>Last Login:</label>
                            <span><?php echo $user['last_login'] ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : 'Never'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Change Password Card -->
            <div class="settings-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-lock"></i> Change Password
                    </h2>
                </div>
                
                <div class="card-body">
                    <form id="changePasswordForm">
                        <div class="form-group">
                            <label for="oldPassword">Current Password</label>
                            <input type="password" id="oldPassword" name="old_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" id="newPassword" name="new_password" required>
                            <small>Minimum 8 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmPassword">Confirm Password</label>
                            <input type="password" id="confirmPassword" name="confirm_password" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Security Card -->
            <div class="settings-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-shield-alt"></i> Security
                    </h2>
                </div>
                
                <div class="card-body">
                    <div class="security-options">
                        <a href="/security/2fa" class="option-card">
                            <i class="fas fa-mobile-alt"></i>
                            <h3>Two-Factor Authentication</h3>
                            <p>Manage your 2FA settings and backup codes</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    .settings-container {
        display: grid;
        gap: 20px;
        max-width: 800px;
    }
    
    .settings-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    .card-title {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .card-body {
        padding: 20px;
    }
    
    .profile-info {
        display: grid;
        gap: 12px;
    }
    
    .info-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px;
        background: #f9fafb;
        border-radius: 6px;
    }
    
    .info-item label {
        font-weight: 600;
        color: #374151;
        min-width: 120px;
    }
    
    .info-item span {
        color: #6b7280;
    }
    
    .role-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .role-admin {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .role-operator {
        background: #fef3c7;
        color: #92400e;
    }
    
    .role-user {
        background: #d1fae5;
        color: #065f46;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-badge.active {
        background: #d1fae5;
        color: #065f46;
    }
    
    .status-badge.inactive {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #374151;
        font-size: 14px;
    }
    
    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
    }
    
    .form-group input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .form-group small {
        display: block;
        margin-top: 5px;
        color: #6b7280;
        font-size: 12px;
    }
    
    .form-actions {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }
    
    .security-options {
        display: grid;
        gap: 12px;
    }
    
    .option-card {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: #f9fafb;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .option-card:hover {
        background: #f0f4ff;
        border-color: #667eea;
        transform: translateX(5px);
    }
    
    .option-card i {
        font-size: 24px;
        color: #667eea;
        width: 50px;
        text-align: center;
    }
    
    .option-card h3 {
        font-size: 14px;
        font-weight: 600;
        margin: 0;
        color: #374151;
    }
    
    .option-card p {
        font-size: 12px;
        color: #6b7280;
        margin: 5px 0 0;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
    }
    
    .alert-success {
        background: #ecfdf5;
        color: #065f46;
        border-left: 4px solid #10b981;
    }
    
    .alert-error {
        background: #fef2f2;
        color: #991b1b;
        border-left: 4px solid #ef4444;
    }
</style>

<script>
const API_URL = '<?php echo "http://{$config['api']['host']}:{$config['api']['port']}{$config['api']['prefix']}"; ?>';

document.getElementById('changePasswordForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const oldPassword = document.getElementById('oldPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    // Validate passwords match
    if (newPassword !== confirmPassword) {
        showAlert('New passwords do not match', 'error');
        return;
    }
    
    // Validate password length
    if (newPassword.length < 8) {
        showAlert('Password must be at least 8 characters', 'error');
        return;
    }
    
    try {
        const response = await fetch(API_URL + '/auth/change-password', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                old_password: oldPassword,
                new_password: newPassword
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Password changed successfully!', 'success');
            document.getElementById('changePasswordForm').reset();
        } else {
            showAlert(data.error || 'Failed to change password', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('An error occurred: ' + error.message, 'error');
    }
});

function showAlert(message, type) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    const container = document.querySelector('.settings-container');
    container.insertBefore(alert, container.firstChild);
    
    setTimeout(() => alert.remove(), 5000);
}
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
