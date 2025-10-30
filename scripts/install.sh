#!/bin/bash
#
# Silo HCI Installation Script
# Supports: Ubuntu 20.04+, Debian 11+, Rocky Linux 8+
#

set -e

echo "======================================"
echo "  Silo HCI Installation Script"
echo "======================================"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run as root or with sudo"
    exit 1
fi

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
    VER=$VERSION_ID
else
    echo "Cannot detect OS"
    exit 1
fi

echo "Detected OS: $OS $VER"
echo ""

# Install dependencies based on OS
install_dependencies() {
    echo "Installing dependencies..."
    
    case $OS in
        ubuntu|debian)
            apt-get update
            apt-get install -y \
                python3 \
                python3-pip \
                python3-venv \
                php8.1 \
                php8.1-fpm \
                php8.1-curl \
                php8.1-json \
                nginx \
                sqlite3 \
                git \
                curl \
                composer
            ;;
        rocky|centos|rhel)
            dnf install -y \
                python39 \
                python39-pip \
                php \
                php-fpm \
                php-curl \
                php-json \
                nginx \
                sqlite \
                git \
                curl
            
            # Install composer
            curl -sS https://getcomposer.org/installer | php
            mv composer.phar /usr/local/bin/composer
            ;;
        *)
            echo "Unsupported OS: $OS"
            exit 1
            ;;
    esac
    
    echo "✓ Dependencies installed"
}

# Setup Python backend
setup_backend() {
    echo ""
    echo "Setting up Python backend..."
    
    cd /opt/silo-hci/backend
    
    # Create virtual environment
    python3 -m venv venv
    source venv/bin/activate
    
    # Install Python packages
    pip install --upgrade pip
    pip install -r requirements.txt
    
    deactivate
    
    echo "✓ Backend setup complete"
}

# Setup PHP frontend
setup_frontend() {
    echo ""
    echo "Setting up PHP frontend..."
    
    cd /opt/silo-hci/frontend
    
    # Install PHP dependencies
    composer install --no-dev --optimize-autoloader
    
    echo "✓ Frontend setup complete"
}

# Setup configuration
setup_config() {
    echo ""
    echo "Setting up configuration..."
    
    # Copy example configs
    if [ ! -f /opt/silo-hci/.env ]; then
        cp /opt/silo-hci/.env.example /opt/silo-hci/.env
        echo "✓ Created .env file"
    fi
    
    if [ ! -f /opt/silo-hci/config/proxmox.json ]; then
        cp /opt/silo-hci/config/proxmox.json.example /opt/silo-hci/config/proxmox.json
        echo "✓ Created proxmox.json"
    fi
    
    # Create directories
    mkdir -p /opt/silo-hci/database
    mkdir -p /opt/silo-hci/logs
    mkdir -p /opt/silo-hci/ssl
    
    # Generate SSL certificate
    echo ""
    echo "Generating SSL certificate..."
    /opt/silo-hci/scripts/setup_ssl.sh
    
    # Set permissions
    chown -R www-data:www-data /opt/silo-hci/frontend
    chown -R www-data:www-data /opt/silo-hci/database
    chown -R www-data:www-data /opt/silo-hci/logs
    chmod 600 /opt/silo-hci/ssl/*.key
    chmod 644 /opt/silo-hci/ssl/*.crt
    
    echo "✓ Configuration setup complete"
}

# Setup systemd services
setup_services() {
    echo ""
    echo "Setting up systemd services..."
    
    # Backend service
    cat > /etc/systemd/system/silo-backend.service <<EOF
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
EOF
    
    # Configure Nginx
    cat > /etc/nginx/sites-available/silo-hci <<EOF
# Redirect HTTP to HTTPS
server {
    listen 8889;
    server_name _;
    return 301 https://\$host:8889\$request_uri;
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
    ssl_prefer_server_ciphers on;
    
    # Security headers
    add_header Strict-Transport-Security "max-age=31536000" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    
    # Root location
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Dashboard alias
    location /dashboard {
        try_files \$uri \$uri/ /index.php?url=dashboard;
    }

    # Nodes alias
    location /nodes {
        try_files \$uri \$uri/ /index.php?url=nodes;
    }

    # VMs alias
    location /vms {
        try_files \$uri \$uri/ /index.php?url=vms;
    }

    # Containers alias
    location /containers {
        try_files \$uri \$uri/ /index.php?url=containers;
    }

    # Storage alias
    location /storage {
        try_files \$uri \$uri/ /index.php?url=storage;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location /api/ {
        proxy_pass http://127.0.0.1:5000;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-Proto https;
    }

    location /health {
        proxy_pass http://127.0.0.1:5000/health;
        access_log off;
    }
}
EOF
    
    # Enable site
    ln -sf /etc/nginx/sites-available/silo-hci /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    # Reload services
    systemctl daemon-reload
    systemctl enable silo-backend
    systemctl enable nginx
    systemctl enable php8.1-fpm
    
    echo "✓ Services configured"
}

# Start services
start_services() {
    echo ""
    echo "Starting services..."
    
    systemctl start silo-backend
    systemctl start php8.1-fpm
    systemctl restart nginx
    
    echo "✓ Services started"
}

# Main installation
main() {
    install_dependencies
    setup_backend
    setup_frontend
    setup_config
    setup_services
    start_services
    
    echo ""
    echo "======================================"
    echo "  Installation Complete!"
    echo "======================================"
    echo ""
    echo "Next steps:"
    echo "1. Edit /opt/silo-hci/config/proxmox.json with your Proxmox details"
    echo "2. Access Silo HCI at: https://$(hostname -I | awk '{print $1}'):8889"
    echo ""
    echo "Service commands:"
    echo "  systemctl status silo-backend"
    echo "  systemctl status nginx"
    echo ""
    echo "Logs:"
    echo "  journalctl -u silo-backend -f"
    echo "  tail -f /var/log/nginx/access.log"
    echo ""
    echo "Note: Your browser will show a security warning for the"
    echo "      self-signed certificate. This is normal. Click 'Advanced'"
    echo "      and 'Proceed' to continue."
    echo ""
}

# Run installation
main
