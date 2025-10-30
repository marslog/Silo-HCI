# Silo HCI - Quick Start Guide

## 🚀 ขั้นตอนการติดตั้ง

### 1. ติดตั้ง Dependencies

```bash
# Backend
cd /opt/silo-hci/backend
pip install -r requirements.txt

# Frontend (PHP)
cd /opt/silo-hci/frontend
composer install  # ถ้ายังไม่ได้
```

### 2. สร้าง Admin Account (ครั้งแรก)

ต้องสร้าง Admin Account ผ่าน Database API หรือ Python script:

```python
# สร้าง file: create_admin.py
from app import create_app
from app.models.user import db, User

app = create_app()

with app.app_context():
    # ตรวจสอบว่า admin ยังไม่มี
    admin = User.query.filter_by(username='admin').first()
    
    if not admin:
        admin = User(
            username='admin',
            email='admin@silo-hci.local',
            full_name='System Administrator',
            role='admin',
            is_active=True
        )
        admin.set_password('admin123')  # เปลี่ยนรหัสผ่านเพื่อความปลอดภัย
        
        db.session.add(admin)
        db.session.commit()
        
        print("✅ Admin account created successfully!")
        print("Username: admin")
        print("Password: admin123")
    else:
        print("⚠️ Admin account already exists")
```

ต่อจากนั้นรัน:
```bash
cd backend
python create_admin.py
```

### 3. เริ่มต้น Backend Server

```bash
cd /opt/silo-hci/backend
python wsgi.py

# หรือใช้ gunicorn สำหรับ production
gunicorn -w 4 -b 0.0.0.0:5000 wsgi:app
```

Backend จะทำงานที่ `http://localhost:5000`

### 4. เริ่มต้น Frontend Server

```bash
# ใช้ built-in PHP server
cd /opt/silo-hci/frontend/public
php -S localhost:8000

# หรือใช้ Apache/Nginx ตามที่ตั้งค่าไว้
```

Frontend จะทำงานที่ `http://localhost:8000`

---

## 🔐 Login & 2FA Setup

### Login ครั้งแรก
1. เปิด `http://localhost:8000/login`
2. ป้อน Username: `admin` และ Password: `admin123`
3. คลิก "Sign In"

### Setup 2FA (ทำให้ได้)
1. ไปที่ `/security/2fa`
2. คลิก "Generate QR Code"
3. ใช้แอพ Google Authenticator แล้ว Scan QR Code
4. ป้อน 6-digit code
5. บันทึก Backup Codes ไว้ที่ปลอดภัย

หลังจากนี้ทุกครั้งที่ login ต้องป้อน 2FA code

---

## 📋 ฟีเจอร์ที่พร้อมใช้งาน

### 1️⃣ Login Page
- ✅ Simple UI ที่สวยงาม
- ✅ 2FA Support
- ✅ Session Management

### 2️⃣ System Menu (Sidebar)
ไปที่ **System** ใน Sidebar จะเห็น:
- Generate Settings
- License Management
- Account Management

### 3️⃣ Generate Settings (`/system/generate`)
**สำหรับ Admin เท่านั้น**
- ตั้งค่า Date & Time
- ตั้งค่า NTP Server
- บันทึก Settings ลงฐานข้อมูล

```
API Endpoints:
GET  /api/v1/system/generate/datetime
PUT  /api/v1/system/generate/datetime
GET  /api/v1/system/generate/ntp
PUT  /api/v1/system/generate/ntp
```

### 4️⃣ License Management (`/system/license`)
**สำหรับ Admin เท่านั้น**
- ดู Device ID
- Export Device Info (JSON)
- Import Device Info
- Activate License
- ตรวจสอบสถานะ License

```
API Endpoints:
GET  /api/v1/system/license/device-info
GET  /api/v1/system/license/export
POST /api/v1/system/license/import
POST /api/v1/system/license/activate
GET  /api/v1/system/license/status
```

### 5️⃣ Account Management (`/system/account`)
**สำหรับ Admin เท่านั้น**
- สร้าง Account ใหม่
- แก้ไข Account
- ลบ Account
- เปลี่ยน Role (Admin/Operator/User)
- เปิด/ปิด Account
- ดู 2FA Status

```
API Endpoints:
GET    /api/v1/auth/users
POST   /api/v1/auth/users
GET    /api/v1/auth/users/<id>
PUT    /api/v1/auth/users/<id>
DELETE /api/v1/auth/users/<id>
```

### 6️⃣ Security/2FA (`/security/2fa`)
**สำหรับทุก User**
- Enable/Disable 2FA
- Generate QR Code
- Verify Setup
- Generate Backup Codes
- Regenerate Backup Codes

```
API Endpoints:
POST /api/v1/auth/totp/enable
POST /api/v1/auth/totp/verify-setup
POST /api/v1/auth/totp/disable
GET  /api/v1/auth/totp/status
POST /api/v1/auth/totp/regenerate-backup-codes
```

### 7️⃣ Settings (`/settings`)
**สำหรับทุก User**
- ดูข้อมูล Profile
- เปลี่ยน Password
- ไปที่ 2FA Settings

---

## 🧪 Testing

### Test Login API
```bash
curl -X POST http://localhost:5000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username":"admin",
    "password":"admin123"
  }'
```

### Test Get Current User
```bash
curl -X GET http://localhost:5000/api/v1/auth/me \
  -H "Cookie: session=YOUR_SESSION_ID"
```

### Test List Users
```bash
curl -X GET http://localhost:5000/api/v1/auth/users \
  -H "Cookie: session=YOUR_SESSION_ID"
```

### Test System Settings
```bash
curl -X GET http://localhost:5000/api/v1/system/generate/ntp \
  -H "Cookie: session=YOUR_SESSION_ID"
```

---

## 📊 Database Tables

ระบบจะสร้าง 4 tables โดยอัตโนมัติ:

1. **users** - ข้อมูล User
2. **system_settings** - ตั้งค่าระบบ
3. **device_licenses** - ข้อมูล License
4. **audit_logs** - บันทึก Activity

---

## 🔧 Configuration

### Backend Config
ไฟล์: `backend/app/config.py`

```python
# ปรับตามต้องการ
DEBUG = False
API_PREFIX = '/api/v1'
CACHE_ENABLED = True
CACHE_TTL = 60
```

### Frontend Config
ไฟล์: `frontend/src/Config/config.php`

```php
'api' => [
    'host' => 'localhost',
    'port' => 5000,
    'prefix' => '/api/v1',
    'timeout' => 30,
],
```

---

## ⚠️ Important Notes

1. **Default Password**: หลังจากสร้าง Admin account ให้เปลี่ยนรหัสผ่านทันที่
   - ไปที่ `/settings` คลิก "Change Password"
   - หรือ Admin เปลี่ยนให้ผ่าน `/system/account`

2. **2FA Setup**: แนะนำให้ setup 2FA ตั้งแต่ต้น
   - ไปที่ `/security/2fa`
   - Setup QR Code
   - บันทึก Backup Codes

3. **Database**: ใช้ SQLite โดยค่าเริ่มต้น
   - ไฟล์ database: `backend/database/silo.db`
   - สำหรับ Production ให้ใช้ PostgreSQL/MySQL

4. **Session**: Session ใช้ Flask cookie-based
   - Session TTL: 2 hours
   - ปรับในการ Production

5. **Password**: ใช้ SHA-256 ชั่วคราว
   - แนะนำให้ upgrade ไป bcrypt
   - แก้ไขใน `models/user.py`

---

## 🐛 Troubleshooting

### ❌ Login ไม่ได้
**สาเหตุ**: Admin account ยังไม่สร้าง หรือ Database ไม่พร้อม

**วิธีแก้**:
```bash
cd backend
python create_admin.py
```

### ❌ 2FA QR Code ไม่ขึ้น
**สาเหตุ**: Module `pyotp` หรือ `qrcode` ยังไม่ติดตั้ง

**วิธีแก้**:
```bash
pip install -r requirements.txt
```

### ❌ CORS Error
**สาเหตุ**: Frontend และ Backend อยู่คนละ origin

**วิธีแก้**: ปรับ CORS ใน `backend/app/__init__.py`:
```python
CORS(app, resources={
    r"/api/*": {
        "origins": "http://localhost:8000",  # เปลี่ยน origin
        ...
    }
})
```

### ❌ Database Error
**สาเหตุ**: Permission หรือ Path ผิด

**วิธีแก้**:
```bash
rm backend/database/silo.db
python wsgi.py  # สร้าง database ใหม่
```

---

## 📚 API Documentation

### Authentication Endpoints

#### POST /api/v1/auth/login
```json
Request:
{
  "username": "admin",
  "password": "admin123"
}

Response:
{
  "success": true,
  "message": "Login successful",
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@example.com",
    "role": "admin",
    "totp_enabled": false
  }
}
```

#### POST /api/v1/auth/logout
```json
Response:
{
  "success": true,
  "message": "Logged out successfully"
}
```

#### GET /api/v1/auth/me
```json
Response:
{
  "success": true,
  "user": {
    "id": 1,
    "username": "admin",
    ...
  }
}
```

---

## 📞 Support

หากมีปัญหา:
1. ตรวจสอบ Browser Console สำหรับ Error
2. ตรวจสอบ Backend Logs
3. ตรวจสอบ Database Connection
4. ตรวจสอบ API Endpoints

---

**ยินดีต้อนรับสู่ Silo HCI! 🎉**
