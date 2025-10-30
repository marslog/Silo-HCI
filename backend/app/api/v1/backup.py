"""
Backup API
"""
from flask import Blueprint, jsonify, request
from app.services.proxmox_service import proxmox_service
import logging

logger = logging.getLogger(__name__)
bp = Blueprint('backup', __name__)

@bp.route('/cluster/backup', methods=['GET'])
def list_backups():
    """List backup jobs"""
    try:
        proxmox = proxmox_service.get_proxmox()
        backups = proxmox.cluster.backup.get()
        
        return jsonify({'success': True, 'data': backups})
    
    except Exception as e:
        logger.error(f"Error listing backups: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/cluster/backup', methods=['POST'])
def create_backup():
    """Create backup job"""
    try:
        data = request.json
        proxmox = proxmox_service.get_proxmox()
        result = proxmox.cluster.backup.post(**data)
        
        return jsonify({'success': True, 'data': result}), 201
    
    except Exception as e:
        logger.error(f"Error creating backup: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500
