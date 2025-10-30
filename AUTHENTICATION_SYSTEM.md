# Silo HCI - Enhanced Authentication & System Management

## What's New

นี่คือชุดของการอัปเดตที่เพิ่มเติมฟีเจอร์การรักษาความปลอดภัยและการจัดการระบบในระบบ Silo HCI

### ✨ ฟีเจอร์ใหม่

#### 1. **Authentication System (ระบบการยืนยันตัวตน)**
- ✅ Login Page - หน้าเข้าสู่ระบบที่ทันสมัย
- ✅ Session Management - จัดการ Session ของผู้ใช้
- ✅ Password Hashing - เข้ารหัสรหัสผ่าน
- ✅ API Authentication Endpoints - `/api/v1/auth/*`

#### 2. **Two-Factor Authentication (2FA) - Google Authenticator**
- ✅ QR Code Generation - สร้าง QR code สำหรับแอพ Authenticator
- ✅ TOTP Verification - ยืนยัน 6-digit code
- ✅ Backup Codes - รหัสสำรองสำหรับกรณีฉุกเฉิน
- ✅ 2FA Management Page - `/security/2fa`
- ✅ Enable/Disable 2FA - เปิด/ปิด 2FA ตามต้องการ
- ✅ API Endpoints - `/api/v1/auth/totp/*`

#### 3. **System Menu - Sidebar Navigation**
- ✅ System Submenu - เมนู System ใน Sidebar พร้อม Submenu

#### 4. **Generate Settings Module**
- ✅ Date & Time Settings - ตั้งค่าวันที่และเวลา
- ✅ NTP Server Configuration - ตั้งค่า NTP Server
- ✅ Frontend Page - `/system/generate`
- ✅ Backend API - `/api/v1/system/generate/*`
- ✅ Admin Only - ปกป้องด้วยสิทธิ์ Admin

#### 5. **License Management Module**
- ✅ Device Information Export - ส่งออกข้อมูลอุปกรณ์เป็น JSON
- ✅ Device Information Import - นำเข้าข้อมูลอุปกรณ์
- ✅ License Activation - เปิดใช้งาน License
- ✅ License Status Check - ตรวจสอบสถานะ License
- ✅ Frontend Page - `/system/license`
- ✅ Backend API - `/api/v1/system/license/*`
- ✅ Database Storage - บันทึก License ไว้ใน Database

#### 6. **Account Management Module**
- ✅ User CRUD Operations - สร้าง/อ่าน/แก้ไข/ลบ Account
- ✅ Role Management - Admin, Operator, User roles
- ✅ Account Status Control - เปิด/ปิด Account
- ✅ 2FA Status Display - แสดงสถานะ 2FA ของแต่ละ Account
- ✅ Frontend Page - `/system/account`
- ✅ Backend API - `/api/v1/auth/users/*`
- ✅ Audit Logging - บันทึก Log การจัดการ Account
- ✅ Admin Only - จัดการได้เฉพาะ Admin

---

## File Structure

### Backend (Python Flask)

```
backend/
├── app/
│   ├── models/
│   │   └── user.py                      # User, SystemSettings, DeviceLicense models
│   ├── services/
│   │   └── auth_service.py              # TOTP, QR Code, Backup Codes service
│   ├── api/
│   │   └── v1/
│   │       ├── auth.py                  # Authentication endpoints
│   │       ├── system.py                # System settings endpoints
│   │       └── totp.py                  # 2FA endpoints
│   └── __init__.py                      # Flask app initialization
└── requirements.txt                     # Updated dependencies
```

### Frontend (PHP)

```
frontend/
├── public/
│   ├── login.php                        # Login page
│   └── pages/
│       ├── system/
│       │   ├── generate.php             # Generate Settings page
│       │   ├── license.php              # License Management page
│       │   └── account.php              # Account Management page
│       └── security/
│           └── 2fa.php                  # 2FA Settings page
├── src/
│   ├── Utils/
│   │   └── Session.php                  # Session management utilities
│   └── Config/
│       └── config.php                   # Configuration
└── public/
    └── components/
        └── sidebar.php                  # Updated with System Menu
```

---

## API Endpoints

### Authentication
- `POST /api/v1/auth/login` - Login
- `POST /api/v1/auth/verify-2fa` - Verify 2FA code
- `POST /api/v1/auth/logout` - Logout
- `GET /api/v1/auth/me` - Get current user
- `POST /api/v1/auth/change-password` - Change password

### User Management (Admin)
- `GET /api/v1/auth/users` - List all users
- `POST /api/v1/auth/users` - Create user
- `GET /api/v1/auth/users/<id>` - Get user
- `PUT /api/v1/auth/users/<id>` - Update user
- `DELETE /api/v1/auth/users/<id>` - Delete user

### 2FA Management
- `POST /api/v1/auth/totp/enable` - Enable 2FA
- `POST /api/v1/auth/totp/verify-setup` - Verify 2FA setup
- `POST /api/v1/auth/totp/disable` - Disable 2FA
- `GET /api/v1/auth/totp/status` - Get 2FA status
- `POST /api/v1/auth/totp/regenerate-backup-codes` - Regenerate backup codes

### System Settings
- `GET /api/v1/system/generate/datetime` - Get date/time
- `PUT /api/v1/system/generate/datetime` - Set date/time
- `GET /api/v1/system/generate/ntp` - Get NTP settings
- `PUT /api/v1/system/generate/ntp` - Set NTP settings

### License Management
- `GET /api/v1/system/license/device-info` - Get device info
- `GET /api/v1/system/license/export` - Export device info
- `POST /api/v1/system/license/import` - Import device info
- `POST /api/v1/system/license/activate` - Activate license
- `GET /api/v1/system/license/status` - Get license status

---

## Database Schema

### Tables Created

#### `users`
```sql
- id (PK)
- username (unique)
- email (unique)
- password_hash
- full_name
- role (admin, operator, user)
- totp_secret
- totp_enabled
- backup_codes (JSON)
- is_active
- created_at
- updated_at
- last_login
```

#### `system_settings`
```sql
- id (PK)
- key (unique)
- value
- description
- created_at
- updated_at
```

#### `device_licenses`
```sql
- id (PK)
- device_id (unique)
- device_info (JSON)
- license_key (unique)
- license_status
- activation_date
- expiry_date
- created_at
- updated_at
```

#### `audit_logs`
```sql
- id (PK)
- user_id (FK)
- action
- resource
- resource_id
- details
- ip_address
- created_at
```

---

## Installation & Setup

### 1. Backend Setup

```bash
# Install Python dependencies
cd backend
pip install -r requirements.txt

# Database initialization (automatic on first run)
# Tables will be created by SQLAlchemy

# Run backend
python wsgi.py
```

### 2. Frontend Setup

Frontend ใช้ PHP ตรงกับที่ผ่านมา ไม่ต้องติดตั้งอะไรเพิ่มเติม

### 3. Environment Configuration

ระบบใช้ `.env` สำหรับ configuration (หากมี)

---

## Usage

### Login
1. ไปที่ `/login`
2. ป้อน Username และ Password
3. ถ้าเปิด 2FA ให้ป้อน 6-digit code จากแอพ Authenticator

### Setup 2FA
1. ไปที่ `/security/2fa`
2. คลิก "Generate QR Code"
3. Scan ด้วย Google Authenticator (หรือแอพ Authenticator อื่น)
4. ป้อน 6-digit code เพื่อยืนยัน
5. บันทึก Backup Codes

### Generate Settings
1. (Admin only) ไปที่ `/system/generate`
2. ตั้งค่า Date/Time หรือ NTP Server
3. คลิก Save

### License Management
1. (Admin only) ไปที่ `/system/license`
2. Export Device Info หรือ Import/Activate License

### Account Management
1. (Admin only) ไปที่ `/system/account`
2. สร้าง/แก้ไข/ลบ Account
3. กำหนด Role ให้ User

---

## Security Features

✅ Password Hashing (SHA-256, upgrade to bcrypt recommended)
✅ Session Management
✅ CSRF Protection (via token in forms)
✅ Admin-only Routes
✅ Audit Logging - บันทึก Action ทั้งหมด
✅ IP Address Logging
✅ 2FA with TOTP
✅ Backup Codes
✅ Password Change
✅ Account Disable/Enable

---

## New Dependencies

```
Flask-SQLAlchemy==3.1.1
pyotp==2.9.0
qrcode==7.4.2
bcrypt==4.1.1
```

---

## Frontend Pages

| Route | Description | Admin Only |
|-------|-------------|-----------|
| `/login` | Login Page | No |
| `/dashboard` | Dashboard | No |
| `/security/2fa` | 2FA Settings | No |
| `/system/generate` | Generate Settings | Yes |
| `/system/license` | License Management | Yes |
| `/system/account` | Account Management | Yes |

---

## Testing

### Login Test
```
Username: admin (create first via DB or API)
Password: (your password)
```

### 2FA Test
1. Setup 2FA
2. Logout
3. Login again dan verify 2FA code

### API Test
```bash
# Login
curl -X POST http://localhost:5000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'

# Get users
curl -X GET http://localhost:5000/api/v1/auth/users \
  -H "Cookie: session=..."

# Get system settings
curl -X GET http://localhost:5000/api/v1/system/generate/ntp \
  -H "Cookie: session=..."
```

---

## Notes

⚠️ **Important:**
- Password hashing ใช้ SHA-256 ชั่วคราว - ควร upgrade ไป bcrypt ในโปรดักชั่น
- Database ใช้ SQLite โดยค่าเริ่มต้น - สำหรับโปรดักชั่นให้ใช้ PostgreSQL/MySQL
- TOTP secret ถูกเก็บในฐานข้อมูล - ควรเข้ารหัสเพิ่มเติม
- CORS อยู่ในโหมด permissive ชั่วคราว - ปรับเปลี่ยนตามระบบจริง
- Audit logs ช่วยติดตามการเปลี่ยนแปลง - ตรวจสอบ `audit_logs` table

---

## Troubleshooting

### Issues with TOTP QR Code
- ตรวจสอบว่า `pyotp` และ `qrcode` ติดตั้งแล้ว
- ตรวจสอบ PIL/Pillow สำหรับ image processing

### 2FA Code Not Verifying
- ตรวจสอบเวลาระบบ - TOTP ต้องการเวลา sync
- ลองใช้ Backup Code แทน

### Database Issues
- ลบไฟล์ database และรัน app ใหม่ (สำหรับ SQLite)
- ตรวจสอบ `database/` directory permissions

---

## Future Enhancements

- [ ] Email verification
- [ ] Password reset via email
- [ ] Multi-tenancy support
- [ ] Advanced audit logs filtering
- [ ] License expiry notifications
- [ ] Backup codes as QR code
- [ ] WebAuthn/FIDO2 support
- [ ] OAuth2 integration

---

**Version:** 1.0.0  
**Last Updated:** October 29, 2025
