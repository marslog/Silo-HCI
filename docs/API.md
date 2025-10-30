# Silo HCI API Documentation

## Overview

Silo HCI provides a RESTful API for managing Proxmox VE infrastructure. The API is built with Flask and provides endpoints for nodes, VMs, containers, storage, and more.

**Base URL**: `http://your-server/api/v1`

**Authentication**: Currently uses Proxmox authentication

## API Endpoints

### Health Check

#### GET /health
Check if API is running

**Response:**
```json
{
  "status": "healthy",
  "service": "silo-hci-api"
}
```

---

## Nodes

### GET /nodes
List all cluster nodes

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "node": "pve1",
      "status": "online",
      "cpu": 0.15,
      "maxcpu": 8,
      "mem": 8589934592,
      "maxmem": 16777216000,
      "uptime": 864000
    }
  ],
  "cached": false
}
```

### GET /nodes/{node}
Get specific node information

**Parameters:**
- `node` (path) - Node name

**Response:**
```json
{
  "success": true,
  "data": {
    "node": "pve1",
    "status": "online",
    "cpu": 0.15,
    "memory": {...},
    "swap": {...}
  }
}
```

### GET /nodes/{node}/status
Get node status and resources

### GET /nodes/{node}/vms
Get all VMs on node

### GET /nodes/{node}/containers
Get all containers on node

### GET /nodes/{node}/storage
Get storage list for node

### GET /nodes/{node}/network
Get network interfaces

### GET /nodes/{node}/tasks
Get running tasks

---

## Virtual Machines (QEMU)

### GET /nodes/{node}/qemu
List all VMs on node

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "vmid": 100,
      "name": "web-server",
      "status": "running",
      "cpu": 0.05,
      "mem": 2147483648,
      "maxmem": 4294967296
    }
  ]
}
```

### GET /nodes/{node}/qemu/{vmid}
Get VM configuration and status

**Parameters:**
- `node` (path) - Node name
- `vmid` (path) - VM ID

**Response:**
```json
{
  "success": true,
  "data": {
    "config": {
      "vmid": 100,
      "name": "web-server",
      "cores": 2,
      "memory": 4096,
      "sockets": 1
    },
    "status": {
      "status": "running",
      "cpu": 0.05,
      "mem": 2147483648,
      "uptime": 86400
    }
  }
}
```

### POST /nodes/{node}/qemu
Create new VM

**Request Body:**
```json
{
  "vmid": 100,
  "name": "new-vm",
  "memory": 4096,
  "cores": 2,
  "sockets": 1,
  "scsi0": "local:32,format=qcow2",
  "net0": "virtio,bridge=vmbr0"
}
```

### PUT /nodes/{node}/qemu/{vmid}
Update VM configuration

### DELETE /nodes/{node}/qemu/{vmid}
Delete VM

### POST /nodes/{node}/qemu/{vmid}/status/{action}
Perform VM action

**Actions:**
- `start` - Start VM
- `stop` - Stop VM immediately
- `shutdown` - Graceful shutdown
- `reboot` - Reboot VM
- `suspend` - Suspend VM
- `resume` - Resume suspended VM
- `reset` - Reset VM

**Example:**
```bash
curl -X POST http://localhost:5000/api/v1/nodes/pve1/qemu/100/status/start
```

**Response:**
```json
{
  "success": true,
  "data": "UPID:pve1:00001234:...",
  "action": "start"
}
```

### POST /nodes/{node}/qemu/{vmid}/clone
Clone VM

**Request Body:**
```json
{
  "newid": 101,
  "name": "cloned-vm",
  "full": 1
}
```

### GET /nodes/{node}/qemu/{vmid}/snapshot
List VM snapshots

### POST /nodes/{node}/qemu/{vmid}/snapshot
Create snapshot

**Request Body:**
```json
{
  "snapname": "backup-2025-01-01",
  "description": "Daily backup"
}
```

### DELETE /nodes/{node}/qemu/{vmid}/snapshot/{snapname}
Delete snapshot

---

## Containers (LXC)

### GET /nodes/{node}/lxc
List all containers

### GET /nodes/{node}/lxc/{vmid}
Get container configuration

### POST /nodes/{node}/lxc
Create new container

**Request Body:**
```json
{
  "vmid": 200,
  "hostname": "web-container",
  "ostemplate": "local:vztmpl/ubuntu-22.04-standard_22.04-1_amd64.tar.zst",
  "memory": 2048,
  "swap": 512,
  "rootfs": "local:8",
  "net0": "name=eth0,bridge=vmbr0,ip=dhcp"
}
```

### DELETE /nodes/{node}/lxc/{vmid}
Delete container

### POST /nodes/{node}/lxc/{vmid}/status/{action}
Container action (start, stop, shutdown, reboot)

---

## Storage

### GET /storage
List all storage in cluster

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "storage": "local",
      "type": "dir",
      "content": "vztmpl,iso,images",
      "shared": 0,
      "active": 1
    }
  ]
}
```

### GET /nodes/{node}/storage/{storage}/content
Get storage content

---

## Network

### GET /nodes/{node}/network
Get network configuration

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "iface": "vmbr0",
      "type": "bridge",
      "active": 1,
      "address": "192.168.1.10",
      "netmask": "255.255.255.0"
    }
  ]
}
```

---

## Backup

### GET /cluster/backup
List backup jobs

### POST /cluster/backup
Create backup job

**Request Body:**
```json
{
  "vmid": "100,101,102",
  "storage": "backup-storage",
  "mode": "snapshot",
  "dow": "mon,wed,fri",
  "starttime": "02:00"
}
```

---

## Cluster

### GET /cluster/status
Get cluster status

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "type": "cluster",
      "name": "proxmox-cluster",
      "nodes": 3,
      "quorate": 1
    }
  ]
}
```

### GET /cluster/resources
Get cluster resources

---

## Monitoring

### GET /monitoring/summary
Get cluster summary for dashboard

**Response:**
```json
{
  "success": true,
  "data": {
    "nodes": {
      "total": 3,
      "online": 3
    },
    "cpu": {
      "total": 24,
      "used": 3.5,
      "percentage": 14.58
    },
    "memory": {
      "total": 51539607552,
      "used": 20615843020,
      "percentage": 40.0
    },
    "vms": {
      "total": 15,
      "running": 12,
      "stopped": 3
    }
  }
}
```

---

## Error Responses

All endpoints return errors in the following format:

```json
{
  "success": false,
  "error": "Error message here"
}
```

**HTTP Status Codes:**
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `500` - Internal Server Error

---

## Caching

The API implements caching for frequently accessed data:

- Cache TTL: 60 seconds (configurable)
- Cached endpoints return `"cached": true` in response
- Cache is automatically invalidated on write operations

---

## Rate Limiting

Currently no rate limiting is implemented. Consider adding rate limiting for production use.

---

## Examples

### Python

```python
import requests

BASE_URL = "http://localhost:5000/api/v1"

# List nodes
response = requests.get(f"{BASE_URL}/nodes")
nodes = response.json()['data']

# Start VM
response = requests.post(
    f"{BASE_URL}/nodes/pve1/qemu/100/status/start"
)
print(response.json())
```

### JavaScript

```javascript
const BASE_URL = 'http://localhost:5000/api/v1';

// List VMs
fetch(`${BASE_URL}/nodes/pve1/qemu`)
  .then(res => res.json())
  .then(data => console.log(data));

// Create VM
fetch(`${BASE_URL}/nodes/pve1/qemu`, {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    vmid: 100,
    name: 'new-vm',
    memory: 4096,
    cores: 2
  })
});
```

### cURL

```bash
# List nodes
curl http://localhost:5000/api/v1/nodes

# Start VM
curl -X POST http://localhost:5000/api/v1/nodes/pve1/qemu/100/status/start

# Create container
curl -X POST http://localhost:5000/api/v1/nodes/pve1/lxc \
  -H "Content-Type: application/json" \
  -d '{
    "vmid": 200,
    "hostname": "test-ct",
    "ostemplate": "local:vztmpl/ubuntu-22.04-standard.tar.zst",
    "memory": 2048
  }'
```
