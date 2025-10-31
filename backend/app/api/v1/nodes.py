"""
Nodes API endpoints
Manage Proxmox cluster nodes
"""
from flask import Blueprint, jsonify, request
from app.services.proxmox_service import proxmox_service
import logging

logger = logging.getLogger(__name__)
bp = Blueprint('nodes', __name__)

@bp.route('/nodes', methods=['GET'])
def list_nodes():
    """Get list of all nodes in cluster"""
    try:
        # Check cache first
        cache_key = 'nodes:list'
        cached = proxmox_service.cache_get(cache_key)
        if cached:
            return jsonify({'success': True, 'data': cached, 'cached': True})
        
        # Get from Proxmox
        proxmox = proxmox_service.get_proxmox()
        nodes = proxmox.nodes.get()
        
        # Cache the result
        proxmox_service.cache_set(cache_key, nodes)
        
        return jsonify({'success': True, 'data': nodes, 'cached': False})
    
    except Exception as e:
        logger.error(f"Error listing nodes: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>', methods=['GET'])
def get_node(node):
    """Get specific node information"""
    try:
        cache_key = f'nodes:{node}'
        cached = proxmox_service.cache_get(cache_key)
        if cached:
            return jsonify({'success': True, 'data': cached, 'cached': True})
        
        proxmox = proxmox_service.get_proxmox()
        node_status = proxmox.nodes(node).status.get()
        
        proxmox_service.cache_set(cache_key, node_status)
        
        return jsonify({'success': True, 'data': node_status, 'cached': False})
    
    except Exception as e:
        logger.error(f"Error getting node {node}: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/status', methods=['GET'])
def node_status(node):
    """Get node status and resources"""
    try:
        proxmox = proxmox_service.get_proxmox()
        status = proxmox.nodes(node).status.get()
        
        return jsonify({'success': True, 'data': status})
    
    except Exception as e:
        logger.error(f"Error getting node status: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/kvm', methods=['GET'])
def node_kvm(node):
    """Best-effort KVM availability probe for node.
    Returns { available: true|false, reason?: str }
    """
    try:
        proxmox = proxmox_service.get_proxmox()
        # Try capabilities endpoint if available
        available = None
        reason = None
        try:
            caps = proxmox.nodes(node).capabilities.qemu.get()
            # Some PVE versions include 'version'/'kvm' info; this is heuristic
            kvm_flags = str(caps)
            if 'kvm' in kvm_flags.lower():
                # Heuristic: if string suggests disabled
                if 'disabled' in kvm_flags.lower() or 'not available' in kvm_flags.lower():
                    available = False
                    reason = 'Capabilities report KVM disabled'
                else:
                    available = True
        except Exception:
            pass

        if available is None:
            # Fallback: inspect node status cpu flags for 'hypervisor' which often implies no KVM on bare metal
            status = proxmox.nodes(node).status.get()
            flags = status.get('cpuinfo', {}).get('flags', '') or ''
            # If running under a hypervisor and no nested KVM, assume unavailable
            if 'hypervisor' in flags:
                available = False
                reason = 'Host is a VM (hypervisor flag), KVM/Nested may be unavailable'
            else:
                available = True

        return jsonify({'success': True, 'data': {'available': bool(available), 'reason': reason}})
    except Exception as e:
        logger.error(f"Error probing KVM on node {node}: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/vms', methods=['GET'])
def node_vms(node):
    """Get all VMs on specific node"""
    try:
        cache_key = f'nodes:{node}:vms'
        cached = proxmox_service.cache_get(cache_key)
        if cached:
            return jsonify({'success': True, 'data': cached, 'cached': True})
        
        proxmox = proxmox_service.get_proxmox()
        vms = proxmox.nodes(node).qemu.get()
        
        proxmox_service.cache_set(cache_key, vms)
        
        return jsonify({'success': True, 'data': vms, 'cached': False})
    
    except Exception as e:
        logger.error(f"Error getting VMs: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/containers', methods=['GET'])
def node_containers(node):
    """Get all containers on specific node"""
    try:
        cache_key = f'nodes:{node}:containers'
        cached = proxmox_service.cache_get(cache_key)
        if cached:
            return jsonify({'success': True, 'data': cached, 'cached': True})
        
        proxmox = proxmox_service.get_proxmox()
        containers = proxmox.nodes(node).lxc.get()
        
        proxmox_service.cache_set(cache_key, containers)
        
        return jsonify({'success': True, 'data': containers, 'cached': False})
    
    except Exception as e:
        logger.error(f"Error getting containers: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/storage', methods=['GET'])
def node_storage(node):
    """Get storage list for node"""
    try:
        proxmox = proxmox_service.get_proxmox()
        storage = proxmox.nodes(node).storage.get()
        
        return jsonify({'success': True, 'data': storage})
    
    except Exception as e:
        logger.error(f"Error getting storage: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/network', methods=['GET'])
def node_network(node):
    """Get network interfaces for node"""
    try:
        proxmox = proxmox_service.get_proxmox()
        network = proxmox.nodes(node).network.get()
        
        return jsonify({'success': True, 'data': network})
    
    except Exception as e:
        logger.error(f"Error getting network: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/tasks', methods=['GET'])
def node_tasks(node):
    """Get running tasks for node"""
    try:
        proxmox = proxmox_service.get_proxmox()
        tasks = proxmox.nodes(node).tasks.get()
        
        return jsonify({'success': True, 'data': tasks})
    
    except Exception as e:
        logger.error(f"Error getting tasks: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500
