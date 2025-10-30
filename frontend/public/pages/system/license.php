<?php
/**
 * License Management Page
 */

$config = require dirname(__DIR__, 2) . '/../src/Config/config.php';

use Silo\Utils\Session;
use Silo\Services\ApiService;

Session::requireAdmin();

$active = 'system-license';
$api = new ApiService();

// Get license status and device info
$licenseStatus = $api->get('/system/license/status');
$deviceInfo = $api->get('/system/license/device-info');

// Handle API response with defaults
$licenseData = $licenseStatus['data'] ?? [];
$deviceData = $deviceInfo['data'] ?? [];

// Set default values if keys don't exist
$licenseStatus = [
    'license_status' => $licenseData['license_status'] ?? 'inactive',
    'device_id' => $licenseData['device_id'] ?? 'N/A',
    'activation_date' => $licenseData['activation_date'] ?? null,
    'expiry_date' => $licenseData['expiry_date'] ?? null
];

$deviceInfo = [
    'device_info' => [
        'device_id' => $deviceData['device_info']['device_id'] ?? $deviceData['device_id'] ?? 'N/A',
        'hostname' => $deviceData['device_info']['hostname'] ?? $deviceData['hostname'] ?? 'N/A',
        'mac_address' => $deviceData['device_info']['mac_address'] ?? $deviceData['mac_address'] ?? 'N/A'
    ]
];
?>

<?php include dirname(__DIR__, 2) . '/components/header.php'; ?>

<div class="main-wrapper">
    <?php include dirname(__DIR__, 2) . '/components/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">License Management</h1>
            <p class="page-subtitle">Manage your Silo HCI license and device information</p>
        </div>
        
        <div class="license-container">
            <!-- License Status -->
            <div class="content-card license-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-certificate"></i> License Status
                    </h2>
                </div>
                
                <div class="card-body">
                    <div class="status-display">
                        <div class="status-item">
                            <label>License Status:</label>
                            <span class="status-badge <?php echo $licenseStatus['license_status'] == 'active' ? 'active' : 'inactive'; ?>">
                                <?php echo strtoupper($licenseStatus['license_status']); ?>
                            </span>
                        </div>
                        
                        <div class="status-item">
                            <label>Device ID:</label>
                            <span class="device-id"><?php echo $licenseStatus['device_id']; ?></span>
                        </div>
                        
                        <?php if ($licenseStatus['activation_date']): ?>
                        <div class="status-item">
                            <label>Activation Date:</label>
                            <span><?php echo date('Y-m-d H:i:s', strtotime($licenseStatus['activation_date'])); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($licenseStatus['expiry_date']): ?>
                        <div class="status-item">
                            <label>Expiry Date:</label>
                            <span><?php echo date('Y-m-d H:i:s', strtotime($licenseStatus['expiry_date'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Device Information -->
            <div class="content-card license-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-info-circle"></i> Device Information
                    </h2>
                </div>
                
                <div class="card-body">
                    <div class="device-info-display">
                        <div class="info-item">
                            <label>Device ID:</label>
                            <span><?php echo $deviceInfo['device_info']['device_id']; ?></span>
                        </div>
                        
                        <div class="info-item">
                            <label>Hostname:</label>
                            <span><?php echo $deviceInfo['device_info']['hostname']; ?></span>
                        </div>
                        
                        <div class="info-item">
                            <label>MAC Address:</label>
                            <span><?php echo $deviceInfo['device_info']['mac_address']; ?></span>
                        </div>
                    </div>
                    
                    <div class="device-actions">
                        <button class="btn btn-secondary" id="exportBtn">
                            <i class="fas fa-download"></i> Export Device Info
                        </button>
                        <button class="btn btn-secondary" id="copyDeviceIdBtn">
                            <i class="fas fa-copy"></i> Copy Device ID
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- License Activation -->
            <div class="content-card license-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-key"></i> Activate License
                    </h2>
                </div>
                
                <div class="card-body">
                    <form id="licenseForm">
                        <div class="form-group">
                            <label for="licenseKey">License Key</label>
                            <input type="text" id="licenseKey" name="licenseKey" 
                                   placeholder="Enter your license key" required>
                        </div>
                        
                        <div class="form-info">
                            <i class="fas fa-info-circle"></i>
                            <span>Enter the license key you received from the license provider</span>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check"></i> Activate License
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Import Device Info -->
            <div class="content-card license-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-upload"></i> Import Device Info
                    </h2>
                </div>
                
                <div class="card-body">
                    <form id="importForm">
                        <div class="form-group">
                            <label for="deviceInfoFile">Device Info File (JSON)</label>
                            <div class="file-input-wrapper">
                                <input type="file" id="deviceInfoFile" name="deviceInfoFile" 
                                       accept=".json" required>
                                <span class="file-label">Choose JSON file</span>
                            </div>
                        </div>
                        
                        <div class="form-info">
                            <i class="fas fa-info-circle"></i>
                            <span>Upload a device info JSON file for license activation</span>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Import & Activate
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    .license-container {
        display: grid;
        gap: 20px;
        max-width: 920px;
    }

    .license-card .card-body {
        display: grid;
        gap: 20px;
    }

    .status-display,
    .device-info-display {
        display: grid;
        gap: 16px;
    }

    .status-item,
    .info-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 12px 16px;
        border-radius: var(--border-radius-lg);
        border: 1px solid rgba(148, 163, 184, 0.2);
        background: rgba(255, 255, 255, 0.8);
        color: var(--gray-700);
    }

    .status-item label,
    .info-item label {
        width: 160px;
        font-weight: 600;
        color: var(--gray-600);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        border-radius: var(--border-radius-full);
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .status-badge.active {
        background: rgba(16, 185, 129, 0.16);
        color: #047857;
    }

    .status-badge.inactive {
        background: rgba(248, 113, 113, 0.16);
        color: #b91c1c;
    }

    .device-id {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 0.9rem;
        color: var(--gray-800);
    }

    .device-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .device-actions .btn {
        flex: 1;
        min-width: 180px;
        justify-content: center;
    }

    .license-card label {
        display: block;
        font-weight: 600;
        color: var(--gray-600);
        margin-bottom: 6px;
    }

    .license-card input[type="text"] {
        width: 100%;
        padding: 10px 12px;
        border-radius: var(--border-radius);
        border: 1px solid rgba(148, 163, 184, 0.35);
        background: rgba(255, 255, 255, 0.9);
        color: var(--gray-800);
        font-size: 0.95rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .license-card input:focus {
        border-color: var(--blue-500);
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.18);
        outline: none;
    }

    .form-info {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border-radius: var(--border-radius-lg);
        background: rgba(148, 163, 184, 0.12);
        border: 1px solid rgba(148, 163, 184, 0.18);
        color: var(--gray-600);
    }

    .form-info i {
        color: var(--blue-500);
    }

    .file-input-wrapper {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 12px;
    }

    .file-input-wrapper input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .file-input-wrapper .file-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: var(--border-radius-full);
        border: 1px solid rgba(148, 163, 184, 0.35);
        background: rgba(255, 255, 255, 0.85);
        color: var(--gray-600);
        transition: background 0.2s ease, border-color 0.2s ease;
    }

    .file-input-wrapper .file-label:hover {
        background: rgba(148, 163, 184, 0.12);
        border-color: rgba(102, 126, 234, 0.4);
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
    }

    .alert {
        padding: 12px 16px;
        border-radius: var(--border-radius-lg);
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
    }

    .alert-success {
        background: rgba(134, 239, 172, 0.2);
        color: #047857;
        border-left: 4px solid #10b981;
    }

    .alert-error {
        background: rgba(248, 113, 113, 0.18);
        color: #b91c1c;
        border-left: 4px solid #ef4444;
    }
</style>

<script>
const API_URL = '<?php echo "http://{$config['api']['host']}:{$config['api']['port']}{$config['api']['prefix']}"; ?>';

// Copy device ID to clipboard
document.getElementById('copyDeviceIdBtn').addEventListener('click', () => {
    const deviceId = document.querySelector('.device-id').textContent;
    navigator.clipboard.writeText(deviceId).then(() => {
        showAlert('Device ID copied to clipboard!', 'success');
    });
});

// Export device info
document.getElementById('exportBtn').addEventListener('click', async () => {
    try {
        const response = await fetch(API_URL + '/system/license/export', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            // Create downloadable JSON file
            const element = document.createElement('a');
            element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(JSON.stringify(data.data, null, 2)));
            element.setAttribute('download', data.filename);
            element.style.display = 'none';
            document.body.appendChild(element);
            element.click();
            document.body.removeChild(element);
            showAlert('Device info exported successfully!', 'success');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Failed to export device info', 'error');
    }
});

// Handle license activation
document.getElementById('licenseForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const licenseKey = document.getElementById('licenseKey').value;
    const deviceId = document.querySelector('.device-id').textContent;
    
    try {
        const response = await fetch(API_URL + '/system/license/activate', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                device_id: deviceId,
                license_key: licenseKey,
                device_info: {}
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('License activated successfully!', 'success');
            document.getElementById('licenseKey').value = '';
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert(data.error || 'Failed to activate license', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('An error occurred: ' + error.message, 'error');
    }
});

// Handle import device info
document.getElementById('importForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const fileInput = document.getElementById('deviceInfoFile');
    const file = fileInput.files[0];
    
    if (!file) {
        showAlert('Please select a file', 'error');
        return;
    }
    
    try {
        const content = await file.text();
        const deviceInfo = JSON.parse(content);
        const licenseKey = 'IMPORT-' + Date.now();
        
        const response = await fetch(API_URL + '/system/license/import', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                device_info: JSON.stringify(deviceInfo),
                license_key: licenseKey
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Device info imported successfully!', 'success');
            document.getElementById('importForm').reset();
        } else {
            showAlert(data.error || 'Failed to import device info', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Invalid file format or error: ' + error.message, 'error');
    }
});

function showAlert(message, type) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    const container = document.querySelector('.license-container');
    container.insertBefore(alert, container.firstChild);
    
    setTimeout(() => alert.remove(), 5000);
}

// Update file label when file is selected
document.getElementById('deviceInfoFile').addEventListener('change', (e) => {
    const fileName = e.target.files[0]?.name || 'Choose JSON file';
    const label = document.querySelector('.file-label');
    label.textContent = fileName;
});
</script>

<?php include dirname(__DIR__, 2) . '/components/footer.php'; ?>
