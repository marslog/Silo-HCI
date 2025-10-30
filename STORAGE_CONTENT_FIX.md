# Storage Content Viewing Fix - October 30, 2025

## Problem Description
ในส่วน storage ที่ทำการ mount เข้ามาจาก Proxmox นั้นไม่สามารถ browse เพื่อดูรายละเอียด ISO หรือ create directory ได้

**Issues:**
- Storage mounted from Proxmox (NFS, CIFS, etc.) showed "Empty Storage - No content found"
- File browser was not accessible or functional for viewing files
- Cannot create directories in mounted storage
- Cannot view ISO files in mounted storage

## Root Cause
1. **Backend API Issue**: The `/nodes/<node>/storage/<storage>/content` endpoint only called Proxmox's content API, which doesn't work for filesystem-based mounted storage
2. **Fallback Missing**: No fallback to filesystem browsing for mounted storage types (dir, nfs, cifs, glusterfs, zfs)
3. **Frontend Display**: Limited file type detection and poor empty storage messaging

## Solution Implemented

### Backend Changes (storage.py)

#### 1. Enhanced `storage_content()` Endpoint
**File**: `/opt/silo-hci/backend/app/api/v1/storage.py` (Lines 216-257)

**Changes**:
- Now detects storage type from Proxmox storage info
- For filesystem-based storage (dir, nfs, cifs, glusterfs, zfs), automatically browses the filesystem
- Falls back to filesystem browse if Proxmox API fails
- Returns both Proxmox-managed content and filesystem browsable content

**Logic Flow**:
```
1. Get storage info (type, path)
2. If filesystem-based storage type → browse filesystem directly
3. Else → try Proxmox content API
4. If Proxmox fails and storage has path → fallback to filesystem browse
```

#### 2. New `browse_storage_path()` Helper Function
**File**: `/opt/silo-hci/backend/app/api/v1/storage.py` (Lines 260-325)

**Features**:
- Lists all files and folders in storage path
- Detects file types from extensions:
  - `.iso` → ISO Image
  - `.qcow2`, `.raw`, `.vmdk`, `.vdi` → VM Disk
  - `.tar.gz`, `.tgz` → Container Template
  - `.tar`, `.tar.zst` → Backup
- Calculates file sizes
- Returns structured data with proper metadata
- Includes error handling for permission issues

**Response Format**:
```json
{
  "success": true,
  "data": [
    {
      "name": "ubuntu-20.04.iso",
      "path": "/mnt/storage/iso/ubuntu-20.04.iso",
      "volid": "storage:iso/ubuntu-20.04.iso",
      "is_dir": false,
      "type": "file",
      "content": "iso",
      "size": 3228663808,
      "storage_id": "storage",
      "storage_type": "dir"
    },
    {
      "name": "backups",
      "path": "/mnt/storage/backups",
      "volid": "storage:directory/backups",
      "is_dir": true,
      "type": "directory",
      "content": "directory",
      "size": 0,
      "storage_id": "storage",
      "storage_type": "dir"
    }
  ]
}
```

### Frontend Changes (storage.php)

#### 1. Enhanced `viewStorageContent()` Function
**File**: `/opt/silo-hci/frontend/public/pages/storage.php` (Lines 4612-4767)

**Improvements**:
- Better empty storage message with helpful suggestions
- Shows storage info and item count
- Displays folders and files with appropriate icons
- Automatic size formatting (KB, MB, GB)
- Color-coded file type badges
- Improved table styling with hover effects
- Responsive design

**File Type Display**:
- 📁 **Folder** (Orange) - Directory
- 💿 **ISO Image** (Purple) - ISO files
- 💾 **VM Disk** (Pink) - Virtual disk images
- 📦 **Container** (Cyan) - Container templates
- 💾 **Backup** (Green) - Backup files
- 📄 **File** (Gray) - Other files

#### 2. New `getColorRGB()` Helper Function
**File**: `/opt/silo-hci/frontend/public/pages/storage.php` (Lines 4771-4777)

**Purpose**: Converts hex color codes to RGB for better styling control

## Testing Instructions

### Step 1: Verify Backend Changes
Test the API endpoint directly:

```bash
# SSH into the backend container or server
curl -X GET \
  -H "Authorization: Bearer YOUR_TOKEN" \
  https://192.168.0.200:8889/api/v1/nodes/silo1/storage/disk1/content
```

**Expected Response** (for filesystem-based storage):
```json
{
  "success": true,
  "data": [
    {
      "name": "ubuntu-20.04.iso",
      "path": "/mnt/disk1/ubuntu-20.04.iso",
      "type": "file",
      "content": "iso",
      "size": 3228663808,
      ...
    }
  ]
}
```

### Step 2: Test Frontend Display
1. Go to **Storage** page: `https://192.168.0.200:8889/storage`
2. Click on a storage that's mounted from Proxmox (e.g., "disk1")
3. Should now see:
   - ✅ Directory contents instead of "Empty Storage"
   - ✅ File icons based on type
   - ✅ Proper file sizes
   - ✅ Color-coded type badges

### Step 3: Test File Browser
1. In Storage page, click **"Add Storage"** → **"Browse Folder"**
2. Should see the file browser modal
3. Can navigate between directories using breadcrumb navigation
4. Can click on folders to navigate into them
5. Can create new directories using the input field
6. Can delete empty directories using the delete button

### Step 4: Test File Operations
1. **Create Directory**:
   - Type folder name in the "New folder name" input
   - Click "Create Folder"
   - Should see the new folder in the list

2. **Delete Directory**:
   - Click the delete button next to an empty folder
   - Confirm the deletion
   - Folder should disappear from the list

3. **Navigate**:
   - Click on a folder name to enter it
   - Click breadcrumb path to go back
   - Use "Refresh" button to reload current directory

## Supported Storage Types

| Storage Type | Source | Display Method | File Browsing |
|---|---|---|---|
| **dir** | Local filesystem | Filesystem browse | ✅ Yes |
| **nfs** | NFS mount | Filesystem browse | ✅ Yes |
| **cifs** | SMB/CIFS mount | Filesystem browse | ✅ Yes |
| **glusterfs** | GlusterFS mount | Filesystem browse | ✅ Yes |
| **zfs** | ZFS pool | Filesystem browse | ✅ Yes |
| **lvm** | LVM logical volume | Proxmox API | ❌ No* |
| **lvmthin** | LVM thin pool | Proxmox API | ❌ No* |
| **rbd** | Ceph RBD | Proxmox API | ❌ No* |
| **cephfs** | Ceph filesystem | Proxmox API | ❌ No* |
| **iscsi** | iSCSI target | Proxmox API | ❌ No* |

*Storage types without filesystem paths are block-level and cannot be browsed as directories

## File Type Detection

The system automatically detects file types based on extensions:

| Extension(s) | Type | Icon | Color |
|---|---|---|---|
| `.iso` | ISO Image | 💿 | Purple (#8b5cf6) |
| `.qcow2, .raw, .vmdk, .vdi` | VM Disk | 💾 | Pink (#ec4899) |
| `.tar.gz, .tgz` | Container Template | 📦 | Cyan (#06b6d4) |
| `.tar, .tar.zst` | Backup | 💾 | Green (#10b981) |
| `(directory)` | Folder | 📁 | Orange (#f59e0b) |
| Other | File | 📄 | Gray (#6b7280) |

## Key Features

### ✅ For Mounted Storage (NFS, CIFS, etc.)
- View all files and directories
- See ISO images with proper metadata
- View file sizes
- Navigate through folders
- Create new directories
- Delete empty directories
- Proper permission handling

### ✅ For Proxmox-Managed Storage
- View ISOs, VM disks, templates, backups
- Works with existing Proxmox content API
- Full Proxmox integration

### ✅ Error Handling
- Permission denied messages
- Non-existent directory handling
- Graceful fallback mechanisms
- Informative error messages

## API Endpoints Modified

### GET `/nodes/<node>/storage/<storage>/content`
**Enhancement**: Now works for both Proxmox-managed and filesystem-based storage

**Parameters**:
- `node`: Node name
- `storage`: Storage ID

**Returns**:
- `success`: boolean
- `data`: array of items with metadata
- `error`: string (if failed)

## Frontend Functions Modified

### `viewStorageContent(storageId, node)`
Enhanced to display filesystem-based storage content properly

### New: `getColorRGB(hexColor)`
Helper to convert hex colors to RGB format for inline styles

## Browser File Operations

The file browser now supports:
- ✅ **Browse** - Navigate directory structure
- ✅ **View** - See file names, types, sizes
- ✅ **Create** - Create new folders
- ✅ **Delete** - Delete empty folders
- ✅ **Refresh** - Reload directory contents
- ✅ **Breadcrumb Navigation** - Quick path navigation

## Performance Considerations

1. **Directory Listing**: O(n) where n = number of entries
   - Limited by filesystem I/O
   - Hidden files (starting with `.`) are skipped
   - Sorted alphabetically for consistency

2. **Caching**: Not implemented (always fresh)
   - Consider adding if directories are large
   - Could use browser cache or backend caching

3. **Large Directories**: 
   - No pagination currently implemented
   - May be slow with thousands of files
   - Consider adding pagination if needed

## Security Considerations

1. **Path Validation**:
   - File browser restricted to `/mnt` directory
   - Directory traversal (`..`) blocked
   - Must be within storage path

2. **Protected Paths**:
   - `/mnt/sdb` - Cannot delete
   - `/mnt/storage` - Cannot delete
   - `/mnt/storage1` - Cannot delete

3. **Permission Handling**:
   - Permission denied errors return 403
   - Hidden files skipped automatically
   - User receives descriptive error messages

## Troubleshooting

### Issue: Still showing "No content found"
1. Verify storage is mounted: `df -h | grep /mnt`
2. Check storage type in Proxmox
3. Check filesystem permissions
4. View backend logs: `docker logs silo-backend`

### Issue: File browser not opening
1. Hard refresh browser: `Ctrl+Shift+R`
2. Check browser console for errors: `F12 → Console`
3. Verify API_URL is correct
4. Check network requests in Network tab

### Issue: Cannot create directories
1. Verify write permissions on storage path
2. Check folder name validation (alphanumeric, `-`, `_` only)
3. View browser console for error messages
4. Check backend logs for permission issues

### Issue: File sizes not showing
- Some systems may not support proper size calculation
- Try refreshing the page
- Check filesystem permissions

## Future Enhancements

1. **Pagination**: Add pagination for large directories
2. **Search**: Add file search functionality
3. **Upload**: Direct file upload to storage
4. **Delete Files**: Delete individual files (not just directories)
5. **Caching**: Implement caching for better performance
6. **Sorting**: Add column sorting by name, size, date
7. **Multi-select**: Select multiple files/directories
8. **Batch Operations**: Delete/move multiple items

## Files Modified

- `/opt/silo-hci/backend/app/api/v1/storage.py` - Backend API enhancements
- `/opt/silo-hci/frontend/public/pages/storage.php` - Frontend display improvements

## Deployment

### Docker Deployment
```bash
cd /opt/silo-hci
# Rebuild backend with new code
docker compose down
docker compose up -d

# Verify changes
docker logs silo-backend | tail -20
```

### Manual Deployment
```bash
# Backend changes are in Python, just restart Flask
sudo systemctl restart silo-backend

# Frontend changes are PHP, just refresh browser
# Hard refresh: Ctrl+Shift+R
```

## Testing Checklist

- [ ] Can see storage content instead of "Empty Storage"
- [ ] File icons display correctly
- [ ] File sizes show in proper format
- [ ] Type badges are color-coded
- [ ] File browser modal opens
- [ ] Can navigate folders
- [ ] Can create new directory
- [ ] Can delete empty directory
- [ ] Breadcrumb navigation works
- [ ] Refresh button reloads directory
- [ ] Permissions errors handled gracefully
- [ ] Non-existent directories show error

## Support

For issues or questions:
1. Check browser console: `F12 → Console`
2. Check backend logs: `docker logs silo-backend`
3. Review the debugging tips in this document
4. Check `DEBUG_FILE_BROWSER.md` for modal-specific issues
