# 🔐 SSL Certificate Setup Guide

This guide explains how to set up HTTPS with self-signed certificates for Silo HCI.

## Overview

Silo HCI uses HTTPS by default on port **8889** with self-signed SSL certificates. This provides encrypted communication between your browser and the Silo HCI server.

## Automatic Setup (Recommended)

The installation script automatically generates SSL certificates:

```bash
# During installation
sudo ./scripts/install.sh
```

Or generate manually:

```bash
# Generate SSL certificate
sudo ./scripts/setup_ssl.sh
```

## Manual SSL Certificate Generation

If you prefer to generate certificates manually:

```bash
# Create SSL directory
sudo mkdir -p /opt/silo-hci/ssl

# Generate private key
sudo openssl genrsa -out /opt/silo-hci/ssl/silo-hci.key 4096

# Generate certificate signing request
sudo openssl req -new -key /opt/silo-hci/ssl/silo-hci.key \
  -out /opt/silo-hci/ssl/silo-hci.csr \
  -subj "/C=TH/ST=Bangkok/L=Bangkok/O=Silo HCI/CN=silo-hci.local"

# Generate self-signed certificate (valid for 10 years)
sudo openssl x509 -req -days 3650 \
  -in /opt/silo-hci/ssl/silo-hci.csr \
  -signkey /opt/silo-hci/ssl/silo-hci.key \
  -out /opt/silo-hci/ssl/silo-hci.crt

# Set permissions
sudo chmod 600 /opt/silo-hci/ssl/silo-hci.key
sudo chmod 644 /opt/silo-hci/ssl/silo-hci.crt
```

## Certificate Information

### setup_ssl.sh Features

The `setup_ssl.sh` script provides:

- Interactive certificate generation
- Custom country, state, city, organization
- Custom Common Name (CN) or IP address
- Subject Alternative Names (SANs)
- 10-year validity period
- Automatic permission setup

### What You'll Be Asked

1. **Country Code** (e.g., TH, US, GB)
2. **State/Province** (e.g., Bangkok, California)
3. **City** (e.g., Bangkok, San Francisco)
4. **Organization** (e.g., Your Company Name)
5. **Common Name** (e.g., silo.example.com or 192.168.0.200)

## Using the Certificate

### Browser Warning

When you first access Silo HCI via HTTPS, your browser will show a security warning because the certificate is self-signed. This is normal and expected.

**To proceed:**

1. Click **"Advanced"** or **"Show Details"**
2. Click **"Proceed to [your-ip]"** or **"Accept the Risk and Continue"**
3. The warning will appear only on first visit

### Adding Certificate to Trusted Store (Optional)

To avoid browser warnings, add the certificate to your system's trusted certificate store:

#### Ubuntu/Debian

```bash
# Copy certificate to trusted store
sudo cp /opt/silo-hci/ssl/silo-hci.crt /usr/local/share/ca-certificates/silo-hci.crt

# Update certificates
sudo update-ca-certificates
```

#### CentOS/RHEL/Rocky Linux

```bash
# Copy certificate to trusted store
sudo cp /opt/silo-hci/ssl/silo-hci.crt /etc/pki/ca-trust/source/anchors/

# Update trust
sudo update-ca-trust
```

#### Windows

1. Download the certificate file (`silo-hci.crt`)
2. Double-click the certificate
3. Click **"Install Certificate"**
4. Select **"Local Machine"**
5. Choose **"Place all certificates in the following store"**
6. Browse to **"Trusted Root Certification Authorities"**
7. Click **OK** and **Finish**

#### macOS

1. Download the certificate file (`silo-hci.crt`)
2. Open **Keychain Access**
3. Drag the certificate to **System** keychain
4. Double-click the certificate
5. Expand **Trust** section
6. Set **"When using this certificate"** to **"Always Trust"**

#### Firefox (All Platforms)

Firefox uses its own certificate store:

1. Go to Settings → Privacy & Security → Certificates
2. Click **"View Certificates"** → **"Authorities"** tab
3. Click **"Import"**
4. Select your `silo-hci.crt` file
5. Check **"Trust this CA to identify websites"**
6. Click **OK**

## Using Commercial SSL Certificates

For production environments, you may want to use commercial SSL certificates from providers like Let's Encrypt, DigiCert, or Sectigo.

### Let's Encrypt (Free)

```bash
# Install certbot
sudo apt install certbot python3-certbot-nginx

# Generate certificate (requires domain name and port 80)
sudo certbot certonly --nginx -d your-domain.com

# Copy certificates to Silo HCI
sudo cp /etc/letsencrypt/live/your-domain.com/fullchain.pem /opt/silo-hci/ssl/silo-hci.crt
sudo cp /etc/letsencrypt/live/your-domain.com/privkey.pem /opt/silo-hci/ssl/silo-hci.key

# Set permissions
sudo chmod 600 /opt/silo-hci/ssl/silo-hci.key
sudo chmod 644 /opt/silo-hci/ssl/silo-hci.crt

# Restart services
sudo systemctl restart nginx
# or
docker-compose restart frontend
```

### Commercial Certificate

If you have a commercial certificate:

```bash
# Copy your certificate and key
sudo cp your-certificate.crt /opt/silo-hci/ssl/silo-hci.crt
sudo cp your-private-key.key /opt/silo-hci/ssl/silo-hci.key

# If you have a certificate chain
sudo cat your-certificate.crt intermediate.crt root.crt > /opt/silo-hci/ssl/silo-hci.crt

# Set permissions
sudo chmod 600 /opt/silo-hci/ssl/silo-hci.key
sudo chmod 644 /opt/silo-hci/ssl/silo-hci.crt

# Restart services
sudo systemctl restart nginx
# or
docker-compose restart frontend
```

## Docker Configuration

The docker-compose.yml file is already configured to mount the SSL directory:

```yaml
frontend:
  volumes:
    - ./ssl:/etc/nginx/ssl:ro
  ports:
    - "8889:8889"
```

## Nginx Configuration

The nginx.conf is configured for HTTPS with these settings:

```nginx
server {
    listen 8889 ssl http2;
    
    ssl_certificate /etc/nginx/ssl/silo-hci.crt;
    ssl_certificate_key /etc/nginx/ssl/silo-hci.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # Security headers
    add_header Strict-Transport-Security "max-age=31536000" always;
}
```

## Verification

### Check Certificate Validity

```bash
# View certificate details
openssl x509 -in /opt/silo-hci/ssl/silo-hci.crt -noout -text

# Check expiration date
openssl x509 -in /opt/silo-hci/ssl/silo-hci.crt -noout -dates

# Verify certificate and key match
openssl x509 -noout -modulus -in /opt/silo-hci/ssl/silo-hci.crt | openssl md5
openssl rsa -noout -modulus -in /opt/silo-hci/ssl/silo-hci.key | openssl md5
```

### Test HTTPS Connection

```bash
# Test SSL connection
curl -k https://localhost:8889/health

# View SSL certificate from browser
openssl s_client -connect localhost:8889 -showcerts
```

## Troubleshooting

### Certificate Not Found

**Error**: `nginx: [emerg] cannot load certificate`

**Solution**:
```bash
# Check if certificate exists
ls -la /opt/silo-hci/ssl/

# Regenerate certificate
sudo ./scripts/setup_ssl.sh

# Restart nginx
sudo systemctl restart nginx
```

### Permission Denied

**Error**: `Permission denied` when accessing certificate files

**Solution**:
```bash
# Fix permissions
sudo chmod 600 /opt/silo-hci/ssl/silo-hci.key
sudo chmod 644 /opt/silo-hci/ssl/silo-hci.crt
sudo chown root:root /opt/silo-hci/ssl/*
```

### Browser Still Shows Warning

**Cause**: Certificate not trusted by browser

**Solutions**:
1. Add certificate to system trusted store (see above)
2. Use commercial SSL certificate
3. Accept the risk and proceed (for testing)

### Wrong Port

**Error**: Connection refused or timeout

**Solution**:
```bash
# Check if Silo HCI is listening on port 8889
sudo netstat -tlnp | grep 8889

# Check firewall
sudo ufw allow 8889/tcp
# or
sudo firewall-cmd --permanent --add-port=8889/tcp
sudo firewall-cmd --reload
```

### Certificate Expired

**Solution**:
```bash
# Check expiration
openssl x509 -in /opt/silo-hci/ssl/silo-hci.crt -noout -dates

# Regenerate certificate
sudo ./scripts/setup_ssl.sh

# Restart services
sudo systemctl restart nginx
```

## Port Configuration

Silo HCI uses port **8889** by default for both HTTP and HTTPS:

- HTTP requests on port 8889 are automatically redirected to HTTPS
- HTTPS runs on port 8889 with SSL/TLS encryption

### Changing the Port

To use a different port, edit:

1. **docker-compose.yml** (Docker):
   ```yaml
   frontend:
     ports:
       - "YOUR_PORT:8889"
   ```

2. **nginx.conf**:
   ```nginx
   server {
       listen YOUR_PORT ssl http2;
   }
   ```

3. Restart services

## Security Best Practices

1. **Keep private key secure**: Never share or expose your private key
2. **Use strong encryption**: TLSv1.2 and TLSv1.3 only
3. **Regular updates**: Regenerate certificates before expiration
4. **Certificate monitoring**: Set up expiration alerts
5. **Use commercial certs in production**: For public-facing deployments
6. **Enable HSTS**: Already configured in nginx.conf
7. **Restrict access**: Use firewall rules to limit access

## References

- [OpenSSL Documentation](https://www.openssl.org/docs/)
- [Let's Encrypt](https://letsencrypt.org/)
- [Nginx SSL Configuration](https://nginx.org/en/docs/http/configuring_https_servers.html)
- [Mozilla SSL Configuration Generator](https://ssl-config.mozilla.org/)

---

**Need help?** Check the main [Installation Guide](INSTALLATION.md) or [README](../README.md)
