"""
Two-Factor Authentication API
"""
from flask import Blueprint, request, session, jsonify
from app.models.user import db, User, AuditLog
from app.services.auth_service import AuthService
from functools import wraps
import json

bp = Blueprint('totp', __name__, url_prefix='/api/v1/auth/totp')

auth_service = AuthService()

def login_required(f):
    """Decorator to check if user is logged in"""
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'user_id' not in session:
            return {'success': False, 'error': 'Unauthorized'}, 401
        return f(*args, **kwargs)
    return decorated_function

def get_client_ip():
    """Get client IP address"""
    if request.headers.getlist("X-Forwarded-For"):
        return request.headers.getlist("X-Forwarded-For")[0]
    return request.remote_addr

@bp.route('/enable', methods=['POST'])
@login_required
def enable_2fa():
    """Enable 2FA for current user"""
    try:
        user = User.query.get(session.get('user_id'))
        
        # Generate new secret
        secret = auth_service.generate_totp_secret()
        
        # Generate QR code
        qr_info = auth_service.get_totp_qrcode(user.username, secret)
        
        # Generate backup codes
        backup_codes = auth_service.generate_backup_codes(10)
        
        # Store temporarily in session (not in database yet until verified)
        session['temp_totp_secret'] = secret
        session['temp_backup_codes'] = backup_codes
        
        return {
            'success': True,
            'secret': secret,
            'qrcode': qr_info['qrcode'],
            'backup_codes': backup_codes,
            'message': 'Scan the QR code with your authenticator app'
        }, 200
    
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500

@bp.route('/verify-setup', methods=['POST'])
@login_required
def verify_2fa_setup():
    """Verify 2FA setup with code"""
    try:
        data = request.get_json()
        code = data.get('code')
        
        if not code:
            return {'success': False, 'error': 'Code required'}, 400
        
        if 'temp_totp_secret' not in session:
            return {'success': False, 'error': 'Start 2FA setup first'}, 400
        
        secret = session.get('temp_totp_secret')
        
        # Verify code
        if not auth_service.verify_totp(secret, code):
            return {'success': False, 'error': 'Invalid code'}, 401
        
        # Save to database
        user = User.query.get(session.get('user_id'))
        user.totp_secret = secret
        user.totp_enabled = True
        
        # Save backup codes as JSON
        backup_codes = session.get('temp_backup_codes', [])
        user.backup_codes = json.dumps(backup_codes)
        
        db.session.commit()
        
        # Clear temporary session data
        session.pop('temp_totp_secret', None)
        session.pop('temp_backup_codes', None)
        
        # Log 2FA enable
        log = AuditLog(
            user_id=user.id,
            action='2FA_ENABLED',
            resource='USER',
            resource_id=str(user.id),
            ip_address=get_client_ip()
        )
        db.session.add(log)
        db.session.commit()
        
        return {
            'success': True,
            'message': '2FA enabled successfully',
            'backup_codes': backup_codes
        }, 200
    
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500

@bp.route('/disable', methods=['POST'])
@login_required
def disable_2fa():
    """Disable 2FA for current user"""
    try:
        data = request.get_json()
        password = data.get('password')
        
        if not password:
            return {'success': False, 'error': 'Password required'}, 400
        
        user = User.query.get(session.get('user_id'))
        
        # Verify password
        if not user.check_password(password):
            return {'success': False, 'error': 'Invalid password'}, 401
        
        # Disable 2FA
        user.totp_secret = None
        user.totp_enabled = False
        user.backup_codes = None
        
        db.session.commit()
        
        # Log 2FA disable
        log = AuditLog(
            user_id=user.id,
            action='2FA_DISABLED',
            resource='USER',
            resource_id=str(user.id),
            ip_address=get_client_ip()
        )
        db.session.add(log)
        db.session.commit()
        
        return {'success': True, 'message': '2FA disabled successfully'}, 200
    
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500

@bp.route('/status', methods=['GET'])
@login_required
def get_2fa_status():
    """Get 2FA status for current user"""
    try:
        user = User.query.get(session.get('user_id'))
        
        return {
            'success': True,
            'totp_enabled': user.totp_enabled,
            'has_backup_codes': bool(user.backup_codes)
        }, 200
    
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500

@bp.route('/regenerate-backup-codes', methods=['POST'])
@login_required
def regenerate_backup_codes():
    """Regenerate backup codes"""
    try:
        data = request.get_json()
        password = data.get('password')
        
        if not password:
            return {'success': False, 'error': 'Password required'}, 400
        
        user = User.query.get(session.get('user_id'))
        
        # Verify password
        if not user.check_password(password):
            return {'success': False, 'error': 'Invalid password'}, 401
        
        # Generate new backup codes
        backup_codes = auth_service.generate_backup_codes(10)
        user.backup_codes = json.dumps(backup_codes)
        
        db.session.commit()
        
        # Log regeneration
        log = AuditLog(
            user_id=user.id,
            action='BACKUP_CODES_REGENERATED',
            resource='USER',
            resource_id=str(user.id),
            ip_address=get_client_ip()
        )
        db.session.add(log)
        db.session.commit()
        
        return {
            'success': True,
            'message': 'Backup codes regenerated successfully',
            'backup_codes': backup_codes
        }, 200
    
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500
