"""
Authentication Service
"""
import pyotp
import qrcode
import io
import base64
from datetime import datetime, timedelta

class AuthService:
    """Service for authentication operations"""
    
    @staticmethod
    def generate_totp_secret():
        """Generate TOTP secret for Google Authenticator"""
        return pyotp.random_base32()
    
    @staticmethod
    def get_totp_qrcode(username, secret, issuer="Silo HCI"):
        """Generate QR code for TOTP setup"""
        totp = pyotp.TOTP(secret)
        uri = totp.provisioning_uri(name=username, issuer_name=issuer)
        
        # Generate QR code
        qr = qrcode.QRCode(version=1, box_size=10, border=5)
        qr.add_data(uri)
        qr.make(fit=True)
        
        img = qr.make_image(fill_color="black", back_color="white")
        
        # Convert to base64
        buffer = io.BytesIO()
        img.save(buffer, format='PNG')
        img_str = base64.b64encode(buffer.getvalue()).decode()
        
        return {
            'secret': secret,
            'qrcode': f'data:image/png;base64,{img_str}',
            'uri': uri
        }
    
    @staticmethod
    def verify_totp(secret, token, window=1):
        """Verify TOTP token"""
        if not secret:
            return False
        
        try:
            totp = pyotp.TOTP(secret)
            return totp.verify(token, valid_window=window)
        except:
            return False
    
    @staticmethod
    def generate_backup_codes(count=10):
        """Generate backup codes for 2FA"""
        import secrets
        codes = []
        for _ in range(count):
            code = secrets.token_hex(4).upper()
            codes.append(code)
        return codes
