# 🚀 Silo HCI - Proxmox Management Platform

<p align="center">
  <img src="frontend/public/assets/img/logo-silo.png" alt="Silo HCI Logo" width="200"/>
</p>

> Production-ready Hyperconverged Infrastructure management platform based on Proxmox VE

## 📋 Overview

Silo HCI is a modern, enterprise-grade web management interface for Proxmox VE clusters. Built with PHP frontend and Flask Python backend, it provides a clean and intuitive interface for managing virtual machines, containers, storage, and networking - with full offline capability.

## ✨ Features

### 🖥️ Virtual Machine Management
- Create, clone, and manage QEMU VMs
- Console access (VNC/SPICE)
- Snapshot management
- Live migration
- Resource monitoring

### 📦 Container Management
- LXC container lifecycle management
- Template management
- Resource allocation
- Quick deployment

### 💾 Storage Management
- Multi-protocol storage support (NFS, iSCSI, Ceph, ZFS)
- Volume management
- Storage pool monitoring
- Backup storage configuration

### 🌐 Network Management
- Virtual network configuration
- Bridge management
- Firewall rules
- VLAN support

### 📊 Monitoring & Analytics
- Real-time performance metrics
- Resource utilization charts
- Historical data tracking
- Alert notifications

### 🔄 Backup & Restore
- Scheduled backup jobs
- Manual backup/restore
- Multiple backup targets
- Retention policies

### 🔌 Offline Mode (100%)
- Complete offline functionality
- Local SQLite caching
- Auto-sync when online
- No internet dependency

## 🏗️ Architecture

```
┌─────────────────┐
│  Web Browser    │
└────────┬────────┘
         │
    ┌────▼────┐
    │  Nginx  │
    └────┬────┘
         │
    ┌────▼──────────┐      ┌──────────────┐
    │  PHP Frontend │◄─────┤  Flask API   │
    └───────────────┘      └──────┬───────┘
                                  │
                           ┌──────▼───────┐
                           │  Proxmox VE  │
                           │  API Server  │
                           └──────────────┘
```

## 📦 Requirements

### System Requirements
- OS: Ubuntu 20.04+ / Debian 11+ / Rocky Linux 8+
- RAM: 2GB minimum, 4GB recommended
- Storage: 10GB minimum
- Network: 1Gbps recommended

### Software Requirements
- PHP 8.0+
- Python 3.9+
- Nginx 1.18+
- SQLite 3.x
- Docker & Docker Compose (optional)

## � Security Features

- **HTTPS by Default**: Self-signed SSL certificates (8889 port)
- **API Token Authentication**: Secure Proxmox API access
- **Session Management**: Secure PHP sessions
- **SSL/TLS**: TLSv1.2 and TLSv1.3 support
- **Security Headers**: HSTS, X-Frame-Options, CSP

## �🚀 Quick Start

### Prerequisites

- Ubuntu 20.04+, Debian 11+, or Rocky Linux 8+
- Proxmox VE 7.0+
- Python 3.9+
- PHP 8.0+
- Nginx

### Method 1: Docker (Recommended)

```bash
# Clone repository
git clone https://github.com/yourusername/silo-hci.git
cd silo-hci

# Copy configuration
cp .env.example .env
cp config/proxmox.json.example config/proxmox.json

# Edit configuration
nano config/proxmox.json

# Generate SSL certificate
sudo ./scripts/setup_ssl.sh

# Start with Docker Compose (auto-restart enabled)
docker-compose up -d

# Access at https://localhost:8889
```

### Method 2: Manual Installation

```bash
# Install dependencies and setup SSL
./scripts/install.sh

# Configure Proxmox connection
cp config/proxmox.json.example config/proxmox.json
nano config/proxmox.json

# Services will auto-start on boot
systemctl status silo-backend
systemctl status nginx

# Access at https://your-server-ip:8889
```

## ⚙️ Configuration

### Proxmox Connection

Edit `config/proxmox.json`:

```json
{
  "proxmox": {
    "host": "proxmox.example.com",
    "port": 8006,
    "user": "root@pam",
    "password": "your-password",
    "verify_ssl": false
  },
  "cache": {
    "enabled": true,
    "ttl": 60
  },
  "offline_mode": {
    "enabled": true,
    "sync_interval": 300
  }
}
```

### API Token (Recommended)

For better security, use API tokens instead of passwords:

```json
{
  "proxmox": {
    "host": "proxmox.example.com",
    "port": 8006,
    "user": "root@pam",
    "token_name": "silo-api",
    "token_value": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
    "verify_ssl": false
  }
}
```

## 🌐 URL Aliases

Silo HCI supports clean URL aliases for easy navigation:

- `https://your-ip:8889/` - Homepage
- `https://your-ip:8889/dashboard` - Dashboard
- `https://your-ip:8889/nodes` - Node Management
- `https://your-ip:8889/vms` - Virtual Machines
- `https://your-ip:8889/containers` - LXC Containers
- `https://your-ip:8889/storage` - Storage Management
- `https://your-ip:8889/network` - Network Configuration
- `https://your-ip:8889/backup` - Backup Management

Example: `https://192.168.0.200:8889/dashboard`

## 🔄 Auto-Start Configuration

Both Docker containers and systemd services are configured to automatically start on boot:

**Docker**: `restart: always` policy in docker-compose.yml
**Systemd**: Services enabled with `systemctl enable`

## 📖 Documentation

- [Installation Guide](docs/INSTALLATION.md)
- [API Documentation](docs/API.md)
- [Offline Mode Guide](docs/OFFLINE_MODE.md)
- [SSL Certificate Guide](docs/SSL_SETUP.md)

## 🔒 Security

- API authentication via tokens
- Role-based access control (RBAC)
- Session management
- CSRF protection
- SSL/TLS support

## 🌍 Offline Mode

Silo HCI works 100% offline:

1. **Initial Sync**: Connect once to sync data
2. **Offline Operation**: Full functionality without internet
3. **Auto-Sync**: Automatically syncs when connection restored
4. **Conflict Resolution**: Smart merge of offline changes

Enable offline mode:

```bash
./scripts/setup_offline.sh
```

## 🛠️ Development

### Frontend (PHP)

```bash
cd frontend
composer install
php -S localhost:8000 -t public
```

### Backend (Flask)

```bash
cd backend
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
flask run --host=0.0.0.0 --port=5000
```

## 🧪 Testing

```bash
# Backend tests
cd backend
pytest

# Frontend tests
cd frontend
composer test
```

## 📊 Performance

- API Response: < 100ms
- Dashboard Load: < 2s
- VM Create: < 30s
- Real-time Updates: 1s interval

## 🤝 Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 📝 License

This project is licensed under the MIT License - see [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- [Proxmox VE](https://www.proxmox.com/) - Base platform
- [ProxmoxVE PHP API](https://github.com/Saleh7/ProxmoxVE_PHP_API) - API reference
- [Arclight](https://github.com/Chatnaut/Arclight) - UI inspiration

## 📞 Support

- Issues: [GitHub Issues](https://github.com/yourusername/silo-hci/issues)
- Docs: [Documentation](https://docs.silo-hci.io)
- Discord: [Join Community](https://discord.gg/silo-hci)

## 🗺️ Roadmap

- [x] Basic VM/Container management
- [x] Storage management
- [x] Network configuration
- [x] Monitoring dashboard
- [x] Offline mode
- [ ] High Availability setup
- [ ] Multi-cluster support
- [ ] Mobile app
- [ ] Kubernetes integration

---

Made with ❤️ for the Proxmox community
