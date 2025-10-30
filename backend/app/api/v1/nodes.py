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
