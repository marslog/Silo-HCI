# Code Changes - Storage API Scan Endpoints

## File 1: Backend API - storage.py

### NEW FUNCTION 1: scan_dir()
**Location:** `/opt/silo-hci/backend/app/api/v1/storage.py` (lines after scan_glusterfs)

```python
@bp.route('/nodes/<node>/scan/dir', methods=['GET'])
def scan_dir(node):
    """Scan for local directories and disks"""
    try:
        proxmox = proxmox_service.get_proxmox()
        
        # Get list of disks available on node
        disks = proxmox.nodes(node).scan.disks.get()
        
        # Transform disk data for display
        result = []
        if isinstance(disks, list):
            for disk in disks:
                result.append({
                    'name': disk.get('devpath', ''),
                    'path': disk.get('devpath', ''),
                    'size': disk.get('size', 0),
                    'used': disk.get('used', 0),
                    'free': disk.get('size', 0) - disk.get('used', 0),
                    'model': disk.get('model', 'Unknown'),
                    'type': 'disk'
                })
        
        return jsonify({'success': True, 'data': result})
    
    except Exception as e:
        logger.error(f"Error scanning directories: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500
```

### NEW FUNCTION 2: scan_ceph()
**Location:** `/opt/silo-hci/backend/app/api/v1/storage.py` (after scan_dir)

```python
@bp.route('/nodes/<node>/scan/ceph', methods=['GET'])
def scan_ceph(node):
    """Scan for Ceph RBD pools"""
    try:
        proxmox = proxmox_service.get_proxmox()
        
        # Get Ceph configuration and pools
        try:
            ceph_status = proxmox.nodes(node).ceph.status.get()
        except:
            ceph_status = {}
        
        # Get list of RBD pools
        try:
            pools = proxmox.nodes(node).ceph.pools.get()
        except:
            pools = []
        
        # Transform pool data for display
        result = []
        if isinstance(pools, list):
            for pool in pools:
                result.append({
                    'name': pool.get('pool_name', pool.get('name', '')),
                    'pool': pool.get('pool_name', pool.get('name', '')),
                    'size': pool.get('size', pool.get('quota', 0)),
                    'used': pool.get('used', 0),
                    'type': 'ceph_pool',
                    'status': 'active'
                })
        
        return jsonify({'success': True, 'data': result})
    
    except Exception as e:
        logger.error(f"Error scanning Ceph: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500
```

---

## File 2: Frontend JavaScript - storage.php

### UPDATED FUNCTION: scanAvailableStorage()
**Location:** `/opt/silo-hci/frontend/public/pages/storage.php` (in JavaScript section)

```javascript
async function scanAvailableStorage() {
    const node = document.getElementById('storageNode').value;
    const typeRadio = document.querySelector('input[name="type"]:checked');
    const type = typeRadio ? typeRadio.value : null;
    
    if (!node || !type) {
        alert('Please select both node and storage type first');
        return;
    }
    
    const scanBtn = document.getElementById('scanBtn');
    const originalHTML = scanBtn.innerHTML;
    scanBtn.disabled = true;
    scanBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning...';
    
    try {
        let endpoint = '';
        let params = '';
        
        // Map storage types to scan endpoints
        if (type === 'dir') {
            endpoint = `${API_URL}/nodes/${node}/scan/dir`;
        } else if (type === 'rbd') {
            endpoint = `${API_URL}/nodes/${node}/scan/ceph`;
        } else if (type === 'nfs') {
            endpoint = `${API_URL}/nodes/${node}/scan/nfs`;
            
            // NFS scan requires server parameter from user
            const server = prompt('Enter NFS server IP or hostname:\n(e.g., 192.168.1.100)');
            if (!server) {
                scanBtn.disabled = false;
                scanBtn.innerHTML = originalHTML;
                return;
            }
            params = `?server=${encodeURIComponent(server)}`;
        }
        
        if (!endpoint) {
            alert('Scanning not supported for this storage type');
            scanBtn.disabled = false;
            scanBtn.innerHTML = originalHTML;
            return;
        }
        
        console.log('Scanning storage:', endpoint + params, 'type:', type);
        
        const response = await fetch(endpoint + params, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success || result.data) {
            const data = result.data || result;
            displayDetectedStorage(data, type);
        } else {
            alert('No storage found or scan failed');
        }
    } catch (error) {
        console.error('Scan error:', error);
        alert('Error scanning storage: ' + error.message);
    } finally {
        scanBtn.disabled = false;
        scanBtn.innerHTML = originalHTML;
    }
}
```

---

## Summary of Changes

### Backend Changes:
| Change | File | Lines Added | Status |
|--------|------|-------------|--------|
| New scan_dir() function | storage.py | ~30 | ✅ Added |
| New scan_ceph() function | storage.py | ~30 | ✅ Added |

### Frontend Changes:
| Change | File | Status |
|--------|------|--------|
| Updated scanAvailableStorage() | storage.php | ✅ Updated |
| Proper endpoint mapping | storage.php | ✅ Added |
| NFS server parameter handling | storage.php | ✅ Added |

---

## Testing Checklist

After deploying, test each endpoint:

### Test 1: Local Disk Scan
```
Request: GET /nodes/silo1/scan/dir
Expected Status: 200
Expected Response: List of available disks
```

### Test 2: Ceph Scan
```
Request: GET /nodes/silo1/scan/ceph
Expected Status: 200
Expected Response: List of Ceph RBD pools
```

### Test 3: NFS Scan
```
Request: GET /nodes/silo1/scan/nfs?server=192.168.1.100
Expected Status: 200
Expected Response: List of NFS exports from that server
```

---

## Deployment Steps

### Option 1: Docker
```bash
# Rebuild backend image with new code
docker build -f docker/backend.Dockerfile -t silo-hci-backend:latest .

# Restart backend container
docker restart <backend-container-id>

# Or restart via compose
docker-compose restart backend
```

### Option 2: Direct Python
```bash
cd /opt/silo-hci/backend

# If using Flask development server
python -m flask run --host=0.0.0.0 --port=5000

# If using production WSGI (gunicorn)
gunicorn -w 4 -b 0.0.0.0:5000 wsgi:app
```

### Option 3: Systemd Service
```bash
# Restart the service
systemctl restart silo-hci-backend

# Check status
systemctl status silo-hci-backend

# View logs
journalctl -u silo-hci-backend -f
```

---

## Verification

After deployment, verify the endpoints are working:

```bash
# Test Local Disk Scan
curl -k https://192.168.0.200:8889/api/v1/nodes/silo1/scan/dir

# Test Ceph Scan
curl -k https://192.168.0.200:8889/api/v1/nodes/silo1/scan/ceph

# Test NFS Scan
curl -k "https://192.168.0.200:8889/api/v1/nodes/silo1/scan/nfs?server=192.168.1.100"
```

All should return HTTP 200 with JSON data in the format shown above.
