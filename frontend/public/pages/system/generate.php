<?php
/**
 * Generate Settings Page
 */

$config = require dirname(__DIR__, 2) . '/../src/Config/config.php';

use Silo\Utils\Session;
use Silo\Services\ApiService;

Session::requireAdmin();

$active = 'system-generate';
$api = new ApiService();

// Get current datetime and NTP settings with defaults
$datetimeResponse = $api->get('/system/generate/datetime');
$ntpResponse = $api->get('/system/generate/ntp');

$datetime = $datetimeResponse['data'] ?? [
    'date' => date('Y-m-d'),
    'time' => date('H:i:s')
];

$ntp = $ntpResponse['data'] ?? [
    'server' => 'pool.ntp.org',
    'enabled' => false
];
?>

<?php include dirname(__DIR__, 2) . '/components/header.php'; ?>

<div class="main-wrapper">
    <?php include dirname(__DIR__, 2) . '/components/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Generate Settings</h1>
            <p class="page-subtitle">Configure system date, time, and NTP settings</p>
        </div>
        
        <div class="settings-container">
            <!-- Date/Time Settings -->
            <div class="content-card settings-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-calendar-alt"></i> Date & Time Settings
                    </h2>
                </div>
                
                <div class="card-body">
                    <form id="datetimeForm">
                        <div class="form-row">
                            <div class="form-group" style="flex: 1;">
                                <label for="date">Date</label>
                                <input type="date" id="date" name="date" required>
                            </div>
                            
                            <div class="form-group" style="flex: 1;">
                                <label for="time">Time</label>
                                <input type="time" id="time" name="time" required>
                            </div>
                        </div>
                        
                        <div class="form-info">
                            <i class="fas fa-info-circle"></i>
                            <span>Current system time: <strong id="currentTime">--:--:--</strong></span>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Date & Time
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- NTP Settings -->
            <div class="content-card settings-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-clock"></i> NTP Server Settings
                    </h2>
                </div>
                
                <div class="card-body">
                    <form id="ntpForm">
                        <div class="form-group">
                            <label for="ntpServer">NTP Server</label>
                            <input type="text" id="ntpServer" name="ntpServer" 
                                   placeholder="e.g., pool.ntp.org" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="ntpEnabled" name="ntpEnabled">
                                <span>Enable NTP Synchronization</span>
                            </label>
                        </div>
                        
                        <div class="form-info">
                            <i class="fas fa-info-circle"></i>
                            <span>NTP will automatically sync your system clock with the specified server</span>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save NTP Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    .settings-container {
        display: grid;
        gap: 20px;
        max-width: 820px;
    }

    .settings-card .card-body {
        display: grid;
        gap: 20px;
    }

    .form-row {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }

    .form-row .form-group {
        min-width: 220px;
    }

    .settings-card label {
        display: block;
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--gray-700);
        margin-bottom: 6px;
    }

    .settings-card input[type="date"],
    .settings-card input[type="time"],
    .settings-card input[type="text"] {
        width: 100%;
        padding: 10px 12px;
        border-radius: var(--border-radius);
        border: 1px solid rgba(148, 163, 184, 0.35);
        background: rgba(255, 255, 255, 0.9);
        color: var(--gray-800);
        font-size: 0.95rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .settings-card input:focus {
        border-color: var(--blue-500);
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.18);
        outline: none;
    }

    .checkbox-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--gray-600);
        cursor: pointer;
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

// Load current datetime
async function loadDatetime() {
    try {
        const response = await fetch(API_URL + '/system/generate/datetime', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('date').value = data.data.date;
            document.getElementById('time').value = data.data.time;
            updateCurrentTime();
        }
    } catch (error) {
        console.error('Error loading datetime:', error);
    }
}

// Load NTP settings
async function loadNTPSettings() {
    try {
        const response = await fetch(API_URL + '/system/generate/ntp', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('ntpServer').value = data.data.server;
            document.getElementById('ntpEnabled').checked = data.data.enabled;
        }
    } catch (error) {
        console.error('Error loading NTP settings:', error);
    }
}

// Update current time display
function updateCurrentTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString();
    document.getElementById('currentTime').textContent = timeString;
}

// Handle datetime form submission
document.getElementById('datetimeForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const date = document.getElementById('date').value;
    const time = document.getElementById('time').value;
    
    try {
        const response = await fetch(API_URL + '/system/generate/datetime', {
            method: 'PUT',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ date, time })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Date & time updated successfully!', 'success');
        } else {
            showAlert(data.error || 'Failed to update date & time', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('An error occurred: ' + error.message, 'error');
    }
});

// Handle NTP form submission
document.getElementById('ntpForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const server = document.getElementById('ntpServer').value;
    const enabled = document.getElementById('ntpEnabled').checked;
    
    try {
        const response = await fetch(API_URL + '/system/generate/ntp', {
            method: 'PUT',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ server, enabled })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('NTP settings updated successfully!', 'success');
        } else {
            showAlert(data.error || 'Failed to update NTP settings', 'error');
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

// Update current time every second
setInterval(updateCurrentTime, 1000);

// Load initial data
loadDatetime();
loadNTPSettings();
</script>

<?php include dirname(__DIR__, 2) . '/components/footer.php'; ?>
