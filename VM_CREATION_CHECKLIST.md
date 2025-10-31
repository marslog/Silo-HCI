# Complete VM Creation Checklist

## Frontend Form Fields (vms.php)

### Basic Settings
- [x] VM Name (text input)
- [x] Node (dropdown - auto select first node)
- [x] Description (textarea)

### Hardware Configuration
- [x] vCPU / Cores (number input)
- [x] Memory (GB input - converts to MB)
- [x] Disk Size (GB input, default 50)
- [x] Disk Format (hidden, auto set based on storage type)

### Storage
- [x] Storage (dropdown - filtered to show only storages with 'images' content)
- [x] Format auto-updates based on storage type:
  - lvmthin → raw
  - dir → qcow2
  - others → raw

### Network
- [x] Network Model (dropdown - VirtIO only)
- [x] Bridge (hidden - defaults to vmbr0)
- [x] VLAN ID (optional)
- [x] Add extra networks button

### OS & Media
- [x] OS Type (dropdown - l26, linux24, windows xp-2022, solaris, other)
- [x] ISO Image (dropdown - loads from /api/v1/storage/iso-images)
- [x] ISO auto-loads when Create VM tab opened

### Advanced
- [x] Autostart (checkbox)
- [x] KVM Enabled (checkbox)
- [x] HugePages (checkbox)
- [x] Add extra disks button

## Backend Endpoints

### Storage & ISO
- [x] GET /api/v1/storage - List all storages
- [x] GET /api/v1/storage/info - Get storage details with free space
- [x] GET /api/v1/storage/iso-images - List ISO files
  - Returns: volid, name, path, storage_name, size, created

### VM Management
- [x] GET /api/v1/vms - List all VMs across all nodes
- [x] GET /api/v1/nodes/{node}/qemu - List VMs on specific node
- [x] POST /api/v1/nodes/{node}/qemu - **CREATE NEW VM**
  - Parameters: vmid, name, memory, cores, scsi0, net0, ostype, ide2 (ISO), description, autostart, kvm, hugePages
  - Response: UPID (Proxmox task ID)

### VM Operations
- [x] POST /api/v1/nodes/{node}/qemu/{vmid}/status/start - Start VM
- [x] POST /api/v1/nodes/{node}/qemu/{vmid}/status/stop - Stop VM
- [x] DELETE /api/v1/nodes/{node}/qemu/{vmid} - Delete VM
- [x] GET /api/v1/nodes/{node}/qemu/{vmid}/console - Get console access

### Nodes
- [x] GET /api/v1/nodes - List all nodes

## VM Creation Flow

1. **Frontend** - User fills form
2. **Validation** - Check required fields (name, node, storage)
3. **Memory Conversion** - GB → MB (multiply by 1024)
4. **Storage Selection** - Choose from filtered storages
5. **Disk Format** - Auto-set based on storage type
6. **ISO Selection** (optional) - Load from ISO dropdown
7. **Submit** - POST to `/api/v1/nodes/{node}/qemu`
8. **Wait** - 2 second delay for Proxmox indexing
9. **Refresh** - Reload VM list
10. **Display** - Show new VM in list with Running/Stopped status

## VM Parameters Sent to Backend

```json
{
  "vmid": 100,
  "name": "vm-name",
  "memory": 2048,
  "cores": 2,
  "sockets": 1,
  "cpu": "host",
  "scsi0": "local-lvm:30,format=raw",
  "net0": "virtio,bridge=vmbr0",
  "ostype": "l26",
  "ide2": "local:iso/ubuntu.iso,media=cdrom",
  "description": "Test VM",
  "autostart": 1,
  "agent": 1,
  "kvm": 1,
  "hugepages": 1
}
```

## Storage Types & Support

| Storage | Type | Content | Use Case | Disk Format |
|---------|------|---------|----------|-------------|
| local | dir | iso,backup,vztmpl | ISO/Templates | - |
| disk1 | dir | images,iso,vztmpl | ⚠️ Not for VMs | - |
| local-lvm | lvmthin | rootdir,images | **VM Creation** ✓ | raw |

## Console/Monitor Features

- [x] Monitor icon in VM list (desktop icon)
- [x] Click to open Proxmox console
- [x] Console proxy endpoint for authentication
- [x] Opens in new window with noVNC viewer

## Testing Checklist

- [ ] Test VM creation without ISO
- [ ] Test VM creation with ISO
- [ ] Test VM start/stop
- [ ] Test VM delete
- [ ] Test console access
- [ ] Test with different storage types
- [ ] Test form validation
- [ ] Test error handling
