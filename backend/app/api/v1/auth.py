"""
Authentication API Routes
"""
from flask import Blueprint, request, session, jsonify
from app.models.user import db, User, AuditLog
from app.services.auth_service import AuthService
from functools import wraps
from datetime import datetime
import os

bp = Blueprint('auth', __name__, url_prefix='/api/v1/auth')

auth_service = AuthService()

def get_client_ip():
    """Get client IP address"""
    if request.headers.getlist("X-Forwarded-For"):
        return request.headers.getlist("X-Forwarded-For")[0]
    return request.remote_addr

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
        if 'user_id' not in session:
            return {'success': False, 'error': 'Unauthorized'}, 401
        
        user = User.query.get(session['user_id'])
        if not user or user.role != 'admin':
            return {'success': False, 'error': 'Admin access required'}, 403
        
        return f(*args, **kwargs)
    return decorated_function

@bp.route('/login', methods=['POST'])
def login():
    """User login endpoint"""
    try:
        data = request.get_json()
        username = data.get('username')
        password = data.get('password')
        
        if not username or not password:
            return {'success': False, 'error': 'Username and password required'}, 400
        
        user = User.query.filter_by(username=username).first()
        
        if not user or not user.check_password(password):
            # Log failed attempt
            log = AuditLog(
                action='LOGIN_FAILED',
                resource='AUTH',
                details=f'Failed login attempt for user: {username}',
                ip_address=get_client_ip()
            )
            db.session.add(log)
            db.session.commit()
            return {'success': False, 'error': 'Invalid credentials'}, 401
        
        if not user.is_active:
            return {'success': False, 'error': 'User account is disabled'}, 401
        
        # Check if 2FA is enabled
        if user.totp_enabled:
            session['user_id_temp'] = user.id
            session['require_2fa'] = True
            return {
                'success': True,
                'require_2fa': True,
                'message': 'Please enter your 2FA code'
            }, 200
        
        # Set session
        session['user_id'] = user.id
        session['username'] = user.username
        session['user_role'] = user.role
        
        # Update last login
        user.last_login = datetime.utcnow()
        db.session.commit()
        
        # Log successful login
        log = AuditLog(
            user_id=user.id,
            action='LOGIN_SUCCESS',
            resource='AUTH',
            ip_address=get_client_ip()
        )
        db.session.add(log)
        db.session.commit()
        
        return {
            'success': True,
            'message': 'Login successful',
            'user': user.to_dict()
        }, 200
    
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500


@bp.route('/verify-2fa', methods=['POST'])
def verify_2fa():
    """Verify 2FA code"""
    try:
        if 'user_id_temp' not in session:
            return {'success': False, 'error': 'Invalid 2FA request'}, 400
        
        data = request.get_json()
        code = data.get('code')
        
        if not code:
            return {'success': False, 'error': 'Code required'}, 400
        
        user = User.query.get(session['user_id_temp'])
        
        if not auth_service.verify_totp(user.totp_secret, code):
            return {'success': False, 'error': 'Invalid 2FA code'}, 401
        
        # Clear temp session
        session.pop('user_id_temp', None)
        session.pop('require_2fa', None)
        
        # Set actual session
        session['user_id'] = user.id
        session['username'] = user.username
        session['user_role'] = user.role
        
        # Update last login
        user.last_login = datetime.utcnow()
        db.session.commit()
        
        # Log 2FA verification
        log = AuditLog(
            user_id=user.id,
            action='2FA_VERIFIED',
            resource='AUTH',
            ip_address=get_client_ip()
        )
        db.session.add(log)
        db.session.commit()
        
        return {
            'success': True,
            'message': '2FA verified successfully',
            'user': user.to_dict()
        }, 200
    
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500


@bp.route('/logout', methods=['POST'])
@login_required
def logout():
    """User logout endpoint"""
    try:
        user_id = session.get('user_id')
        
        # Log logout
        log = AuditLog(
            user_id=user_id,
            action='LOGOUT',
            resource='AUTH',
            ip_address=get_client_ip()
        )
        db.session.add(log)
        db.session.commit()
        
        session.clear()
        return {'success': True, 'message': 'Logged out successfully'}, 200
    
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500


@bp.route('/me', methods=['GET'])
@login_required
def get_current_user():
    """Get current logged in user info"""
    try:
        user = User.query.get(session.get('user_id'))
        if not user:
            return {'success': False, 'error': 'User not found'}, 404
        
        return {'success': True, 'user': user.to_dict()}, 200
    
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500


@bp.route('/change-password', methods=['POST'])
@login_required
def change_password():
    """Change user password"""
    try:
        user = User.query.get(session.get('user_id'))
        data = request.get_json()
        
        old_password = data.get('old_password')
        new_password = data.get('new_password')
        
        if not old_password or not new_password:
            return {'success': False, 'error': 'Old and new password required'}, 400
        
        if not user.check_password(old_password):
            return {'success': False, 'error': 'Old password is incorrect'}, 401
        
        if len(new_password) < 8:
            return {'success': False, 'error': 'Password must be at least 8 characters'}, 400
        
        user.set_password(new_password)
        db.session.commit()
        
        # Log password change
        log = AuditLog(
            user_id=user.id,
            action='PASSWORD_CHANGED',
            resource='USER',
            resource_id=str(user.id),
            ip_address=get_client_ip()
        )
        db.session.add(log)
        db.session.commit()
        
        return {'success': True, 'message': 'Password changed successfully'}, 200
    
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500


# Admin endpoints

@bp.route('/users', methods=['GET'])
@admin_required
def list_users():
    """List all users (admin only)"""
    try:
        users = User.query.all()
        return {
            'success': True,
            'users': [user.to_dict() for user in users]
        }, 200
    
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500


@bp.route('/users', methods=['POST'])
@admin_required
def create_user():
    """Create new user (admin only)"""
    try:
        data = request.get_json()
        
        username = data.get('username')
        email = data.get('email')
        password = data.get('password')
        full_name = data.get('full_name')
        role = data.get('role', 'user')
        
        if not username or not email or not password:
            return {'success': False, 'error': 'Username, email, and password required'}, 400
        
        if User.query.filter_by(username=username).first():
            return {'success': False, 'error': 'Username already exists'}, 400
        
        if User.query.filter_by(email=email).first():
            return {'success': False, 'error': 'Email already exists'}, 400
        
        user = User(
            username=username,
            email=email,
            full_name=full_name,
            role=role,
            is_active=True
        )
        user.set_password(password)
        
        db.session.add(user)
        db.session.commit()
        
        # Log user creation
        log = AuditLog(
            user_id=session.get('user_id'),
            action='USER_CREATED',
            resource='USER',
            resource_id=str(user.id),
            details=f'Created user: {username}',
            ip_address=get_client_ip()
        )
        db.session.add(log)
        db.session.commit()
        
        return {
            'success': True,
            'message': 'User created successfully',
            'user': user.to_dict()
        }, 201
    
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500


@bp.route('/users/<int:user_id>', methods=['GET'])
@admin_required
def get_user(user_id):
    """Get user by ID (admin only)"""
    try:
        user = User.query.get(user_id)
        if not user:
            return {'success': False, 'error': 'User not found'}, 404
        
        return {'success': True, 'user': user.to_dict()}, 200
    
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500


@bp.route('/users/<int:user_id>', methods=['PUT'])
@admin_required
def update_user(user_id):
    """Update user (admin only)"""
    try:
        user = User.query.get(user_id)
        if not user:
            return {'success': False, 'error': 'User not found'}, 404
        
        data = request.get_json()
        
        if 'full_name' in data:
            user.full_name = data['full_name']
        if 'email' in data:
            user.email = data['email']
        if 'role' in data:
            user.role = data['role']
        if 'is_active' in data:
            user.is_active = data['is_active']
        
        db.session.commit()
        
        # Log user update
        log = AuditLog(
            user_id=session.get('user_id'),
            action='USER_UPDATED',
            resource='USER',
            resource_id=str(user_id),
            ip_address=get_client_ip()
        )
        db.session.add(log)
        db.session.commit()
        
        return {
            'success': True,
            'message': 'User updated successfully',
            'user': user.to_dict()
        }, 200
    
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500


@bp.route('/users/<int:user_id>', methods=['DELETE'])
@admin_required
def delete_user(user_id):
    """Delete user (admin only)"""
    try:
        if user_id == session.get('user_id'):
            return {'success': False, 'error': 'Cannot delete your own account'}, 400
        
        user = User.query.get(user_id)
        if not user:
            return {'success': False, 'error': 'User not found'}, 404
        
        username = user.username
        db.session.delete(user)
        db.session.commit()
        
        # Log user deletion
        log = AuditLog(
            user_id=session.get('user_id'),
            action='USER_DELETED',
            resource='USER',
            resource_id=str(user_id),
            details=f'Deleted user: {username}',
            ip_address=get_client_ip()
        )
        db.session.add(log)
        db.session.commit()
        
        return {'success': True, 'message': 'User deleted successfully'}, 200
    
    except Exception as e:
        return {'success': False, 'error': str(e)}, 500
