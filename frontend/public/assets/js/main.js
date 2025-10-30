// Silo HCI Main JavaScript

// API Base URL
const API_BASE = '/api/v1';

// Utility function for API calls
async function apiCall(endpoint, options = {}) {
    try {
        const response = await fetch(API_BASE + endpoint, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'API request failed');
        }
        
        return data;
    } catch (error) {
        console.error('API Error:', error);
        showNotification('Error: ' + error.message, 'error');
        throw error;
    }
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Format bytes to human readable
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// Format uptime
function formatUptime(seconds) {
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    
    return `${days}d ${hours}h ${minutes}m`;
}

// VM Actions
async function vmAction(node, vmid, action) {
    try {
        showNotification(`${action}ing VM ${vmid}...`, 'info');
        
        const result = await apiCall(`/nodes/${node}/qemu/${vmid}/status/${action}`, {
            method: 'POST'
        });
        
        if (result.success) {
            showNotification(`VM ${vmid} ${action} successful`, 'success');
            setTimeout(() => location.reload(), 2000);
        }
    } catch (error) {
        showNotification(`Failed to ${action} VM: ${error.message}`, 'error');
    }
}

// Container Actions
async function lxcAction(node, vmid, action) {
    try {
        showNotification(`${action}ing Container ${vmid}...`, 'info');
        
        const result = await apiCall(`/nodes/${node}/lxc/${vmid}/status/${action}`, {
            method: 'POST'
        });
        
        if (result.success) {
            showNotification(`Container ${vmid} ${action} successful`, 'success');
            setTimeout(() => location.reload(), 2000);
        }
    } catch (error) {
        showNotification(`Failed to ${action} container: ${error.message}`, 'error');
    }
}

// Auto-refresh dashboard
let refreshInterval = null;

function startAutoRefresh(intervalSeconds = 30) {
    stopAutoRefresh();
    
    refreshInterval = setInterval(() => {
        console.log('Auto-refreshing dashboard...');
        updateDashboard();
    }, intervalSeconds * 1000);
}

function stopAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
}

async function updateDashboard() {
    try {
        const summary = await apiCall('/monitoring/summary');
        
        if (summary.success) {
            // Update dashboard values
            updateDashboardValues(summary.data);
        }
    } catch (error) {
        console.error('Failed to update dashboard:', error);
    }
}

function updateDashboardValues(data) {
    // Update CPU
    const cpuPercent = data.cpu?.percentage || 0;
    document.querySelectorAll('[data-cpu-percent]').forEach(el => {
        el.textContent = cpuPercent.toFixed(1) + '%';
    });
    
    // Update Memory
    const memPercent = data.memory?.percentage || 0;
    document.querySelectorAll('[data-mem-percent]').forEach(el => {
        el.textContent = memPercent.toFixed(1) + '%';
    });
    
    // Update VMs
    if (data.vms) {
        document.querySelectorAll('[data-vms-running]').forEach(el => {
            el.textContent = data.vms.running;
        });
        document.querySelectorAll('[data-vms-total]').forEach(el => {
            el.textContent = data.vms.total;
        });
    }
}

// Mobile menu toggle
function toggleMobileMenu() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('open');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    console.log('Silo HCI initialized');
    
    // Start auto-refresh on dashboard
    if (window.location.pathname === '/dashboard' || window.location.pathname === '/') {
        startAutoRefresh(30);
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    stopAutoRefresh();
});
