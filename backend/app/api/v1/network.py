"""
Network API
"""
from flask import Blueprint, jsonify, request
from app.services.proxmox_service import proxmox_service
import logging

logger = logging.getLogger(__name__)
bp = Blueprint('network', __name__)

@bp.route('/nodes/<node>/network', methods=['GET'])
def get_network(node):
    """Get network configuration"""
    try:
        proxmox = proxmox_service.get_proxmox()
        network = proxmox.nodes(node).network.get()
        
        return jsonify({'success': True, 'data': network})
    
    except Exception as e:
        logger.error(f"Error getting network: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500
