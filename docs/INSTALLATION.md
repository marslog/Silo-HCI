# Silo HCI Installation Guide

## Prerequisites

### System Requirements
- **OS**: Ubuntu 20.04+, Debian 11+, Rocky Linux 8+
- **RAM**: 2GB minimum, 4GB recommended
- **Storage**: 10GB minimum
- **CPU**: 2 cores minimum
- **Network**: Access to Proxmox VE cluster
- **Ports**: 8889 (HTTPS), 5000 (Backend API)

### Software Requirements
- Python 3.9+
- PHP 8.0+
- Nginx or Apache
- SQLite 3
- Composer
- OpenSSL (for SSL certificates)

## Installation Methods

### Method 1: Automated Installation (Recommended)

```bash
# Clone or download Silo HCI
cd /opt
git clone https://github.com/yourusername/silo-hci.git
cd silo-hci

# Run installation script
sudo ./scripts/install.sh
```

The script will automatically:
- Install all dependencies
- Set up Python virtual environment
- Install PHP packages
- Generate SSL certificate
- Configure Nginx with HTTPS on port 8889
- Create systemd services with auto-start
- Start all services

**Access**: `https://YOUR-SERVER-IP:8889`

### Method 2: Docker Installation

```bash
# Copy environment file
cp .env.example .env

# Copy Proxmox configuration
cp config/proxmox.json.example config/proxmox.json

# Edit configuration files
nano config/proxmox.json

# Generate SSL certificate
sudo ./scripts/setup_ssl.sh

# Start with Docker Compose (auto-restart enabled)
docker-compose up -d

# View logs
docker-compose logs -f
```

**Access**: `https://localhost:8889`

Docker containers will automatically restart on boot.

### Method 3: Manual Installation

#### 1. Install Dependencies

**Ubuntu/Debian:**
```bash
apt-get update
apt-get install -y python3 python3-pip python3-venv \
    php8.1 php8.1-fpm php8.1-curl php8.1-json \
    nginx sqlite3 composer openssl
```

**Rocky/CentOS:**
```bash
dnf install -y python39 python39-pip \
    php php-fpm php-curl php-json \
    nginx sqlite openssl

# Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

#### 2. Setup Backend

```bash
cd /opt/silo-hci/backend

# Create virtual environment
python3 -m venv venv
source venv/bin/activate

# Install Python packages
pip install -r requirements.txt
```

#### 3. Setup Frontend

```bash
cd /opt/silo-hci/frontend

# Install PHP dependencies
composer install --no-dev --optimize-autoloader
```

#### 4. Generate SSL Certificate

```bash
# Run SSL setup script
sudo /opt/silo-hci/scripts/setup_ssl.sh
```

Or generate manually:

```bash
sudo mkdir -p /opt/silo-hci/ssl
sudo openssl req -x509 -nodes -days 3650 -newkey rsa:4096 \
  -keyout /opt/silo-hci/ssl/silo-hci.key \
  -out /opt/silo-hci/ssl/silo-hci.crt \
  -subj "/C=TH/ST=Bangkok/L=Bangkok/O=Silo HCI/CN=silo-hci.local"
sudo chmod 600 /opt/silo-hci/ssl/silo-hci.key
sudo chmod 644 /opt/silo-hci/ssl/silo-hci.crt
```

#### 5. Configure Services

Create systemd service for backend:

```bash
sudo nano /etc/systemd/system/silo-backend.service
```

```ini
[Unit]
Description=Silo HCI Backend API
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/opt/silo-hci/backend
Environment="PATH=/opt/silo-hci/backend/venv/bin"
ExecStart=/opt/silo-hci/backend/venv/bin/gunicorn --bind 127.0.0.1:5000 --workers 4 wsgi:application
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Configure Nginx:

```bash
sudo nano /etc/nginx/sites-available/silo-hci
```

```nginx
# Redirect HTTP to HTTPS
server {
    listen 8889;
    server_name _;
    return 301 https://$host:8889$request_uri;
}

# HTTPS Server
server {
    listen 8889 ssl http2;
    server_name _;
    
    root /opt/silo-hci/frontend/public;
    index index.php;
    
    # SSL Configuration
    ssl_certificate /opt/silo-hci/ssl/silo-hci.crt;
    ssl_certificate_key /opt/silo-hci/ssl/silo-hci.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # Security headers
    add_header Strict-Transport-Security "max-age=31536000" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # URL aliases
    location /dashboard {
        try_files $uri $uri/ /index.php?url=dashboard;
    }

    location /nodes {
        try_files $uri $uri/ /index.php?url=nodes;
    }

    location /vms {
        try_files $uri $uri/ /index.php?url=vms;
    }

    location /containers {
        try_files $uri $uri/ /index.php?url=containers;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location /api/ {
        proxy_pass http://127.0.0.1:5000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-Proto https;
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/silo-hci /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
```

#### 6. Configure Firewall

```bash
# Ubuntu/Debian (UFW)
sudo ufw allow 8889/tcp

# CentOS/Rocky (firewalld)
sudo firewall-cmd --permanent --add-port=8889/tcp
sudo firewall-cmd --reload
```

#### 7. Start Services

```bash
sudo systemctl daemon-reload
sudo systemctl enable silo-backend nginx php8.1-fpm
sudo systemctl start silo-backend
sudo systemctl restart nginx php8.1-fpm
```

**Access**: `https://YOUR-SERVER-IP:8889`

## Configuration

### Proxmox Connection

Edit `/opt/silo-hci/config/proxmox.json`:

```json
{
  "proxmox": {
    "host": "proxmox.example.com",
    "port": 8006,
    "user": "root@pam",
    "token_name": "silo-api",
    "token_value": "your-api-token-here",
    "verify_ssl": false
  }
}
```

### Creating API Token in Proxmox

1. Login to Proxmox web interface
2. Go to **Datacenter** → **Permissions** → **API Tokens**
3. Click **Add**
4. Set:
   - User: `root@pam`
   - Token ID: `silo-api`
   - Privilege Separation: Unchecked (for full access)
5. Copy the token value
6. Add to `config/proxmox.json`

### Environment Variables

Edit `/opt/silo-hci/.env`:

```bash
APP_NAME="Silo HCI"
APP_ENV=production
APP_DEBUG=false

API_HOST=localhost
API_PORT=5000

CACHE_ENABLED=true
CACHE_TTL=60

OFFLINE_MODE=true
SYNC_INTERVAL=300
```

## Verification

### Check Services

```bash
# Backend status
sudo systemctl status silo-backend

# Nginx status
sudo systemctl status nginx

# View backend logs
sudo journalctl -u silo-backend -f

# View Nginx logs
sudo tail -f /var/log/nginx/access.log
```

### Test API

```bash
# Health check
curl http://localhost:5000/health

# Test Proxmox connection
curl http://localhost:5000/api/v1/nodes
```

### Access Web Interface

Open browser: `http://your-server-ip`

## Troubleshooting

### Backend won't start

```bash
# Check logs
sudo journalctl -u silo-backend -n 50

# Test manually
cd /opt/silo-hci/backend
source venv/bin/activate
python -m flask run
```

### Can't connect to Proxmox

```bash
# Test connection
cd /opt/silo-hci/backend
source venv/bin/activate
python
>>> from app.config import Config
>>> config = Config.get_proxmox_config()
>>> print(config)
```

### Permission errors

```bash
# Fix permissions
sudo chown -R www-data:www-data /opt/silo-hci/frontend
sudo chown -R www-data:www-data /opt/silo-hci/database
sudo chown -R www-data:www-data /opt/silo-hci/logs
```

## Upgrading

```bash
# Stop services
sudo systemctl stop silo-backend

# Backup
sudo ./scripts/backup.sh

# Pull updates
cd /opt/silo-hci
git pull

# Update dependencies
cd backend && source venv/bin/activate && pip install -r requirements.txt
cd ../frontend && composer update

# Restart services
sudo systemctl start silo-backend
sudo systemctl restart nginx
```

## Uninstallation

```bash
# Stop services
sudo systemctl stop silo-backend nginx

# Disable services
sudo systemctl disable silo-backend

# Remove files
sudo rm -rf /opt/silo-hci
sudo rm /etc/systemd/system/silo-backend.service
sudo rm /etc/nginx/sites-enabled/silo-hci
sudo rm /etc/nginx/sites-available/silo-hci

# Reload systemd
sudo systemctl daemon-reload
```

## Next Steps

- [Configure Offline Mode](OFFLINE_MODE.md)
- [Read API Documentation](API.md)
- Setup SSL/TLS certificates
- Configure firewall rules
- Set up automatic backups
