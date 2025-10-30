# Silo HCI Offline Mode Guide

## Overview

Silo HCI features a 100% offline mode that allows you to manage your Proxmox infrastructure even when disconnected from the network or when the Proxmox servers are temporarily unreachable.

## Features

- ✅ **Full Offline Functionality** - Complete access to cached data
- ✅ **Automatic Synchronization** - Syncs when connection is restored
- ✅ **Smart Caching** - Intelligent data caching strategy
- ✅ **Conflict Resolution** - Handles offline changes gracefully
- ✅ **No Internet Required** - Works completely offline

## How It Works

1. **Initial Sync**: When online, Silo HCI caches all cluster data to local SQLite database
2. **Offline Operation**: When offline, all reads come from cache
3. **Write Queue**: Write operations are queued for later execution
4. **Auto-Sync**: When connection restored, changes are automatically synced
5. **Conflict Handling**: Smart merge of offline and online changes

## Setup

### Enable Offline Mode

Run the setup script:

```bash
sudo /opt/silo-hci/scripts/setup_offline.sh
```

This will:
- Create SQLite cache database
- Enable offline mode in configuration
- Perform initial data sync
- Set up automatic sync

### Manual Configuration

Edit `/opt/silo-hci/.env`:

```bash
OFFLINE_MODE=true
SYNC_INTERVAL=300
CACHE_TTL=60
```

Edit `/opt/silo-hci/config/proxmox.json`:

```json
{
  "offline_mode": {
    "enabled": true,
    "sync_interval": 300,
    "auto_sync": true,
    "queue_writes": true
  }
}
```

## Database Schema

Offline mode uses SQLite with the following tables:

### nodes
Caches cluster nodes
```sql
CREATE TABLE nodes (
    id INTEGER PRIMARY KEY,
    node VARCHAR(100) UNIQUE,
    status VARCHAR(20),
    cpu REAL,
    maxcpu INTEGER,
    mem INTEGER,
    maxmem INTEGER,
    uptime INTEGER,
    data TEXT,
    cached_at TIMESTAMP
);
```

### vms
Caches virtual machines
```sql
CREATE TABLE vms (
    id INTEGER PRIMARY KEY,
    node VARCHAR(100),
    vmid INTEGER,
    name VARCHAR(255),
    status VARCHAR(20),
    data TEXT,
    cached_at TIMESTAMP,
    UNIQUE(node, vmid)
);
```

### containers
Caches LXC containers

### storage
Caches storage information

### sync_log
Tracks synchronization events

### write_queue
Queues write operations for later execution

## Usage

### Check Offline Status

```bash
# Check if in offline mode
curl http://localhost:5000/api/v1/status

# View cache status
sqlite3 /opt/silo-hci/database/silo.db "SELECT * FROM sync_log ORDER BY synced_at DESC LIMIT 10;"
```

### Manual Sync

```bash
# Force synchronization
curl -X POST http://localhost:5000/api/v1/sync

# Or restart backend
sudo systemctl restart silo-backend
```

### View Queued Operations

```bash
sqlite3 /opt/silo-hci/database/silo.db "SELECT * FROM write_queue WHERE status='pending';"
```

## Cache Management

### Cache Duration

Default cache duration is 60 seconds for online mode and unlimited for offline mode.

### Clear Cache

```bash
# Clear all cache
sqlite3 /opt/silo-hci/database/silo.db "DELETE FROM nodes; DELETE FROM vms; DELETE FROM containers; DELETE FROM storage;"

# Force full resync
curl -X POST http://localhost:5000/api/v1/sync?full=true
```

### Cache Size

```bash
# Check database size
du -h /opt/silo-hci/database/silo.db

# View cache statistics
sqlite3 /opt/silo-hci/database/silo.db "
SELECT 
    'Nodes' as type, COUNT(*) as count FROM nodes
UNION ALL SELECT 'VMs', COUNT(*) FROM vms
UNION ALL SELECT 'Containers', COUNT(*) FROM containers
UNION ALL SELECT 'Storage', COUNT(*) FROM storage;
"
```

## Synchronization

### Automatic Sync

Sync happens automatically:
- Every 5 minutes (configurable)
- When connection is restored
- After backend restart
- On manual trigger

### Sync Process

1. Connect to Proxmox
2. Fetch latest data
3. Compare with cache
4. Update changed items
5. Execute queued writes
6. Handle conflicts
7. Update sync log

### Conflict Resolution

When conflicts occur:
- **Read conflicts**: Latest data from Proxmox wins
- **Write conflicts**: Queued operation is attempted, errors logged
- **Delete conflicts**: User is notified to resolve manually

## Monitoring

### View Sync History

```bash
sqlite3 /opt/silo-hci/database/silo.db "
SELECT 
    sync_type,
    status,
    message,
    synced_at
FROM sync_log
ORDER BY synced_at DESC
LIMIT 20;
"
```

### Check Last Sync

```bash
sqlite3 /opt/silo-hci/database/silo.db "
SELECT value FROM settings WHERE key='last_sync';
"
```

### Monitor Sync Logs

```bash
# Watch backend logs
sudo journalctl -u silo-backend -f | grep -i sync
```

## Limitations

### Read-Only in Offline Mode

When offline, the following operations are queued:
- VM start/stop/restart
- Container management
- VM/Container creation
- Configuration changes

These will execute automatically when connection is restored.

### Data Freshness

Cached data may be stale. The UI indicates:
- ⚠️ Yellow: Data is cached (online but from cache)
- 🔴 Red: Offline mode (no connection)
- ✅ Green: Live data (just fetched)

### Not Cached

The following are not cached (require online):
- Console access (VNC/SPICE)
- ISO uploads
- Template downloads
- Backup restore

## Troubleshooting

### Cache not updating

```bash
# Check backend logs
sudo journalctl -u silo-backend -n 100

# Verify Proxmox connection
curl http://localhost:5000/api/v1/health

# Force sync
sudo systemctl restart silo-backend
```

### Database locked

```bash
# Check processes using database
lsof /opt/silo-hci/database/silo.db

# If needed, stop backend
sudo systemctl stop silo-backend
```

### Sync errors

```bash
# View error log
sqlite3 /opt/silo-hci/database/silo.db "
SELECT * FROM sync_log WHERE status='error' ORDER BY synced_at DESC LIMIT 10;
"

# Check Proxmox credentials
cat /opt/silo-hci/config/proxmox.json
```

### Corrupted database

```bash
# Backup current database
cp /opt/silo-hci/database/silo.db /tmp/silo.db.backup

# Recreate database
rm /opt/silo-hci/database/silo.db
sudo /opt/silo-hci/scripts/setup_offline.sh
```

## Best Practices

1. **Regular Syncs**: Keep sync interval reasonable (5-10 minutes)
2. **Monitor Size**: Keep database size under control
3. **Backup Database**: Include database in backups
4. **Test Offline**: Regularly test offline functionality
5. **Quick Reconnect**: Minimize offline time for critical operations

## API Endpoints

### Check Offline Status
```bash
GET /api/v1/offline/status
```

### Force Sync
```bash
POST /api/v1/offline/sync
```

### View Cache Stats
```bash
GET /api/v1/offline/cache/stats
```

### Clear Cache
```bash
DELETE /api/v1/offline/cache
```

## Performance

- **Read Performance**: ~0.5ms (from SQLite)
- **Sync Time**: ~2-5 seconds (depending on cluster size)
- **Database Size**: ~10-50MB (typical cluster)
- **Sync Overhead**: Minimal (~1% CPU during sync)

## Security

- Database is stored locally with restricted permissions
- No sensitive data in cache (passwords excluded)
- Cache encrypted at rest (if filesystem encrypted)
- Queued operations validated before execution

---

For more information, see:
- [Installation Guide](INSTALLATION.md)
- [API Documentation](API.md)
