"""
LXC Containers API
"""
from flask import Blueprint, jsonify, request
from app.services.proxmox_service import proxmox_service
import logging

logger = logging.getLogger(__name__)
bp = Blueprint('lxc', __name__)

@bp.route('/nodes/<node>/lxc', methods=['GET'])
def list_containers(node):
    """List all containers on node"""
    try:
        proxmox = proxmox_service.get_proxmox()
        containers = proxmox.nodes(node).lxc.get()
        
        return jsonify({'success': True, 'data': containers})
    
    except Exception as e:
        logger.error(f"Error listing containers: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/lxc/<int:vmid>', methods=['GET'])
def get_container(node, vmid):
    """Get container configuration"""
    try:
        proxmox = proxmox_service.get_proxmox()
        config = proxmox.nodes(node).lxc(vmid).config.get()
        status = proxmox.nodes(node).lxc(vmid).status.current.get()
        
        return jsonify({
            'success': True,
            'data': {
                'config': config,
                'status': status
            }
        })
    
    except Exception as e:
        logger.error(f"Error getting container: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/lxc', methods=['POST'])
def create_container(node):
    """Create new container"""
    try:
        data = request.json
        proxmox = proxmox_service.get_proxmox()
        
        result = proxmox.nodes(node).lxc.create(**data)
        proxmox_service.cache_clear(f'nodes:{node}:containers')
        
        return jsonify({'success': True, 'data': result}), 201
    
    except Exception as e:
        logger.error(f"Error creating container: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/lxc/<int:vmid>', methods=['DELETE'])
def delete_container(node, vmid):
    """Delete container"""
    try:
        proxmox = proxmox_service.get_proxmox()
        result = proxmox.nodes(node).lxc(vmid).delete()
        proxmox_service.cache_clear(f'nodes:{node}:containers')
        
        return jsonify({'success': True, 'data': result})
    
    except Exception as e:
        logger.error(f"Error deleting container: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/lxc/<int:vmid>/status/<action>', methods=['POST'])
def container_action(node, vmid, action):
    """Container actions: start, stop, shutdown, reboot"""
    try:
        proxmox = proxmox_service.get_proxmox()
        
        if action == 'start':
            result = proxmox.nodes(node).lxc(vmid).status.start.post()
        elif action == 'stop':
            result = proxmox.nodes(node).lxc(vmid).status.stop.post()
        elif action == 'shutdown':
            result = proxmox.nodes(node).lxc(vmid).status.shutdown.post()
        elif action == 'reboot':
            result = proxmox.nodes(node).lxc(vmid).status.reboot.post()
        else:
            return jsonify({'success': False, 'error': 'Invalid action'}), 400
        
        return jsonify({'success': True, 'data': result, 'action': action})
    
    except Exception as e:
        logger.error(f"Error performing container action: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500
