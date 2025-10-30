"""
Cluster management API with network scanning and node discovery
"""
from flask import Blueprint, jsonify, request
from app.services.proxmox_service import proxmox_service
import logging
import socket
import concurrent.futures
from ipaddress import IPv4Network

logger = logging.getLogger(__name__)
bp = Blueprint('cluster', __name__)

@bp.route('/cluster/status', methods=['GET'])
def cluster_status():
    """Get cluster status"""
    try:
        proxmox = proxmox_service.get_proxmox()
        status = proxmox.cluster.status.get()
        
        return jsonify({'success': True, 'data': status})
    
    except Exception as e:
        logger.error(f"Error getting cluster status: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/cluster/resources', methods=['GET'])
def cluster_resources():
    """Get all cluster resources"""
    try:
        proxmox = proxmox_service.get_proxmox()
        resources = proxmox.cluster.resources.get()
        
        return jsonify({'success': True, 'data': resources})
    
    except Exception as e:
        logger.error(f"Error getting cluster resources: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/cluster/scan', methods=['POST'])
def scan_network():
    """Scan network for Proxmox nodes"""
    try:
        data = request.get_json()
        network_range = data.get('network', '192.168.0.0/24')
        
        logger.info(f"Scanning network: {network_range}")
        
        # Parse network range
        try:
            network = IPv4Network(network_range, strict=False)
        except ValueError as e:
            return jsonify({'success': False, 'error': f'Invalid network range: {str(e)}'}), 400
        
        discovered_nodes = []
        
        # Scan network for Proxmox nodes (port 8006)
        def scan_host(ip):
            try:
                sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
                sock.settimeout(0.5)
                result = sock.connect_ex((str(ip), 8006))
                sock.close()
                
                if result == 0:
                    # Try to get hostname
                    try:
                        hostname = socket.gethostbyaddr(str(ip))[0]
                    except:
                        hostname = str(ip)
                    
                    # Try to detect if it's a Proxmox node
                    # In real implementation, you would try to connect to API
                    return {
                        'ip': str(ip),
                        'hostname': hostname,
                        'node': hostname.split('.')[0],
                        'online': True,
                        'version': 'Proxmox VE (Detected)',
                        'clustered': False,
                        'port': 8006
                    }
            except:
                pass
            return None
        
        # Scan hosts in parallel
        with concurrent.futures.ThreadPoolExecutor(max_workers=50) as executor:
            # Limit scan to first 254 hosts
            hosts_to_scan = list(network.hosts())[:254]
            futures = [executor.submit(scan_host, ip) for ip in hosts_to_scan]
            
            for future in concurrent.futures.as_completed(futures):
                result = future.result()
                if result:
                    discovered_nodes.append(result)
        
        logger.info(f"Found {len(discovered_nodes)} potential Proxmox nodes")
        
        return jsonify({
            'success': True,
            'data': discovered_nodes,
            'message': f'Found {len(discovered_nodes)} nodes'
        })
    
    except Exception as e:
        logger.error(f"Error scanning network: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/cluster/nodes', methods=['GET'])
def list_cluster_nodes():
    """List all nodes in cluster"""
    try:
        proxmox = proxmox_service.get_proxmox()
        nodes = proxmox.cluster.status.get()
        
        # Filter only nodes
        cluster_nodes = [n for n in nodes if n.get('type') == 'node']
        
        return jsonify({'success': True, 'data': cluster_nodes})
    
    except Exception as e:
        logger.error(f"Error listing cluster nodes: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/cluster/nodes', methods=['POST'])
def add_node_to_cluster():
    """Add node to cluster"""
    try:
        data = request.get_json()
        
        hostname = data.get('hostname')
        ip = data.get('ip')
        password = data.get('password')
        
        if not hostname or not ip or not password:
            return jsonify({
                'success': False,
                'error': 'Hostname, IP, and password are required'
            }), 400
        
        # In Proxmox, you typically add nodes via command line on the node itself
        # pvecm add <master-ip> --force
        # This API would need to execute remote commands or provide join information
        
        # For now, return info for manual addition
        proxmox = proxmox_service.get_proxmox()
        
        # Get cluster join information
        try:
            # Get current cluster info
            cluster_info = proxmox.cluster.status.get()
            cluster_name = None
            for item in cluster_info:
                if item.get('type') == 'cluster':
                    cluster_name = item.get('name')
                    break
            
            return jsonify({
                'success': True,
                'message': 'To add this node to the cluster, run the following command on the new node:',
                'command': f'pvecm add {ip} --force',
                'cluster_name': cluster_name,
                'instructions': [
                    f'1. SSH to the new node: ssh root@{ip}',
                    '2. Ensure Proxmox VE is installed',
                    f'3. Run: pvecm add {ip}',
                    '4. Enter root password when prompted',
                    '5. Wait for cluster synchronization'
                ]
            })
        except Exception as e:
            logger.error(f"Error getting cluster info: {e}")
            return jsonify({
                'success': False,
                'error': 'Unable to get cluster information. Make sure this node is part of a cluster.'
            }), 500
    
    except Exception as e:
        logger.error(f"Error adding node to cluster: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/cluster/config', methods=['GET'])
def get_cluster_config():
    """Get cluster configuration"""
    try:
        proxmox = proxmox_service.get_proxmox()
        config = proxmox.cluster.config.get()
        
        return jsonify({'success': True, 'data': config})
    
    except Exception as e:
        logger.error(f"Error getting cluster config: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/cluster/ha', methods=['GET'])
def get_ha_status():
    """Get HA status"""
    try:
        proxmox = proxmox_service.get_proxmox()
        ha_resources = proxmox.cluster.ha.resources.get()
        
        return jsonify({'success': True, 'data': ha_resources})
    
    except Exception as e:
        logger.error(f"Error getting HA status: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/cluster/backup', methods=['GET'])
def list_backup_jobs():
    """List backup jobs"""
    try:
        proxmox = proxmox_service.get_proxmox()
        backups = proxmox.cluster.backup.get()
        
        return jsonify({'success': True, 'data': backups})
    
    except Exception as e:
        logger.error(f"Error listing backup jobs: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/cluster/backup', methods=['POST'])
def create_backup_job():
    """Create backup job"""
    try:
        data = request.get_json()
        
        proxmox = proxmox_service.get_proxmox()
        result = proxmox.cluster.backup.post(**data)
        
        return jsonify({'success': True, 'data': result})
    
    except Exception as e:
        logger.error(f"Error creating backup job: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/cluster/tasks', methods=['GET'])
def list_cluster_tasks():
    """List cluster tasks"""
    try:
        proxmox = proxmox_service.get_proxmox()
        tasks = proxmox.cluster.tasks.get()
        
        return jsonify({'success': True, 'data': tasks})
    
    except Exception as e:
        logger.error(f"Error listing tasks: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500
