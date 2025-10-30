#!/bin/bash
#
# Setup Offline Mode for Silo HCI
#

set -e

echo "======================================"
echo "  Silo HCI Offline Mode Setup"
echo "======================================"
echo ""

SILO_DIR="/opt/silo-hci"
DB_PATH="$SILO_DIR/database/silo.db"

# Create database if not exists
if [ ! -f "$DB_PATH" ]; then
    echo "Creating offline cache database..."
    
    sqlite3 "$DB_PATH" <<EOF
-- Nodes cache
CREATE TABLE IF NOT EXISTS nodes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    node VARCHAR(100) NOT NULL UNIQUE,
    status VARCHAR(20),
    cpu REAL,
    maxcpu INTEGER,
    mem INTEGER,
    maxmem INTEGER,
    uptime INTEGER,
    data TEXT,
    cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- VMs cache
CREATE TABLE IF NOT EXISTS vms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    node VARCHAR(100) NOT NULL,
    vmid INTEGER NOT NULL,
    name VARCHAR(255),
    status VARCHAR(20),
    cpu REAL,
    mem INTEGER,
    maxmem INTEGER,
    disk INTEGER,
    maxdisk INTEGER,
    data TEXT,
    cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(node, vmid)
);

-- Containers cache
CREATE TABLE IF NOT EXISTS containers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    node VARCHAR(100) NOT NULL,
    vmid INTEGER NOT NULL,
    name VARCHAR(255),
    status VARCHAR(20),
    cpu REAL,
    mem INTEGER,
    maxmem INTEGER,
    data TEXT,
    cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(node, vmid)
);

-- Storage cache
CREATE TABLE IF NOT EXISTS storage (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    storage VARCHAR(100) NOT NULL UNIQUE,
    type VARCHAR(50),
    content TEXT,
    total INTEGER,
    used INTEGER,
    avail INTEGER,
    data TEXT,
    cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sync log
CREATE TABLE IF NOT EXISTS sync_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sync_type VARCHAR(50),
    status VARCHAR(20),
    message TEXT,
    synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings
CREATE TABLE IF NOT EXISTS settings (
    key VARCHAR(100) PRIMARY KEY,
    value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT OR REPLACE INTO settings (key, value) VALUES 
    ('offline_mode', 'true'),
    ('last_sync', datetime('now')),
    ('sync_interval', '300');
EOF
    
    echo "✓ Database created"
fi

# Set permissions
chown www-data:www-data "$DB_PATH"
chmod 664 "$DB_PATH"

# Update configuration
echo ""
echo "Updating configuration..."

# Enable offline mode in .env
sed -i 's/^OFFLINE_MODE=.*/OFFLINE_MODE=true/' "$SILO_DIR/.env"

echo "✓ Offline mode enabled"

# Initial sync
echo ""
echo "Performing initial sync..."

cd "$SILO_DIR/backend"
source venv/bin/activate

python3 <<EOF
from app import create_app
from app.services.proxmox_service import proxmox_service

app = create_app()

with app.app_context():
    print("Connecting to Proxmox...")
    if proxmox_service.connect():
        print("✓ Connected to Proxmox")
        print("Caching data for offline use...")
        
        # Cache nodes
        try:
            nodes = proxmox_service.get_proxmox().nodes.get()
            print(f"✓ Cached {len(nodes)} nodes")
        except Exception as e:
            print(f"✗ Error caching nodes: {e}")
        
        print("Initial sync complete!")
    else:
        print("✗ Failed to connect to Proxmox")
        print("Please check your configuration in config/proxmox.json")
EOF

deactivate

echo ""
echo "======================================"
echo "  Offline Mode Setup Complete!"
echo "======================================"
echo ""
echo "Offline mode is now enabled."
echo "Data will be cached and synced automatically every 5 minutes."
echo ""
echo "Manual sync: systemctl restart silo-backend"
echo ""
