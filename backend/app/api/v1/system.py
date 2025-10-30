"""
System Settings API Routes
"""
from flask import Blueprint, request, session, jsonify
from app.models.user import db, SystemSettings, AuditLog
from functools import wraps
import json
import os

bp = Blueprint('system', __name__, url_prefix='/api/v1/system')

def login_required(f):
    """Decorator to check if user is logged in"""
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'user_id' not in session:
            return {'success': False, 'error': 'Unauthorized'}, 401
        return f(*args, **kwargs)
    return decorated_function

def admin_required(f):
    """Decorator to check if user is admin"""
    @wraps(f)
    def decorated_function(*args, **kwargs):
        from app.models.user import User
        if 'user_id' not in session:
            return {'success': False, 'error': 'Unauthorized'}, 401
        
        user = User.query.get(session['user_id'])
        if not user or user.role != 'admin':
            return {'success': False, 'error': 'Admin access required'}, 403
        
        return f(*args, **kwargs)
    return decorated_function

def get_client_ip():
    """Get client IP address"""
    if request.headers.getlist("X-Forwarded-For"):
        return request.headers.getlist("X-Forwarded-For")[0]
    return request.remote_addr

# ===== Generate Settings =====

@bp.route('/generate/datetime', methods=['GET'])
@login_required
def get_datetime():
    """Get current system date and time"""
    try:
        from datetime import datetime
        current_time = datetime.now()
        
        return {
            'success': True,
            'data': {
                'date': current_time.strftime('%Y-%m-%d'),
                'time': current_time.strftime('%H:%M:%S'),
                'timezone': os.environ.get('TZ', 'UTC'),
                'timestamp': int(current_time.timestamp())
            }
        }, 200
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500

@bp.route('/generate/datetime', methods=['PUT'])
@admin_required
def set_datetime():
    """Set system date and time"""
    try:
        data = request.get_json()
        date_str = data.get('date')
        time_str = data.get('time')
        
        if not date_str or not time_str:
            return {'success': False, 'error': 'Date and time required'}, 400
        
        # Note: In production, you would need proper system privileges to set datetime
        # This is a simplified version
        datetime_str = f"{date_str} {time_str}"
        
        # Log the attempt
        log = AuditLog(
            user_id=session.get('user_id'),
            action='DATETIME_CHANGED',
            resource='SYSTEM',
            details=f'Changed datetime to: {datetime_str}',
            ip_address=get_client_ip()
        )
        db.session.add(log)
        db.session.commit()
        
        return {
            'success': True,
            'message': 'Datetime setting saved',
            'datetime': datetime_str
        }, 200
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500

@bp.route('/generate/ntp', methods=['GET'])
@login_required
def get_ntp_settings():
    """Get NTP settings"""
    try:
        ntp = SystemSettings.query.filter_by(key='ntp_server').first()
        ntp_enabled = SystemSettings.query.filter_by(key='ntp_enabled').first()
        
        return {
            'success': True,
            'data': {
                'server': ntp.value if ntp else 'pool.ntp.org',
                'enabled': ntp_enabled.value == 'true' if ntp_enabled else False
            }
        }, 200
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500

@bp.route('/generate/ntp', methods=['PUT'])
@admin_required
def set_ntp_settings():
    """Set NTP settings"""
    try:
        data = request.get_json()
        server = data.get('server')
        enabled = data.get('enabled', True)
        
        if not server:
            return {'success': False, 'error': 'NTP server required'}, 400
        
        # Update or create NTP server setting
        ntp_setting = SystemSettings.query.filter_by(key='ntp_server').first()
        if not ntp_setting:
            ntp_setting = SystemSettings(key='ntp_server', value=server)
        else:
            ntp_setting.value = server
        
        db.session.add(ntp_setting)
        
        # Update or create NTP enabled setting
        ntp_enabled_setting = SystemSettings.query.filter_by(key='ntp_enabled').first()
        if not ntp_enabled_setting:
            ntp_enabled_setting = SystemSettings(key='ntp_enabled', value='true' if enabled else 'false')
        else:
            ntp_enabled_setting.value = 'true' if enabled else 'false'
        
        db.session.add(ntp_enabled_setting)
        
        # Log the change
        log = AuditLog(
            user_id=session.get('user_id'),
            action='NTP_CHANGED',
            resource='SYSTEM',
            details=f'NTP server set to: {server}, Enabled: {enabled}',
            ip_address=get_client_ip()
        )
        db.session.add(log)
        db.session.commit()
        
        return {
            'success': True,
            'message': 'NTP settings updated',
            'data': {
                'server': server,
                'enabled': enabled
            }
        }, 200
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500

# ===== License Management =====

@bp.route('/license/device-info', methods=['GET'])
@login_required
def get_device_info():
    """Get device information for license"""
    try:
        import socket
        import uuid
        
        # Collect device information
        hostname = socket.gethostname()
        device_id = str(uuid.getnode())
        
        # Get MAC addresses (simplified)
        try:
            mac = uuid.UUID(int=uuid.getnode()).hex[-12:]
        except:
            mac = 'unknown'
        
        device_info = {
            'hostname': hostname,
            'device_id': device_id,
            'mac_address': mac,
            'timestamp': int(__import__('time').time())
        }
        
        return {
            'success': True,
            'device_info': device_info
        }, 200
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500

@bp.route('/license/export', methods=['GET'])
@login_required
def export_device_info():
    """Export device info as JSON file"""
    try:
        import socket
        import uuid
        from datetime import datetime
        
        hostname = socket.gethostname()
        device_id = str(uuid.getnode())
        
        export_data = {
            'hostname': hostname,
            'device_id': device_id,
            'export_date': datetime.now().isoformat(),
            'system': 'Silo HCI'
        }
        
        return {
            'success': True,
            'data': export_data,
            'filename': f'silo-device-info-{device_id}.json'
        }, 200
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500

@bp.route('/license/import', methods=['POST'])
@admin_required
def import_device_info():
    """Import device info for license activation"""
    try:
        data = request.get_json()
        device_info_json = data.get('device_info')
        license_key = data.get('license_key')
        
        if not device_info_json or not license_key:
            return {'success': False, 'error': 'Device info and license key required'}, 400
        
        # Parse device info
        try:
            device_info = json.loads(device_info_json) if isinstance(device_info_json, str) else device_info_json
        except:
            return {'success': False, 'error': 'Invalid device info format'}, 400
        
        # Log import attempt
        log = AuditLog(
            user_id=session.get('user_id'),
            action='LICENSE_IMPORT',
            resource='LICENSE',
            details=f'Imported license for device: {device_info.get("hostname")}',
            ip_address=get_client_ip()
        )
        db.session.add(log)
        db.session.commit()
        
        return {
            'success': True,
            'message': 'Device info imported successfully'
        }, 200
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500

@bp.route('/license/activate', methods=['POST'])
@admin_required
def activate_license():
    """Activate license"""
    try:
        from datetime import datetime, timedelta
        from app.models.user import DeviceLicense
        
        data = request.get_json()
        device_id = data.get('device_id')
        license_key = data.get('license_key')
        device_info = data.get('device_info', {})
        
        if not device_id or not license_key:
            return {'success': False, 'error': 'Device ID and license key required'}, 400
        
        # Check if license already exists
        existing = DeviceLicense.query.filter_by(device_id=device_id).first()
        if existing:
            existing.license_key = license_key
            existing.license_status = 'active'
            existing.activation_date = datetime.utcnow()
            existing.expiry_date = datetime.utcnow() + timedelta(days=365)
            existing.device_info = json.dumps(device_info)
        else:
            license_obj = DeviceLicense(
                device_id=device_id,
                license_key=license_key,
                device_info=json.dumps(device_info),
                license_status='active',
                activation_date=datetime.utcnow(),
                expiry_date=datetime.utcnow() + timedelta(days=365)
            )
            db.session.add(license_obj)
        
        db.session.commit()
        
        # Log activation
        log = AuditLog(
            user_id=session.get('user_id'),
            action='LICENSE_ACTIVATED',
            resource='LICENSE',
            resource_id=device_id,
            details=f'License activated for device: {device_id}',
            ip_address=get_client_ip()
        )
        db.session.add(log)
        db.session.commit()
        
        return {
            'success': True,
            'message': 'License activated successfully',
            'device_id': device_id,
            'status': 'active'
        }, 200
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500

@bp.route('/license/status', methods=['GET'])
@login_required
def get_license_status():
    """Get license status"""
    try:
        from app.models.user import DeviceLicense
        import socket
        import uuid
        
        device_id = str(uuid.getnode())
        license_obj = DeviceLicense.query.filter_by(device_id=device_id).first()
        
        if not license_obj:
            return {
                'success': True,
                'license_status': 'inactive',
                'device_id': device_id
            }, 200
        
        return {
            'success': True,
            'license_status': license_obj.license_status,
            'device_id': device_id,
            'activation_date': license_obj.activation_date.isoformat() if license_obj.activation_date else None,
            'expiry_date': license_obj.expiry_date.isoformat() if license_obj.expiry_date else None
        }, 200
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500
