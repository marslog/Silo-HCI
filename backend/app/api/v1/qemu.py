"""
QEMU/KVM Virtual Machine API
"""
from flask import Blueprint, jsonify, request
from app.services.proxmox_service import proxmox_service
import logging

logger = logging.getLogger(__name__)
bp = Blueprint('qemu', __name__)

@bp.route('/nodes/<node>/qemu', methods=['GET'])
def list_vms(node):
    """List all VMs on a node"""
    try:
        proxmox = proxmox_service.get_proxmox()
        vms = proxmox.nodes(node).qemu.get()
        
        return jsonify({'success': True, 'data': vms})
    
    except Exception as e:
        logger.error(f"Error listing VMs: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/qemu', methods=['POST'])
def create_vm(node):
    """Create a new VM"""
    try:
        data = request.get_json()
        vmid = data.get('vmid')
        
        if not vmid:
            return jsonify({'success': False, 'error': 'VM ID is required'}), 400
        
        proxmox = proxmox_service.get_proxmox()
        
        # Create VM with provided configuration
        result = proxmox.nodes(node).qemu.post(**data)
        
        return jsonify({'success': True, 'data': result})
    
    except Exception as e:
        logger.error(f"Error creating VM: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/qemu/<int:vmid>', methods=['GET'])
def get_vm_config(node, vmid):
    """Get VM configuration"""
    try:
        proxmox = proxmox_service.get_proxmox()
        config = proxmox.nodes(node).qemu(vmid).config.get()
        
        return jsonify({'success': True, 'data': config})
    
    except Exception as e:
        logger.error(f"Error getting VM config: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/qemu/<int:vmid>', methods=['PUT'])
def update_vm_config(node, vmid):
    """Update VM configuration"""
    try:
        data = request.get_json()
        proxmox = proxmox_service.get_proxmox()
        
        result = proxmox.nodes(node).qemu(vmid).config.put(**data)
        
        return jsonify({'success': True, 'data': result})
    
    except Exception as e:
        logger.error(f"Error updating VM: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/qemu/<int:vmid>', methods=['DELETE'])
def delete_vm(node, vmid):
    """Delete a VM"""
    try:
        proxmox = proxmox_service.get_proxmox()
        result = proxmox.nodes(node).qemu(vmid).delete()
        
        return jsonify({'success': True, 'data': result})
    
    except Exception as e:
        logger.error(f"Error deleting VM: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/qemu/<int:vmid>/status/start', methods=['POST'])
def start_vm(node, vmid):
    """Start a VM"""
    try:
        proxmox = proxmox_service.get_proxmox()
        result = proxmox.nodes(node).qemu(vmid).status.start.post()
        
        return jsonify({'success': True, 'data': result})
    
    except Exception as e:
        logger.error(f"Error starting VM: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/qemu/<int:vmid>/status/stop', methods=['POST'])
def stop_vm(node, vmid):
    """Stop a VM"""
    try:
        proxmox = proxmox_service.get_proxmox()
        result = proxmox.nodes(node).qemu(vmid).status.stop.post()
        
        return jsonify({'success': True, 'data': result})
    
    except Exception as e:
        logger.error(f"Error stopping VM: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/qemu/<int:vmid>/status/shutdown', methods=['POST'])
def shutdown_vm(node, vmid):
    """Shutdown a VM gracefully"""
    try:
        proxmox = proxmox_service.get_proxmox()
        result = proxmox.nodes(node).qemu(vmid).status.shutdown.post()
        
        return jsonify({'success': True, 'data': result})
    
    except Exception as e:
        logger.error(f"Error shutting down VM: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/qemu/<int:vmid>/status/reboot', methods=['POST'])
def reboot_vm(node, vmid):
    """Reboot a VM"""
    try:
        proxmox = proxmox_service.get_proxmox()
        result = proxmox.nodes(node).qemu(vmid).status.reboot.post()
        
        return jsonify({'success': True, 'data': result})
    
    except Exception as e:
        logger.error(f"Error rebooting VM: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/qemu/<int:vmid>/status/current', methods=['GET'])
def get_vm_status(node, vmid):
    """Get current VM status"""
    try:
        proxmox = proxmox_service.get_proxmox()
        status = proxmox.nodes(node).qemu(vmid).status.current.get()
        
        return jsonify({'success': True, 'data': status})
    
    except Exception as e:
        logger.error(f"Error getting VM status: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/qemu/<int:vmid>/clone', methods=['POST'])
def clone_vm(node, vmid):
    """Clone a VM"""
    try:
        data = request.get_json()
        newid = data.get('newid')
        
        if not newid:
            return jsonify({'success': False, 'error': 'New VM ID is required'}), 400
        
        proxmox = proxmox_service.get_proxmox()
        result = proxmox.nodes(node).qemu(vmid).clone.post(**data)
        
        return jsonify({'success': True, 'data': result})
    
    except Exception as e:
        logger.error(f"Error cloning VM: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500
