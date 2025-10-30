#!/bin/bash
#
# Backup Script for Silo HCI
#

set -e

SILO_DIR="/opt/silo-hci"
BACKUP_DIR="/var/backups/silo-hci"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="silo-backup-$DATE.tar.gz"

echo "======================================"
echo "  Silo HCI Backup"
echo "======================================"
echo ""

# Create backup directory
mkdir -p "$BACKUP_DIR"

echo "Creating backup..."

# Create backup
tar -czf "$BACKUP_DIR/$BACKUP_FILE" \
    -C /opt \
    --exclude='silo-hci/backend/venv' \
    --exclude='silo-hci/frontend/vendor' \
    --exclude='silo-hci/logs/*.log' \
    silo-hci

echo "✓ Backup created: $BACKUP_DIR/$BACKUP_FILE"

# Keep only last 7 backups
echo "Cleaning old backups..."
cd "$BACKUP_DIR"
ls -t silo-backup-*.tar.gz | tail -n +8 | xargs -r rm

echo "✓ Backup complete!"
echo ""
echo "Backup location: $BACKUP_DIR/$BACKUP_FILE"
echo "Backup size: $(du -h "$BACKUP_DIR/$BACKUP_FILE" | cut -f1)"
echo ""
