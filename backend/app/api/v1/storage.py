"""
Storage API with comprehensive CRUD operations for all storage types
"""
from flask import Blueprint, jsonify, request
from app.services.proxmox_service import proxmox_service
import logging
import os

logger = logging.getLogger(__name__)
bp = Blueprint('storage', __name__)

@bp.route('/storage', methods=['GET'])
def list_storage():
    """List all storage in cluster"""
    try:
        proxmox = proxmox_service.get_proxmox()
        storage = proxmox.storage.get()
        
        return jsonify({'success': True, 'data': storage})
    
    except Exception as e:
        logger.error(f"Error listing storage: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/storage', methods=['POST'])
def create_storage():
    """Create new storage - supports all types for cluster"""
    try:
        data = request.get_json()
        storage_id = data.get('storage')
        storage_type = data.get('type', 'dir')
        content = data.get('content', 'images,iso,vztmpl')
        
        if not storage_id:
            return jsonify({'success': False, 'error': 'Storage ID is required'}), 400
        
        proxmox = proxmox_service.get_proxmox()
        
        # Base configuration
        config = {
            'storage': storage_id,
            'type': storage_type,
            'content': content
        }
        
        # Add optional nodes parameter (for cluster)
        if data.get('nodes'):
            config['nodes'] = data.get('nodes')
        
        # Add shared parameter if specified
        if 'shared' in data:
            config['shared'] = int(data.get('shared', 0))
        
        # Type-specific configurations
        if storage_type == 'dir':
            # Local Directory
            if not data.get('path'):
                return jsonify({'success': False, 'error': 'Path is required for directory storage'}), 400
            config['path'] = data.get('path')
            
        elif storage_type == 'lvm':
            # LVM Volume Group
            if not data.get('vgname'):
                return jsonify({'success': False, 'error': 'Volume group name is required'}), 400
            config['vgname'] = data.get('vgname')
            
        elif storage_type == 'lvmthin':
            # LVM Thin Pool
            if not data.get('vgname') or not data.get('thinpool'):
                return jsonify({'success': False, 'error': 'Volume group and thin pool are required'}), 400
            config['vgname'] = data.get('vgname')
            config['thinpool'] = data.get('thinpool')
            
        elif storage_type == 'iscsi':
            # iSCSI Target
            if not data.get('portal') or not data.get('target'):
                return jsonify({'success': False, 'error': 'Portal and target are required for iSCSI'}), 400
            config['portal'] = data.get('portal')
            config['target'] = data.get('target')
            
        elif storage_type == 'nfs':
            # NFS Share
            if not data.get('server') or not data.get('export'):
                return jsonify({'success': False, 'error': 'Server and export path are required for NFS'}), 400
            config['server'] = data.get('server')
            config['export'] = data.get('export')
            if data.get('options'):
                config['options'] = data.get('options')
                
        elif storage_type == 'cifs':
            # CIFS/SMB Share
            if not data.get('server') or not data.get('share'):
                return jsonify({'success': False, 'error': 'Server and share are required for CIFS'}), 400
            config['server'] = data.get('server')
            config['share'] = data.get('share')
            if data.get('username'):
                config['username'] = data.get('username')
            if data.get('password'):
                config['password'] = data.get('password')
            if data.get('domain'):
                config['domain'] = data.get('domain')
                
        elif storage_type == 'zfspool':
            # ZFS Pool
            if not data.get('pool'):
                return jsonify({'success': False, 'error': 'Pool name is required for ZFS'}), 400
            config['pool'] = data.get('pool')
            if data.get('blocksize'):
                config['blocksize'] = data.get('blocksize')
                
        elif storage_type == 'rbd':
            # Ceph RBD
            if not data.get('monhost') or not data.get('pool'):
                return jsonify({'success': False, 'error': 'Monitor hosts and pool are required for Ceph RBD'}), 400
            config['monhost'] = data.get('monhost')
            config['pool'] = data.get('pool')
            if data.get('username'):
                config['username'] = data.get('username')
                
        elif storage_type == 'glusterfs':
            # GlusterFS
            if not data.get('server') or not data.get('volume'):
                return jsonify({'success': False, 'error': 'Server and volume are required for GlusterFS'}), 400
            config['server'] = data.get('server')
            config['volume'] = data.get('volume')
        
        # Create storage
        result = proxmox.storage.post(**config)
        
        logger.info(f"Created {storage_type} storage: {storage_id}")
        
        # Get storage info with actual size data
        try:
            storage_info = proxmox.storage(storage_id).get()
            logger.info(f"Storage info: {storage_info}")
            
            # For dir type, get filesystem stats using df command
            if storage_type == 'dir' and data.get('path'):
                import subprocess
                path = data.get('path')
                try:
                    # Use df to get actual filesystem size
                    result_df = subprocess.check_output(['df', path], text=True)
                    lines = result_df.strip().split('\n')
                    if len(lines) >= 2:
                        parts = lines[1].split()
                        if len(parts) >= 4:
                            total_kb = int(parts[1])
                            used_kb = int(parts[2])
                            avail_kb = int(parts[3])
                            
                            storage_info['size'] = total_kb * 1024
                            storage_info['available'] = avail_kb * 1024
                            storage_info['used'] = used_kb * 1024
                            storage_info['size_gb'] = round(total_kb / (1024 * 1024), 2)
                            storage_info['available_gb'] = round(avail_kb / (1024 * 1024), 2)
                            storage_info['used_gb'] = round(used_kb / (1024 * 1024), 2)
                            logger.info(f"Storage size: {storage_info['size_gb']} GB")
                except Exception as e:
                    logger.error(f"Error getting filesystem stats for {path}: {e}")
        except Exception as e:
            logger.error(f"Error getting storage info: {e}")
            storage_info = result
        
        return jsonify({'success': True, 'data': storage_info, 'message': f'{storage_type.upper()} storage created successfully'})
    
    except Exception as e:
        logger.error(f"Error creating storage: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/storage/<storage_id>', methods=['GET'])
def get_storage(storage_id):
    """Get storage details"""
    try:
        proxmox = proxmox_service.get_proxmox()
        storage = proxmox.storage(storage_id).get()
        
        return jsonify({'success': True, 'data': storage})
    
    except Exception as e:
        logger.error(f"Error getting storage: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/storage/<storage_id>', methods=['PUT'])
def update_storage(storage_id):
    """Update storage configuration"""
    try:
        data = request.get_json()
        proxmox = proxmox_service.get_proxmox()
        
        # Remove storage field if present (can't update ID)
        data.pop('storage', None)
        
        result = proxmox.storage(storage_id).put(**data)
        
        return jsonify({'success': True, 'data': result})
    
    except Exception as e:
        logger.error(f"Error updating storage: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/storage/<storage_id>', methods=['DELETE'])
def delete_storage(storage_id):
    """Delete storage"""
    try:
        proxmox = proxmox_service.get_proxmox()
        result = proxmox.storage(storage_id).delete()
        
        logger.info(f"Deleted storage: {storage_id}")
        return jsonify({'success': True, 'data': result, 'message': 'Storage deleted successfully'})
    
    except Exception as e:
        logger.error(f"Error deleting storage: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/storage/<storage>/content', methods=['GET'])
def storage_content(node, storage):
    """Get storage content - works for both Proxmox-managed and filesystem-based storage
    Query params:
    - path: relative path within storage (optional, for browsing subdirectories)
    """
    try:
        proxmox = proxmox_service.get_proxmox()
        
        # Get optional path parameter for recursive browsing
        rel_path = request.args.get('path', '').strip()
        
        # Try to get storage info to see if it's a mount point
        try:
            storage_info = proxmox.storage(storage).get()
            storage_type = storage_info.get('type', '')
            storage_path = storage_info.get('path', '')
            
            logger.info(f"Storage {storage}: type={storage_type}, path={storage_path}, rel_path={rel_path}")
            
            # For filesystem-based storage (dir, nfs, cifs, etc.), ALWAYS browse filesystem
            # This is more reliable than Proxmox content API which may be empty
            if storage_type in ['dir', 'nfs', 'cifs', 'glusterfs', 'zfs']:
                if storage_path:
                    # If relative path provided, append it
                    if rel_path:
                        full_path = os.path.join(storage_path, rel_path)
                        # Security: prevent path traversal
                        real_storage = os.path.realpath(storage_path)
                        real_full = os.path.realpath(full_path)
                        if not real_full.startswith(real_storage):
                            return jsonify({'success': False, 'error': 'Invalid path'}), 403
                        return browse_storage_path(full_path, storage, storage_type, rel_path)
                    else:
                        return browse_storage_path(storage_path, storage, storage_type, '')
                else:
                    logger.warning(f"Filesystem storage {storage} has no path defined")
                    return jsonify({'success': True, 'data': []})
            
            # For other storage types, try Proxmox content API
            try:
                content = proxmox.nodes(node).storage(storage).content.get()
                # Enrich content with additional metadata
                for item in content:
                    if item.get('volid'):
                        item['storage_id'] = storage
                        item['storage_type'] = 'proxmox'
                return jsonify({'success': True, 'data': content})
            except Exception as e:
                logger.warning(f"Proxmox content API failed for {storage}: {e}")
                # Fallback: if storage has a filesystem path, try browsing it
                if storage_path and storage_type in ['dir', 'nfs', 'cifs', 'glusterfs', 'zfs']:
                    return browse_storage_path(storage_path, storage, storage_type, '')
                return jsonify({'success': True, 'data': []})
        
        except Exception as e:
            logger.error(f"Error getting storage info: {e}")
            return jsonify({'success': False, 'error': str(e)}), 500
    
    except Exception as e:
        logger.error(f"Error getting storage content: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500


def browse_storage_path(storage_path, storage_id, storage_type, rel_path=''):
    """Browse filesystem storage and return file listing
    
    Args:
        storage_path: Full path to storage root
        storage_id: Storage identifier
        storage_type: Type of storage (dir, nfs, cifs, etc.)
        rel_path: Relative path within storage for breadcrumb tracking
    """
    try:
        if not os.path.isdir(storage_path):
            logger.warning(f"Storage path does not exist: {storage_path}")
            return jsonify({'success': True, 'data': []})
        
        items = []
        
        try:
            entries = os.listdir(storage_path)
            for entry in sorted(entries):
                full_path = os.path.join(storage_path, entry)
                
                # Skip hidden files
                if entry.startswith('.'):
                    continue
                
                try:
                    is_dir = os.path.isdir(full_path)
                    
                    # Determine content type
                    content_type = 'directory' if is_dir else 'file'
                    size = 0
                    
                    if not is_dir:
                        try:
                            size = os.path.getsize(full_path)
                            # Detect content type from extension
                            if entry.lower().endswith('.iso'):
                                content_type = 'iso'
                            elif entry.lower().endswith('.tar.gz') or entry.lower().endswith('.tgz'):
                                content_type = 'vztmpl'
                            elif entry.lower().endswith('.tar') or entry.lower().endswith('.tar.zst'):
                                content_type = 'backup'
                            elif entry.lower().endswith(('.qcow2', '.raw', '.vmdk', '.vdi')):
                                content_type = 'images'
                        except Exception as e:
                            logger.warning(f"Error getting size for {full_path}: {e}")
                    
                    # Build relative path for breadcrumb
                    if rel_path:
                        item_rel_path = os.path.join(rel_path, entry)
                    else:
                        item_rel_path = entry
                    
                    item = {
                        'name': entry,
                        'path': full_path,
                        'rel_path': item_rel_path,  # Add relative path for navigation
                        'volid': f"{storage_id}:{content_type}/{entry}",
                        'is_dir': is_dir,
                        'type': 'directory' if is_dir else 'file',
                        'content': content_type,
                        'size': size,
                        'storage_id': storage_id,
                        'storage_type': storage_type
                    }
                    items.append(item)
                    
                except Exception as e:
                    logger.warning(f"Error processing {full_path}: {e}")
                    continue
            
            logger.info(f"browse_storage_path: Found {len(items)} items in {storage_path}")
            return jsonify({'success': True, 'data': items})
        
        except PermissionError:
            logger.error(f"Permission denied accessing {storage_path}")
            return jsonify({'success': False, 'error': 'Permission denied'}), 403
        except OSError as e:
            logger.error(f"Error reading directory {storage_path}: {e}")
            return jsonify({'success': False, 'error': f'Error reading directory: {str(e)}'}), 500
    
    except Exception as e:
        logger.error(f"Error in browse_storage_path: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/storage/<storage>/create-directory', methods=['POST'])
def create_storage_directory(node, storage):
    """Create a new directory in storage"""
    try:
        data = request.get_json()
        dir_name = data.get('name', '').strip()
        
        if not dir_name:
            return jsonify({'success': False, 'error': 'Directory name is required'}), 400
        
        # Validate directory name - only allow safe characters
        import re
        if not re.match(r'^[a-zA-Z0-9_\-\.]+$', dir_name):
            return jsonify({'success': False, 'error': 'Only alphanumeric characters, dashes, dots, and underscores allowed'}), 400
        
        proxmox = proxmox_service.get_proxmox()
        
        # Get storage info to find the mount path
        try:
            storage_info = proxmox.storage(storage).get()
            storage_path = storage_info.get('path', '')
            storage_type = storage_info.get('type', '')
            
            logger.info(f"Creating directory in {storage}: type={storage_type}, path={storage_path}")
            
            # For filesystem-based storage, create directory locally
            if storage_type in ['dir', 'nfs', 'cifs', 'glusterfs', 'zfs'] and storage_path:
                # Create full path
                new_dir_path = os.path.join(storage_path, dir_name)
                
                # Prevent directory traversal attacks
                real_storage_path = os.path.realpath(storage_path)
                real_new_path = os.path.realpath(new_dir_path)
                
                if not real_new_path.startswith(real_storage_path):
                    return jsonify({'success': False, 'error': 'Invalid directory name - path traversal not allowed'}), 403
                
                # Check if directory already exists
                if os.path.exists(new_dir_path):
                    return jsonify({'success': False, 'error': f'Directory "{dir_name}" already exists'}), 409
                
                # Create directory
                try:
                    os.makedirs(new_dir_path, mode=0o755)
                    logger.info(f"Directory created: {new_dir_path}")
                    
                    return jsonify({
                        'success': True,
                        'message': f'Directory "{dir_name}" created successfully',
                        'path': new_dir_path
                    })
                    
                except PermissionError:
                    logger.error(f"Permission denied creating directory {new_dir_path}")
                    return jsonify({'success': False, 'error': 'Permission denied - unable to create directory'}), 403
                except OSError as e:
                    logger.error(f"Error creating directory {new_dir_path}: {e}")
                    return jsonify({'success': False, 'error': f'Failed to create directory: {str(e)}'}), 500
            else:
                logger.warning(f"Storage {storage} is not a filesystem-based storage type or has no path")
                return jsonify({'success': False, 'error': f'Storage type "{storage_type}" does not support directory creation'}), 400
                
        except Exception as e:
            logger.error(f"Error getting storage info for {storage}: {e}")
            return jsonify({'success': False, 'error': f'Unable to get storage information: {str(e)}'}), 500
    
    except Exception as e:
        logger.error(f"Error creating directory: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/storage/<storage>/delete-directory', methods=['POST'])
def delete_storage_directory(node, storage):
    """Delete a directory in storage"""
    try:
        data = request.get_json()
        dir_name = data.get('name', '').strip()
        
        if not dir_name:
            return jsonify({'success': False, 'error': 'Directory name is required'}), 400
        
        proxmox = proxmox_service.get_proxmox()
        
        # Get storage info to find the mount path
        try:
            storage_info = proxmox.storage(storage).get()
            storage_path = storage_info.get('path', '')
            storage_type = storage_info.get('type', '')
            
            logger.info(f"Deleting directory in {storage}: name={dir_name}")
            
            # For filesystem-based storage, delete directory
            if storage_type in ['dir', 'nfs', 'cifs', 'glusterfs', 'zfs'] and storage_path:
                # Create full path
                dir_path = os.path.join(storage_path, dir_name)
                
                # Prevent directory traversal attacks
                real_storage_path = os.path.realpath(storage_path)
                real_dir_path = os.path.realpath(dir_path)
                
                if not real_dir_path.startswith(real_storage_path):
                    return jsonify({'success': False, 'error': 'Invalid directory - path traversal not allowed'}), 403
                
                # Check if directory exists
                if not os.path.exists(dir_path):
                    return jsonify({'success': False, 'error': f'Directory "{dir_name}" does not exist'}), 404
                
                # Check if it's a directory
                if not os.path.isdir(dir_path):
                    return jsonify({'success': False, 'error': f'"{dir_name}" is not a directory'}), 400
                
                # Delete directory and its contents
                try:
                    import shutil
                    shutil.rmtree(dir_path)
                    logger.info(f"Directory deleted: {dir_path}")
                    
                    return jsonify({
                        'success': True,
                        'message': f'Directory "{dir_name}" and its contents have been deleted'
                    })
                    
                except PermissionError:
                    logger.error(f"Permission denied deleting directory {dir_path}")
                    return jsonify({'success': False, 'error': 'Permission denied - unable to delete directory'}), 403
                except OSError as e:
                    logger.error(f"Error deleting directory {dir_path}: {e}")
                    return jsonify({'success': False, 'error': f'Failed to delete directory: {str(e)}'}), 500
            else:
                logger.warning(f"Storage {storage} is not a filesystem-based storage type")
                return jsonify({'success': False, 'error': f'Storage type "{storage_type}" does not support directory deletion'}), 400
                
        except Exception as e:
            logger.error(f"Error getting storage info for {storage}: {e}")
            return jsonify({'success': False, 'error': f'Unable to get storage information: {str(e)}'}), 500
    
    except Exception as e:
        logger.error(f"Error deleting directory: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/storage/<storage>/delete-file', methods=['POST'])
def delete_storage_file(node, storage):
    """Delete a file from storage"""
    try:
        data = request.get_json()
        file_path = data.get('path', '').strip()
        
        if not file_path:
            return jsonify({'success': False, 'error': 'File path is required'}), 400
        
        proxmox = proxmox_service.get_proxmox()
        
        # Get storage info to find the mount path
        try:
            storage_info = proxmox.storage(storage).get()
            storage_path = storage_info.get('path', '')
            storage_type = storage_info.get('type', '')
            
            logger.info(f"Deleting file from {storage}: path={file_path}")
            
            # For filesystem-based storage, delete file
            if storage_type in ['dir', 'nfs', 'cifs', 'glusterfs', 'zfs'] and storage_path:
                # Create full path
                full_path = os.path.join(storage_path, file_path)
                
                # Prevent directory traversal attacks
                real_storage_path = os.path.realpath(storage_path)
                real_file_path = os.path.realpath(full_path)
                
                if not real_file_path.startswith(real_storage_path):
                    return jsonify({'success': False, 'error': 'Invalid file path - path traversal not allowed'}), 403
                
                # Check if file exists
                if not os.path.exists(full_path):
                    return jsonify({'success': False, 'error': f'File "{file_path}" does not exist'}), 404
                
                # Check if it's a file (not a directory)
                if not os.path.isfile(full_path):
                    return jsonify({'success': False, 'error': f'"{file_path}" is not a file'}), 400
                
                # Delete the file
                try:
                    os.remove(full_path)
                    logger.info(f"File deleted: {full_path}")
                    
                    return jsonify({
                        'success': True,
                        'message': f'File "{os.path.basename(file_path)}" deleted successfully'
                    })
                    
                except PermissionError:
                    logger.error(f"Permission denied deleting file {full_path}")
                    return jsonify({'success': False, 'error': 'Permission denied - unable to delete file'}), 403
                except OSError as e:
                    logger.error(f"Error deleting file {full_path}: {e}")
                    return jsonify({'success': False, 'error': f'Failed to delete file: {str(e)}'}), 500
            else:
                logger.warning(f"Storage {storage} is not a filesystem-based storage type or has no path")
                return jsonify({'success': False, 'error': f'Storage type "{storage_type}" does not support file deletion'}), 400
                
        except Exception as e:
            logger.error(f"Error getting storage info for {storage}: {e}")
            return jsonify({'success': False, 'error': f'Unable to get storage information: {str(e)}'}), 500
    
    except Exception as e:
        logger.error(f"Error deleting file: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/storage/<storage>/upload', methods=['POST'])
def upload_iso(node, storage):
    """Upload ISO file to storage by writing directly to storage path"""
    try:
        if 'file' not in request.files:
            return jsonify({'success': False, 'error': 'No file provided'}), 400
        
        file = request.files['file']
        content_type = request.form.get('content', 'iso')
        
        if file.filename == '':
            return jsonify({'success': False, 'error': 'No file selected'}), 400
        
        filename = file.filename
        proxmox = proxmox_service.get_proxmox()
        
        # Get storage path from Proxmox
        try:
            storage_info = proxmox.storage(storage).get()
            storage_path = storage_info.get('path')
            
            if not storage_path:
                return jsonify({'success': False, 'error': f'Storage {storage} path not found'}), 400
            
            # Check if file already exists
            existing_content = proxmox.nodes(node).storage(storage).content.get()
            for item in existing_content:
                if 'volid' in item and filename in item['volid']:
                    logger.warning(f"File {filename} already exists in {storage}")
                    return jsonify({
                        'success': False, 
                        'error': f'File "{filename}" already exists in storage. Please delete it first or rename your file.',
                        'code': 'FILE_EXISTS'
                    }), 409
        except Exception as check_error:
            logger.warning(f"Could not check storage info: {check_error}")
        
        # Determine content subdirectory based on type
        content_subdir = 'iso' if content_type == 'iso' else content_type
        target_path = os.path.join(storage_path, content_subdir, filename)
        target_dir = os.path.dirname(target_path)
        
        # Create directory if needed
        try:
            os.makedirs(target_dir, mode=0o755, exist_ok=True)
        except Exception as e:
            logger.error(f"Error creating directory {target_dir}: {e}")
            return jsonify({'success': False, 'error': f'Cannot create storage directory: {str(e)}'}), 500
        
        logger.info(f"Starting upload of {filename} to {target_path}")
        
        # Write file in chunks to avoid memory issues
        chunk_size = 8 * 1024 * 1024  # 8MB chunks
        total_bytes = 0
        
        try:
            with open(target_path, 'wb') as f:
                while True:
                    chunk = file.read(chunk_size)
                    if not chunk:
                        break
                    f.write(chunk)
                    total_bytes += len(chunk)
                    logger.info(f"Uploaded {total_bytes / (1024**2):.1f}MB of {filename}")
            
            # Verify file was written
            if os.path.exists(target_path):
                file_size = os.path.getsize(target_path)
                logger.info(f"Successfully uploaded {filename} ({file_size} bytes) to {storage}")
                
                return jsonify({
                    'success': True,
                    'data': {
                        'filename': filename,
                        'path': target_path,
                        'size': file_size,
                        'storage': storage
                    },
                    'message': f'File {filename} uploaded successfully ({file_size / (1024**3):.2f}GB)'
                })
            else:
                return jsonify({'success': False, 'error': 'File was not written to storage'}), 500
        
        except Exception as write_error:
            logger.error(f"Error writing file to storage: {write_error}", exc_info=True)
            # Try to clean up partial file
            try:
                if os.path.exists(target_path):
                    os.remove(target_path)
            except:
                pass
            return jsonify({'success': False, 'error': f'Error writing file: {str(write_error)}'}), 500
    
    except Exception as e:
        logger.error(f"Error in upload_iso: {e}", exc_info=True)
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/storage/<storage>/download', methods=['POST'])
def download_iso(node, storage):
    """Download ISO from URL to storage"""
    try:
        data = request.get_json()
        url = data.get('url')
        filename = data.get('filename')
        content_type = data.get('content', 'iso')
        
        if not url or not filename:
            return jsonify({'success': False, 'error': 'URL and filename are required'}), 400
        
        proxmox = proxmox_service.get_proxmox()
        
        # Download file using Proxmox API
        result = proxmox.nodes(node).storage(storage).download_url.post(
            content=content_type,
            filename=filename,
            url=url
        )
        
        logger.info(f"Started download of {filename} from {url} to {storage}")
        return jsonify({'success': True, 'data': result, 'task': result, 'message': 'Download started'})
    
    except Exception as e:
        logger.error(f"Error downloading file: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/storage/<storage>/content/<volume>', methods=['DELETE'])
def delete_content(node, storage, volume):
    """Delete content from storage"""
    try:
        proxmox = proxmox_service.get_proxmox()
        result = proxmox.nodes(node).storage(storage).content(volume).delete()
        
        logger.info(f"Deleted {volume} from {storage} on {node}")
        return jsonify({'success': True, 'data': result, 'message': 'Content deleted successfully'})
    
    except Exception as e:
        logger.error(f"Error deleting content: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/storage/<storage>/status', methods=['GET'])
def storage_status(node, storage):
    """Get storage status and usage"""
    try:
        proxmox = proxmox_service.get_proxmox()
        status = proxmox.nodes(node).storage(storage).status.get()
        
        return jsonify({'success': True, 'data': status})
    
    except Exception as e:
        logger.error(f"Error getting storage status: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/scan/lvm', methods=['GET'])
def scan_lvm(node):
    """Scan for LVM volume groups"""
    try:
        proxmox = proxmox_service.get_proxmox()
        vgs = proxmox.nodes(node).scan.lvm.get()
        
        return jsonify({'success': True, 'data': vgs})
    
    except Exception as e:
        logger.error(f"Error scanning LVM: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/scan/zfs', methods=['GET'])
def scan_zfs(node):
    """Scan for ZFS pools"""
    try:
        proxmox = proxmox_service.get_proxmox()
        pools = proxmox.nodes(node).scan.zfs.get()
        
        return jsonify({'success': True, 'data': pools})
    
    except Exception as e:
        logger.error(f"Error scanning ZFS: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/scan/iscsi', methods=['GET'])
def scan_iscsi(node):
    """Scan for iSCSI targets"""
    try:
        portal = request.args.get('portal')
        if not portal:
            return jsonify({'success': False, 'error': 'Portal parameter is required'}), 400
            
        proxmox = proxmox_service.get_proxmox()
        targets = proxmox.nodes(node).scan.iscsi.get(portal=portal)
        
        return jsonify({'success': True, 'data': targets})
    
    except Exception as e:
        logger.error(f"Error scanning iSCSI: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/scan/nfs', methods=['GET'])
def scan_nfs(node):
    """Scan for NFS exports"""
    try:
        server = request.args.get('server')
        if not server:
            return jsonify({'success': False, 'error': 'Server parameter is required'}), 400
            
        proxmox = proxmox_service.get_proxmox()
        exports = proxmox.nodes(node).scan.nfs.get(server=server)
        
        return jsonify({'success': True, 'data': exports})
    
    except Exception as e:
        logger.error(f"Error scanning NFS: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/scan/glusterfs', methods=['GET'])
def scan_glusterfs(node):
    """Scan for GlusterFS volumes"""
    try:
        server = request.args.get('server')
        if not server:
            return jsonify({'success': False, 'error': 'Server parameter is required'}), 400
            
            
        proxmox = proxmox_service.get_proxmox()
        volumes = proxmox.nodes(node).scan.glusterfs.get(server=server)
        
        return jsonify({'success': True, 'data': volumes})
    
    except Exception as e:
        logger.error(f"Error scanning GlusterFS: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/scan/dir', methods=['GET'])
def scan_dir(node):
    """Scan for unmounted disks with actual capacity"""
    try:
        import subprocess
        import os
        
        result = []
        
        # Get list of block devices that are not mounted
        try:
            output = subprocess.check_output(['lsblk', '-nlo', 'NAME,SIZE,TYPE'], 
                                            text=True, stderr=subprocess.DEVNULL)
            lines = output.strip().split('\n')
            
            for line in lines:
                parts = line.split()
                if len(parts) >= 2:
                    device = parts[0]
                    size = parts[1]
                    dev_type = parts[2] if len(parts) > 2 else 'unknown'
                    
                    # Only show disk type (not partitions) and exclude system disks
                    if dev_type == 'disk' and device not in ['sda', 'sr0']:
                        try:
                            # Check if device is already mounted
                            mount_check = subprocess.check_output(['grep', f'/dev/{device}', '/etc/mtab'],
                                                                 stderr=subprocess.DEVNULL, text=True)
                            is_mounted = True
                        except:
                            is_mounted = False
                        
                        # Only show disks that are not mounted
                        if not is_mounted:
                            result.append({
                                'name': f'/dev/{device}',
                                'path': f'/dev/{device}',
                                'size': size,
                                'device': device,
                                'type': dev_type,
                                'mounted': is_mounted,
                                'suggested_mount': f'/mnt/{device}'
                            })
        except Exception as e:
            logger.error(f"Error scanning block devices: {e}")
        
        return jsonify({'success': True, 'data': result})
    
    except Exception as e:
        logger.error(f"Error scanning directories: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/mount-disk', methods=['POST'])
def mount_disk(node):
    """Format and mount a disk to a specified directory"""
    try:
        import subprocess
        import os
        import shutil
        
        data = request.get_json()
        device = data.get('device')  # e.g., '/dev/sdb'
        mount_path = data.get('mount_path')  # e.g., '/mnt/storage'
        filesystem = data.get('filesystem', 'ext4')  # ext4 or xfs
        
        if not device or not mount_path:
            return jsonify({'success': False, 'error': 'device and mount_path are required'}), 400
        
        # Validate inputs - prevent directory traversal
        if '..' in mount_path or not mount_path.startswith('/mnt/'):
            return jsonify({'success': False, 'error': 'Invalid mount path'}), 400
        
        if not device.startswith('/dev/'):
            return jsonify({'success': False, 'error': 'Invalid device path'}), 400
        
        # Prevent mounting system disks
        if device in ['/dev/sda', '/dev/sda1', '/dev/sda2', '/dev/sda3']:
            return jsonify({'success': False, 'error': 'Cannot mount system disk'}), 403
        
        try:
            # Step 1: Create mount point if it doesn't exist
            if not os.path.exists(mount_path):
                os.makedirs(mount_path, mode=0o755)
            
            # Step 2: Format the device - find mkfs binary location
            mkfs_cmd = shutil.which(f'mkfs.{filesystem}')
            if not mkfs_cmd:
                # Try alternative path
                mkfs_cmd = f'/sbin/mkfs.{filesystem}'
                if not os.path.exists(mkfs_cmd):
                    mkfs_cmd = f'/usr/sbin/mkfs.{filesystem}'
            
            if not os.path.exists(mkfs_cmd):
                return jsonify({'success': False, 'error': f'mkfs.{filesystem} not found on system'}), 500
            
            logger.info(f"Formatting {device} as {filesystem} using {mkfs_cmd}...")
            result = subprocess.run([mkfs_cmd, '-F', device], 
                         capture_output=True, text=True, timeout=120)
            
            if result.returncode != 0:
                logger.error(f"mkfs failed: {result.stderr}")
                return jsonify({'success': False, 'error': f'Format failed: {result.stderr}'}), 500
            
            # Step 3: Mount the device
            logger.info(f"Mounting {device} to {mount_path}...")
            result = subprocess.run(['mount', device, mount_path], 
                         capture_output=True, text=True, timeout=10)
            
            if result.returncode != 0:
                logger.error(f"mount failed: {result.stderr}")
                return jsonify({'success': False, 'error': f'Mount failed: {result.stderr}'}), 500
            
            # Step 4: Set permissions
            os.chmod(mount_path, 0o755)
            
            # Step 5: Add to fstab for permanent mounting
            logger.info(f"Adding {device} to /etc/fstab...")
            fstab_entry = f"{device}  {mount_path}  {filesystem}  defaults  0  2\n"
            with open('/etc/fstab', 'a') as f:
                f.write(fstab_entry)
            
            return jsonify({
                'success': True, 
                'message': f'Successfully formatted and mounted {device} to {mount_path}',
                'device': device,
                'mount_path': mount_path
            })
        
        except Exception as e:
            logger.error(f"Error mounting disk: {e}")
            return jsonify({'success': False, 'error': str(e)}), 500
    
    except Exception as e:
        logger.error(f"Error in mount_disk: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/create-directory', methods=['POST'])
def create_directory(node):
    """Create a new directory for storage"""
    try:
        data = request.get_json()
        path = data.get('path', '').strip()
        
        if not path:
            return jsonify({'success': False, 'error': 'Path is required'}), 400
        
        # Validate path - must start with /mnt/
        if not path.startswith('/mnt/'):
            return jsonify({'success': False, 'error': 'Path must start with /mnt/'}), 400
        
        # Check for path traversal attempts
        if '..' in path or path.count('/') < 2:
            return jsonify({'success': False, 'error': 'Invalid path'}), 400
        
        try:
            # Create directory with parents if needed
            os.makedirs(path, mode=0o755, exist_ok=True)
            
            # Verify creation
            if os.path.isdir(path):
                logger.info(f"Created storage directory: {path}")
                return jsonify({
                    'success': True,
                    'message': f'Directory created successfully at {path}',
                    'path': path
                })
            else:
                return jsonify({'success': False, 'error': 'Failed to create directory'}), 500
                
        except PermissionError:
            return jsonify({'success': False, 'error': 'Permission denied - unable to create directory'}), 403
        except OSError as e:
            logger.error(f"Error creating directory {path}: {e}")
            return jsonify({'success': False, 'error': f'Failed to create directory: {str(e)}'}), 500
    
    except Exception as e:
        logger.error(f"Error in create_directory: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/browse-directory', methods=['POST'])
def browse_directory(node):
    """List contents of a directory"""
    try:
        data = request.get_json()
        path = data.get('path', '/mnt').strip()
        
        # Validate path
        if not path.startswith('/mnt'):
            return jsonify({'success': False, 'error': 'Can only browse /mnt directory'}), 400
        
        if '..' in path:
            return jsonify({'success': False, 'error': 'Invalid path'}), 400
        
        # Check if path exists
        if not os.path.isdir(path):
            return jsonify({'success': False, 'error': 'Directory does not exist'}), 404
        
        try:
            items = []
            
            # List directory contents
            entries = os.listdir(path)
            for entry in sorted(entries):
                full_path = os.path.join(path, entry)
                
                # Skip hidden files
                if entry.startswith('.'):
                    continue
                
                try:
                    is_dir = os.path.isdir(full_path)
                    items.append({
                        'name': entry,
                        'path': full_path,
                        'is_dir': is_dir,
                        'type': 'directory' if is_dir else 'file'
                    })
                except Exception as e:
                    logger.warning(f"Error reading {full_path}: {e}")
                    continue
            
            return jsonify({'success': True, 'data': items})
        
        except PermissionError:
            return jsonify({'success': False, 'error': 'Permission denied'}), 403
        except OSError as e:
            logger.error(f"Error reading directory {path}: {e}")
            return jsonify({'success': False, 'error': f'Error reading directory: {str(e)}'}), 500
    
    except Exception as e:
        logger.error(f"Error in browse_directory: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/delete-directory', methods=['POST'])
def delete_directory(node):
    """Delete a directory"""
    try:
        data = request.get_json()
        path = data.get('path', '').strip()
        
        if not path:
            return jsonify({'success': False, 'error': 'Path is required'}), 400
        
        # Validate path
        if not path.startswith('/mnt/'):
            return jsonify({'success': False, 'error': 'Can only delete directories in /mnt'}), 400
        
        if '..' in path or path.count('/') < 3:
            return jsonify({'success': False, 'error': 'Invalid path'}), 400
        
        # Prevent deletion of important directories
        protected_paths = ['/mnt/sdb', '/mnt/storage', '/mnt/storage1']
        if path in protected_paths:
            return jsonify({'success': False, 'error': f'Cannot delete protected directory: {path}'}), 403
        
        try:
            if not os.path.exists(path):
                return jsonify({'success': False, 'error': 'Directory does not exist'}), 404
            
            if not os.path.isdir(path):
                return jsonify({'success': False, 'error': 'Path is not a directory'}), 400
            
            # Check if directory is empty
            if os.listdir(path):
                return jsonify({'success': False, 'error': 'Directory is not empty. Please delete contents first.'}), 400
            
            # Delete empty directory
            os.rmdir(path)
            logger.info(f"Deleted directory: {path}")
            
            return jsonify({
                'success': True,
                'message': f'Directory deleted successfully'
            })
        
        except PermissionError:
            return jsonify({'success': False, 'error': 'Permission denied'}), 403
        except OSError as e:
            logger.error(f"Error deleting directory {path}: {e}")
            return jsonify({'success': False, 'error': f'Failed to delete directory: {str(e)}'}), 500
    
    except Exception as e:
        logger.error(f"Error in delete_directory: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/nodes/<node>/scan/ceph', methods=['GET'])
def scan_ceph(node):
    """Scan for Ceph RBD pools"""
    try:
        proxmox = proxmox_service.get_proxmox()
        
        # Get list of RBD pools
        try:
            pools = proxmox.nodes(node).ceph.pools.get()
        except:
            pools = []
        
        # Transform pool data for display
        result = []
        if isinstance(pools, list):
            for pool in pools:
                result.append({
                    'name': pool.get('pool_name', pool.get('name', '')),
                    'pool': pool.get('pool_name', pool.get('name', '')),
                    'size': pool.get('size', pool.get('quota', 0)),
                    'used': pool.get('used', 0),
                    'type': 'ceph_pool',
                    'status': 'active'
                })
        
        return jsonify({'success': True, 'data': result})
    
    except Exception as e:
        logger.error(f"Error scanning Ceph: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@bp.route('/storage/info', methods=['GET'])
def storage_info():
    """Get storage info from Proxmox - queries each node for most accurate size data"""
    try:
        proxmox = proxmox_service.get_proxmox()
        
        # Get cluster storage list
        cluster_storage_list = proxmox.storage.get()
        
        # Get all nodes
        try:
            nodes = proxmox.nodes.get()
            node_names = [n.get('node') for n in nodes if n.get('node')]
        except:
            node_names = []
        
        result = []
        processed_storage = set()
        
        # For each node, get storage with actual size info
        for node_name in node_names:
            try:
                node_storage_list = proxmox.nodes(node_name).storage.get()
                for storage in node_storage_list:
                    storage_id = storage.get('storage', '')
                    
                    # Skip if already processed
                    if storage_id in processed_storage:
                        continue
                    
                    processed_storage.add(storage_id)
                    
                    # Get actual avail value (might be under 'avail' or 'available')
                    avail = storage.get('avail', 0) or storage.get('available', 0)
                    total = storage.get('total', 0) or storage.get('maxdisk', 0)
                    used = storage.get('used', 0) or storage.get('disk', 0)
                    
                    # For dir type storage, try to get accurate filesystem info
                    if storage.get('type') == 'dir' and storage.get('path'):
                        try:
                            import subprocess
                            path = storage.get('path')
                            result_df = subprocess.check_output(['df', path], text=True)
                            lines = result_df.strip().split('\n')
                            if len(lines) >= 2:
                                parts = lines[1].split()
                                if len(parts) >= 4:
                                    total = int(parts[1]) * 1024  # df shows KB
                                    used = int(parts[2]) * 1024
                                    avail = int(parts[3]) * 1024
                                    logger.info(f"Storage {storage_id} filesystem: {total} bytes total, {avail} bytes available")
                        except Exception as e:
                            logger.warning(f"Could not get filesystem stats for {storage_id}: {e}")
                    
                    info = {
                        'storage': storage_id,
                        'type': storage.get('type', ''),
                        'node': node_name,
                        'enabled': storage.get('enabled', 1),
                        'content': storage.get('content', ''),
                        'total': total,
                        'used': used,
                        'available': avail,  # Use 'available' for consistency
                        'avail': avail,  # Also include 'avail' for backward compatibility
                    }
                    
                    # Calculate GB values
                    if total > 0:
                        info['total_gb'] = round(total / (1024**3), 2)
                        info['used_gb'] = round(used / (1024**3), 2)
                        info['available_gb'] = round(avail / (1024**3), 2)
                        
                        logger.info(f"Storage {storage_id} (node {node_name}): {info['total_gb']}GB total, {info['available_gb']}GB available, {info['used_gb']}GB used")
                    
                    result.append(info)
            except Exception as e:
                logger.error(f"Error getting storage from node {node_name}: {e}")
        
        # Add any cluster-level storage not already processed
        for storage in cluster_storage_list:
            storage_id = storage.get('storage', '')
            if storage_id not in processed_storage:
                processed_storage.add(storage_id)
                
                # Get actual avail value
                avail = storage.get('avail', 0) or storage.get('available', 0)
                total = storage.get('total', 0) or storage.get('maxdisk', 0)
                used = storage.get('used', 0) or storage.get('disk', 0)
                
                info = {
                    'storage': storage_id,
                    'type': storage.get('type', ''),
                    'node': storage.get('nodes', ''),
                    'enabled': storage.get('enabled', 1),
                    'content': storage.get('content', ''),
                    'total': total,
                    'used': used,
                    'available': avail,  # Use 'available' for consistency
                    'avail': avail,  # Also include 'avail' for backward compatibility
                }
                
                if total > 0:
                    info['total_gb'] = round(total / (1024**3), 2)
                    info['used_gb'] = round(used / (1024**3), 2)
                    info['available_gb'] = round(avail / (1024**3), 2)
                    
                    logger.info(f"Storage {storage_id}: {info['total_gb']}GB total, {info['available_gb']}GB available")
                
                result.append(info)
        
        return jsonify({'success': True, 'data': result})
    
    except Exception as e:
        logger.error(f"Error getting storage info: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500
