# 🎉 Silo HCI - Update Summary

## ✅ การปรับปรุงที่ทำเสร็จแล้ว

### 1. 🔐 HTTPS with Self-Signed Certificate

#### ✅ สร้าง SSL Setup Script
- **ไฟล์**: `/opt/silo-hci/scripts/setup_ssl.sh`
- **คุณสมบัติ**:
  - Interactive certificate generation
  - รองรับ custom CN, SANs
  - Valid 10 years (3650 days)
  - Auto-generate 4096-bit RSA key
  - Proper permissions (600 for key, 644 for cert)
  - Support multiple domains/IPs

#### ✅ SSL Configuration
- **Location**: `/opt/silo-hci/ssl/`
  - `silo-hci.crt` - SSL Certificate
  - `silo-hci.key` - Private Key
- **Protocols**: TLSv1.2, TLSv1.3
- **Ciphers**: HIGH:!aNULL:!MD5
- **Security Headers**:
  - HSTS (Strict-Transport-Security)
  - X-Frame-Options: SAMEORIGIN
  - X-Content-Type-Options: nosniff
  - X-XSS-Protection

---

### 2. 🌐 Port 8889 และ URL Aliases

#### ✅ Nginx Configuration Updated
- **HTTP Port 8889**: Auto-redirect to HTTPS
- **HTTPS Port 8889**: SSL enabled with HTTP/2
- **Redirect**: `http://ip:8889` → `https://ip:8889`

#### ✅ URL Aliases (Clean URLs)
- ✅ `https://192.168.0.200:8889/` - Homepage
- ✅ `https://192.168.0.200:8889/dashboard` - Dashboard
- ✅ `https://192.168.0.200:8889/nodes` - Node Management
- ✅ `https://192.168.0.200:8889/vms` - Virtual Machines
- ✅ `https://192.168.0.200:8889/containers` - LXC Containers
- ✅ `https://192.168.0.200:8889/storage` - Storage Management
- ✅ `https://192.168.0.200:8889/network` - Network Configuration
- ✅ `https://192.168.0.200:8889/backup` - Backup Management

---

### 3. 🔄 Auto-Start Containers

#### ✅ Docker Compose
- **Changed**: `restart: unless-stopped` → `restart: always`
- **Backend**: Auto-restart on failure
- **Frontend**: Auto-restart on failure
- **Boot**: Containers start automatically on system boot

#### ✅ Systemd Services
- **silo-backend.service**:
  - `Restart=always`
  - `RestartSec=10` (retry after 10 seconds)
  - Enabled on boot: `systemctl enable silo-backend`
  
- **nginx.service**:
  - Auto-enabled
  - Auto-start on boot

---

## 📁 ไฟล์ที่ถูกแก้ไข/สร้าง

### ✅ New Files Created
1. **`/opt/silo-hci/scripts/setup_ssl.sh`** (New)
   - SSL certificate generation script
   - Interactive prompts
   - Validation and error handling

2. **`/opt/silo-hci/docs/SSL_SETUP.md`** (New)
   - Complete SSL setup guide
   - Browser certificate trust guide
   - Commercial cert installation
   - Troubleshooting

### ✅ Modified Files

3. **`/opt/silo-hci/docker/nginx.conf`**
   - Port 8889 (HTTP + HTTPS)
   - SSL configuration
   - URL aliases
   - Security headers
   - HTTP to HTTPS redirect

4. **`/opt/silo-hci/docker-compose.yml`**
   - `restart: always` for both services
   - SSL volume mount: `./ssl:/etc/nginx/ssl:ro`
   - Port mapping: `8889:8889`

5. **`/opt/silo-hci/scripts/install.sh`**
   - Auto SSL generation
   - SSL directory creation
   - Port 8889 configuration
   - Enhanced security
   - `RestartSec=10` in systemd

6. **`/opt/silo-hci/README.md`**
   - Updated port to 8889
   - Added HTTPS URLs
   - Added URL aliases section
   - Added auto-start section
   - Security features section

7. **`/opt/silo-hci/docs/INSTALLATION.md`**
   - Updated prerequisites (OpenSSL, Port 8889)
   - Added SSL generation steps
   - Updated nginx config with HTTPS
   - Added firewall configuration
   - URL aliases in nginx

---

## 🚀 วิธีใช้งาน

### การติดตั้งใหม่

#### Docker (แนะนำ)
```bash
cd /opt/silo-hci

# Generate SSL certificate
sudo ./scripts/setup_ssl.sh

# Start containers (auto-restart enabled)
docker-compose up -d

# Access
https://YOUR-IP:8889
```

#### Manual Installation
```bash
cd /opt/silo-hci

# Run installation (includes SSL setup)
sudo ./scripts/install.sh

# Services auto-start on boot
systemctl status silo-backend
systemctl status nginx

# Access
https://YOUR-IP:8889
```

### การอัพเดทจากเวอร์ชันเก่า

```bash
cd /opt/silo-hci

# Pull latest changes
git pull

# Generate SSL certificate
sudo ./scripts/setup_ssl.sh

# Rebuild Docker containers
docker-compose down
docker-compose up -d --build

# Or restart services (manual installation)
sudo systemctl restart silo-backend
sudo systemctl restart nginx
```

---

## 🔧 Configuration Examples

### Docker Compose
```yaml
services:
  backend:
    restart: always
    # ... other config

  frontend:
    restart: always
    volumes:
      - ./ssl:/etc/nginx/ssl:ro  # SSL certificates
    ports:
      - "8889:8889"  # HTTPS port
```

### Nginx (Docker)
```nginx
# HTTP → HTTPS redirect
server {
    listen 8889;
    return 301 https://$host:8889$request_uri;
}

# HTTPS server
server {
    listen 8889 ssl http2;
    
    ssl_certificate /etc/nginx/ssl/silo-hci.crt;
    ssl_certificate_key /etc/nginx/ssl/silo-hci.key;
    
    # URL aliases
    location /dashboard {
        try_files $uri $uri/ /index.php?url=dashboard;
    }
    # ... more aliases
}
```

### Systemd Service
```ini
[Service]
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

---

## 🌐 URL Examples

### ✅ Working URLs
```
https://192.168.0.200:8889/
https://192.168.0.200:8889/dashboard
https://192.168.0.200:8889/nodes
https://192.168.0.200:8889/vms
https://192.168.0.200:8889/containers
https://192.168.0.200:8889/storage
https://192.168.0.200:8889/network
https://192.168.0.200:8889/backup
```

### ❌ Auto-redirect (HTTP → HTTPS)
```
http://192.168.0.200:8889/  →  https://192.168.0.200:8889/
```

---

## 🔐 SSL Certificate Info

### Location
```
/opt/silo-hci/ssl/
├── silo-hci.crt  (Certificate, 644)
└── silo-hci.key  (Private Key, 600)
```

### Properties
- **Algorithm**: RSA 4096-bit
- **Validity**: 10 years (3650 days)
- **Subject**: Customizable (Country, State, City, Org, CN)
- **SANs**: Multiple domains/IPs supported
- **Protocols**: TLSv1.2, TLSv1.3

### Commands
```bash
# Generate
sudo /opt/silo-hci/scripts/setup_ssl.sh

# View certificate info
openssl x509 -in /opt/silo-hci/ssl/silo-hci.crt -noout -text

# Check expiration
openssl x509 -in /opt/silo-hci/ssl/silo-hci.crt -noout -dates

# Verify
curl -k https://localhost:8889/health
```

---

## 🔥 Firewall Configuration

### Ubuntu/Debian (UFW)
```bash
sudo ufw allow 8889/tcp
sudo ufw reload
```

### CentOS/Rocky (firewalld)
```bash
sudo firewall-cmd --permanent --add-port=8889/tcp
sudo firewall-cmd --reload
```

### Check
```bash
sudo netstat -tlnp | grep 8889
```

---

## ✅ Verification

### 1. Check Services
```bash
# Docker
docker-compose ps

# Manual
systemctl status silo-backend
systemctl status nginx
```

### 2. Check SSL
```bash
# View certificate
openssl s_client -connect localhost:8889 -showcerts

# Test HTTPS
curl -k https://localhost:8889/health
```

### 3. Check Auto-Start
```bash
# Docker
docker inspect silo-frontend | grep RestartPolicy

# Systemd
systemctl is-enabled silo-backend
systemctl is-enabled nginx
```

### 4. Test URLs
```bash
# Dashboard
curl -k https://localhost:8889/dashboard

# API
curl -k https://localhost:8889/api/v1/nodes
```

---

## 🐛 Troubleshooting

### Browser Security Warning
**Cause**: Self-signed certificate not trusted

**Solution**:
1. Click "Advanced" or "Show Details"
2. Click "Proceed" or "Accept Risk"
3. Or add certificate to trusted store (see docs/SSL_SETUP.md)

### Port Already in Use
```bash
# Check what's using port 8889
sudo netstat -tlnp | grep 8889

# Stop conflicting service
sudo systemctl stop <service-name>
```

### Container Not Starting
```bash
# View logs
docker-compose logs frontend

# Rebuild
docker-compose down
docker-compose up -d --build
```

### Certificate Error
```bash
# Regenerate
sudo ./scripts/setup_ssl.sh

# Restart
docker-compose restart frontend
# or
sudo systemctl restart nginx
```

---

## 📊 Summary

| Feature | Status | Details |
|---------|--------|---------|
| **HTTPS** | ✅ | Self-signed, TLSv1.2/1.3 |
| **Port** | ✅ | 8889 (HTTP redirects to HTTPS) |
| **URL Aliases** | ✅ | /dashboard, /nodes, /vms, etc. |
| **Auto-Start** | ✅ | Docker `restart: always`, Systemd enabled |
| **SSL Script** | ✅ | `/opt/silo-hci/scripts/setup_ssl.sh` |
| **Documentation** | ✅ | Updated README & INSTALLATION |
| **Security** | ✅ | HSTS, X-Frame-Options, etc. |

---

## 🎓 Next Steps

1. ✅ Generate SSL certificate: `sudo ./scripts/setup_ssl.sh`
2. ✅ Start services: `docker-compose up -d` or `./scripts/install.sh`
3. ✅ Configure Proxmox: `nano config/proxmox.json`
4. ✅ Access: `https://YOUR-IP:8889`
5. ✅ Add cert to browser (optional): See `docs/SSL_SETUP.md`
6. 🔧 Test all URL aliases
7. 🔧 Verify auto-start after reboot

---

## 📚 Documentation

- [README.md](../README.md) - Main documentation (updated)
- [INSTALLATION.md](../docs/INSTALLATION.md) - Installation guide (updated)
- [SSL_SETUP.md](../docs/SSL_SETUP.md) - SSL certificate guide (new)
- [API.md](../docs/API.md) - API documentation
- [OFFLINE_MODE.md](../docs/OFFLINE_MODE.md) - Offline mode guide

---

**ทุกอย่างพร้อมใช้งาน! 🎉**

Access Silo HCI at: **`https://YOUR-IP:8889`**

Example: **`https://192.168.0.200:8889/dashboard`**
