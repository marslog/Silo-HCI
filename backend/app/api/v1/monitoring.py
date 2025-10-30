"""
Monitoring API
"""
from flask import Blueprint, jsonify
from app.services.proxmox_service import proxmox_service
import logging

logger = logging.getLogger(__name__)
bp = Blueprint('monitoring', __name__)

@bp.route('/monitoring/summary', methods=['GET'])
def get_summary():
    """Get cluster summary for dashboard"""
    try:
        proxmox = proxmox_service.get_proxmox()
        
        # Get nodes
        nodes = proxmox.nodes.get()
        
        # Calculate totals
        total_cpu = 0
        used_cpu = 0
        total_mem = 0
        used_mem = 0
        total_vms = 0
        running_vms = 0
        
        for node in nodes:
            if node['status'] == 'online':
                total_cpu += node.get('maxcpu', 0)
                used_cpu += node.get('cpu', 0) * node.get('maxcpu', 0)
                total_mem += node.get('maxmem', 0)
                used_mem += node.get('mem', 0)
        
        # Get VMs/Containers
        resources = proxmox.cluster.resources.get(type='vm')
        total_vms = len(resources)
        running_vms = len([r for r in resources if r.get('status') == 'running'])
        
        summary = {
            'nodes': {
                'total': len(nodes),
                'online': len([n for n in nodes if n['status'] == 'online'])
            },
            'cpu': {
                'total': total_cpu,
                'used': used_cpu,
                'percentage': round((used_cpu / total_cpu * 100) if total_cpu > 0 else 0, 2)
            },
            'memory': {
                'total': total_mem,
                'used': used_mem,
                'percentage': round((used_mem / total_mem * 100) if total_mem > 0 else 0, 2)
            },
            'vms': {
                'total': total_vms,
                'running': running_vms,
                'stopped': total_vms - running_vms
            }
        }
        
        return jsonify({'success': True, 'data': summary})
    
    except Exception as e:
        logger.error(f"Error getting monitoring summary: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500
