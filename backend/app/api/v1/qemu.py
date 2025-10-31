"""
QEMU/KVM Virtual Machine API
"""
from flask import Blueprint, jsonify, request
from app.services.proxmox_service import proxmox_service
from app.utils.proxmox_api import ProxmoxAPIClient
from app.config import Config
import logging
import urllib.parse
import re

logger = logging.getLogger(__name__)
bp = Blueprint('qemu', __name__)

@bp.route('/vms', methods=['GET'])
def list_all_vms():
    """List all VMs across all nodes"""
    try:
        proxmox = proxmox_service.get_proxmox()
        all_vms = []
        
        # Get all nodes
        try:
            nodes = proxmox.nodes.get()
        except Exception as e:
            logger.error(f"Error getting nodes: {e}")
            return jsonify({'success': False, 'error': str(e)}), 500
        
        # Get VMs from each node
        for node in nodes:
            node_name = node.get('node', '')
            try:
                vms = proxmox.nodes(node_name).qemu.get()
                if vms:
                    # Add node name to each VM
                    for vm in vms:
                        vm['node'] = node_name
                    all_vms.extend(vms)
            except Exception as e:
                logger.debug(f"Error getting VMs from node {node_name}: {e}")
                continue
        
        return jsonify({'success': True, 'data': all_vms})
    
    except Exception as e:
        logger.error(f"Error listing all VMs: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

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
        
        # Log the incoming data for debugging
        logger.info(f"Creating VM with data: {data}")
        
        # Create VM with provided configuration
        try:
            result = proxmox.nodes(node).qemu.post(**data)
            logger.info(f"VM creation result: {result}")
            return jsonify({'success': True, 'data': result})
        except Exception as api_error:
            logger.error(f"Proxmox API error: {api_error}")
            # Try to extract error details
            error_msg = str(api_error)
            return jsonify({'success': False, 'error': error_msg, 'detail': str(api_error)}), 400
    
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

@bp.route('/nodes/<node>/qemu/<int:vmid>/console', methods=['GET'])
def get_console(node, vmid):
    """Get VNC console access URL for VM"""
    try:
        # Get console info from Proxmox and create a fresh vncproxy ticket
        cfg = Config.get_proxmox_config()
        pve = cfg.get('proxmox', {})
        host = pve.get('host', 'localhost')
        port = pve.get('port', 8006)
        verify_ssl = pve.get('verify_ssl', False)

        client = ProxmoxAPIClient(host=host, port=port, verify_ssl=verify_ssl)
        if pve.get('token_name') and pve.get('token_value'):
            if pve.get('password'):
                client.login(username=pve.get('user'), password=pve.get('password'))
            else:
                client.login(username=pve.get('user'), token_name=pve.get('token_name'), token_value=pve.get('token_value'))
        else:
            client.login(username=pve.get('user'), password=pve.get('password'))

        # Explicitly request a WebSocket-compatible VNC proxy ticket
        console_data = client.post(
            f"/nodes/{node}/qemu/{vmid}/vncproxy",
            data={
                'websocket': 1
            }
        ) or {}
        logger.info(f"Console access for VM {vmid} on node {node}: {console_data}")

        ticket = console_data.get('ticket')
        vnc_port = console_data.get('port')
        vnc_cert = console_data.get('cert')  # SSL certificate for VNC server validation

        # Build WebSocket path with ticket in URL query
        ws_path = f"/api2/json/nodes/{node}/qemu/{vmid}/vncwebsocket?port={vnc_port}&vncticket={urllib.parse.quote(ticket or '')}"
        
        # Return session auth cookie and CSRF token (Proxmoxia code shows both are required)
        pve_auth_cookie = client.ticket
        csrf_token = client.csrf_token
        
        # Also provide direct Proxmox WebSocket URL (bypassing Nginx proxy) as fallback
        direct_ws_url = f"wss://{host}:{port}{ws_path}"

        return jsonify({
            'success': True,
            'data': {
                'ticket': ticket,
                'port': vnc_port,
                'upid': console_data.get('upid'),
                'ws_path': ws_path,
                'pve_auth_cookie': pve_auth_cookie,
                'csrf_token': csrf_token,
                'vnc_cert': vnc_cert,  # Include VNC server certificate for browser validation
                'direct_ws_url': direct_ws_url,
                'host': host,
                'host_port': port
            }
        })
    
    except Exception as e:
        logger.error(f"Error getting console for VM {vmid}: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500


@bp.route('/nodes/<node>/tasks/<upid>/status', methods=['GET'])
def get_task_status(node, upid):
    """Get Proxmox task status for a given UPID"""
    try:
        proxmox = proxmox_service.get_proxmox()
        status = proxmox.nodes(node).tasks(upid).status.get()
        return jsonify({'success': True, 'data': status})
    except Exception as e:
        logger.error(f"Error getting task status {upid} on node {node}: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500


@bp.route('/nodes/<node>/tasks/<upid>/log', methods=['GET'])
def get_task_log(node, upid):
    """Get Proxmox task log output for a given UPID"""
    try:
        proxmox = proxmox_service.get_proxmox()
        log = proxmox.nodes(node).tasks(upid).log.get()
        return jsonify({'success': True, 'data': log})
    except Exception as e:
        logger.error(f"Error getting task log {upid} on node {node}: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

