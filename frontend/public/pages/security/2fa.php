<?php
/**
 * 2FA Security Settings Page
 */

$config = require dirname(__DIR__, 2) . '/../src/Config/config.php';

use Silo\Utils\Session;
use Silo\Services\ApiService;

Session::requireLogin();

$active = 'security-2fa';
$api = new ApiService();

// Get 2FA status
$status = $api->get('/auth/totp/status');
?>

<?php include dirname(__DIR__, 2) . '/components/header.php'; ?>

<div class="main-wrapper">
    <?php include dirname(__DIR__, 2) . '/components/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Security Settings - Two-Factor Authentication</h1>
            <p class="page-subtitle">Enhance your account security with 2FA</p>
        </div>
        
        <div class="security-container">
            <!-- 2FA Status Card -->
            <div class="security-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-shield-alt"></i> Two-Factor Authentication Status
                    </h2>
                </div>
                
                <div class="card-body">
                    <div class="status-section">
                        <div class="status-display">
                            <span class="status-label">Status:</span>
                            <span class="status-badge <?php echo $status['totp_enabled'] ? 'enabled' : 'disabled'; ?>">
                                <?php echo $status['totp_enabled'] ? 'Enabled' : 'Disabled'; ?>
                            </span>
                        </div>
                        
                        <p class="status-description">
                            <?php if ($status['totp_enabled']): ?>
                                Two-Factor Authentication is currently enabled on your account. You must enter a code from your authenticator app when logging in.
                            <?php else: ?>
                                Two-Factor Authentication is not enabled. Enabling it will add an extra layer of security to your account.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Enable 2FA Card -->
            <?php if (!$status['totp_enabled']): ?>
            <div class="security-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-qrcode"></i> Enable Two-Factor Authentication
                    </h2>
                </div>
                
                <div class="card-body">
                    <div class="setup-steps">
                        <h3>Setup Instructions:</h3>
                        <ol>
                            <li>Download an authenticator app (Google Authenticator, Microsoft Authenticator, Authy, etc.)</li>
                            <li>Click "Generate QR Code" below</li>
                            <li>Scan the QR code with your authenticator app</li>
                            <li>Enter the 6-digit code from your app to verify</li>
                            <li>Save your backup codes in a safe place</li>
                        </ol>
                    </div>
                    
                    <div class="setup-section" id="setupSection">
                        <button class="btn btn-primary" id="generateQrBtn">
                            <i class="fas fa-qrcode"></i> Generate QR Code
                        </button>
                    </div>
                    
                    <!-- QR Code Section (Hidden initially) -->
                    <div class="qr-section" id="qrSection" style="display: none;">
                        <div class="qr-container">
                            <img id="qrCode" src="" alt="QR Code">
                        </div>
                        
                        <div class="manual-entry">
                            <label>Or enter manually:</label>
                            <input type="text" id="manualSecret" readonly class="secret-input">
                            <button type="button" class="btn btn-small" onclick="copySecret()">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                        
                        <div class="verification-section">
                            <label for="verifyCode">Enter 6-digit code from your authenticator app:</label>
                            <input type="text" id="verifyCode" placeholder="000000" maxlength="6" inputmode="numeric">
                            <button type="button" class="btn btn-primary" onclick="verifyCode()">
                                <i class="fas fa-check"></i> Verify & Enable
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Disable 2FA Card -->
            <div class="security-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-times-circle"></i> Disable Two-Factor Authentication
                    </h2>
                </div>
                
                <div class="card-body">
                    <p class="warning-text">
                        <i class="fas fa-exclamation-triangle"></i>
                        Disabling 2FA will reduce the security of your account.
                    </p>
                    
                    <form id="disableTotpForm">
                        <div class="form-group">
                            <label for="disablePassword">Enter your password to confirm:</label>
                            <input type="password" id="disablePassword" name="password" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-minus-circle"></i> Disable 2FA
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Backup Codes Card -->
            <div class="security-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-key"></i> Backup Codes
                    </h2>
                </div>
                
                <div class="card-body">
                    <p class="info-text">
                        Backup codes can be used to access your account if you lose access to your authenticator app. Keep them in a safe place.
                    </p>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="regenerateCodesBtn">
                            <i class="fas fa-sync-alt"></i> Regenerate Backup Codes
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<style>
    .security-container {
        display: grid;
        gap: 20px;
        max-width: 800px;
    }
    
    .security-card {
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
    
    .status-section {
        display: grid;
        gap: 15px;
    }
    
    .status-display {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: #f9fafb;
        border-radius: 8px;
    }
    
    .status-label {
        font-weight: 600;
        color: #374151;
        min-width: 100px;
    }
    
    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-badge.enabled {
        background: #d1fae5;
        color: #065f46;
    }
    
    .status-badge.disabled {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .status-description {
        color: #6b7280;
        font-size: 14px;
        line-height: 1.6;
    }
    
    .setup-steps {
        background: #f0f4ff;
        border-left: 4px solid #667eea;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
    }
    
    .setup-steps h3 {
        margin-top: 0;
        color: #667eea;
        font-size: 14px;
    }
    
    .setup-steps ol {
        margin: 10px 0;
        padding-left: 20px;
        font-size: 13px;
        color: #374151;
    }
    
    .setup-steps li {
        margin-bottom: 8px;
    }
    
    .setup-section {
        text-align: center;
        padding: 20px 0;
    }
    
    .qr-section {
        display: grid;
        gap: 20px;
        padding: 20px 0;
        border-top: 1px solid #e5e7eb;
        margin-top: 20px;
    }
    
    .qr-container {
        text-align: center;
    }
    
    #qrCode {
        width: 250px;
        height: 250px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        padding: 10px;
        background: white;
    }
    
    .manual-entry {
        display: grid;
        gap: 10px;
        padding: 15px;
        background: #f9fafb;
        border-radius: 8px;
    }
    
    .manual-entry label {
        font-size: 13px;
        color: #374151;
        font-weight: 500;
    }
    
    .secret-input {
        font-family: 'Courier New', monospace;
        font-size: 13px;
        padding: 10px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
    }
    
    .verification-section {
        display: grid;
        gap: 10px;
        padding: 20px;
        background: #f9fafb;
        border-radius: 8px;
        border-left: 4px solid #667eea;
    }
    
    .verification-section label {
        font-size: 14px;
        font-weight: 500;
        color: #374151;
    }
    
    #verifyCode {
        padding: 10px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 18px;
        text-align: center;
        letter-spacing: 5px;
    }
    
    #verifyCode:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .warning-text {
        background: #fef2f2;
        border-left: 4px solid #ef4444;
        padding: 12px;
        border-radius: 6px;
        color: #991b1b;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .info-text {
        background: #f0f4ff;
        border-left: 4px solid #667eea;
        padding: 12px;
        border-radius: 6px;
        color: #374151;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
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
    
    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #e5e7eb;
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
    
    .btn-secondary {
        background: #e5e7eb;
        color: #374151;
    }
    
    .btn-secondary:hover {
        background: #d1d5db;
    }
    
    .btn-danger {
        background: #ef4444;
        color: white;
    }
    
    .btn-danger:hover {
        background: #dc2626;
    }
    
    .btn-small {
        padding: 6px 12px;
        font-size: 12px;
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

// Generate QR Code
document.getElementById('generateQrBtn').addEventListener('click', async () => {
    try {
        const response = await fetch(API_URL + '/auth/totp/enable', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show QR section
            document.getElementById('qrSection').style.display = 'grid';
            document.getElementById('setupSection').style.display = 'none';
            
            // Display QR code
            document.getElementById('qrCode').src = data.qrcode;
            document.getElementById('manualSecret').value = data.secret;
        } else {
            showAlert(data.error || 'Failed to generate QR code', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('An error occurred: ' + error.message, 'error');
    }
});

// Copy secret to clipboard
function copySecret() {
    const secret = document.getElementById('manualSecret').value;
    navigator.clipboard.writeText(secret).then(() => {
        showAlert('Secret copied to clipboard!', 'success');
    });
}

// Verify 2FA code
async function verifyCode() {
    const code = document.getElementById('verifyCode').value;
    
    if (code.length !== 6 || !/^\d+$/.test(code)) {
        showAlert('Please enter a valid 6-digit code', 'error');
        return;
    }
    
    try {
        const response = await fetch(API_URL + '/auth/totp/verify-setup', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ code })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('2FA enabled successfully! Save your backup codes.', 'success');
            
            // Show backup codes
            const codes = data.backup_codes.join('\n');
            const element = document.createElement('a');
            element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(codes));
            element.setAttribute('download', 'backup-codes.txt');
            element.style.display = 'none';
            document.body.appendChild(element);
            element.click();
            document.body.removeChild(element);
            
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert(data.error || 'Invalid code', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('An error occurred: ' + error.message, 'error');
    }
}

// Disable 2FA
document.getElementById('disableTotpForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const password = document.getElementById('disablePassword').value;
    
    if (!confirm('Are you sure you want to disable 2FA? This will reduce your account security.')) {
        return;
    }
    
    try {
        const response = await fetch(API_URL + '/auth/totp/disable', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('2FA disabled successfully', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.error || 'Failed to disable 2FA', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('An error occurred: ' + error.message, 'error');
    }
});

// Regenerate backup codes
document.getElementById('regenerateCodesBtn')?.addEventListener('click', async () => {
    const password = prompt('Enter your password to regenerate backup codes:');
    
    if (!password) return;
    
    try {
        const response = await fetch(API_URL + '/auth/totp/regenerate-backup-codes', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Backup codes regenerated successfully!', 'success');
            
            // Download backup codes
            const codes = data.backup_codes.join('\n');
            const element = document.createElement('a');
            element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(codes));
            element.setAttribute('download', 'backup-codes-new.txt');
            element.style.display = 'none';
            document.body.appendChild(element);
            element.click();
            document.body.removeChild(element);
        } else {
            showAlert(data.error || 'Failed to regenerate backup codes', 'error');
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
    
    const container = document.querySelector('.security-container');
    container.insertBefore(alert, container.firstChild);
    
    setTimeout(() => alert.remove(), 5000);
}
</script>

<?php include dirname(__DIR__, 2) . '/components/footer.php'; ?>
