#!/bin/bash

# Silo HCI - SSL Certificate Setup Script
# Generates self-signed SSL certificate for HTTPS

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}Silo HCI - SSL Certificate Setup${NC}"
echo -e "${GREEN}================================${NC}"
echo ""

# Configuration
SSL_DIR="/opt/silo-hci/ssl"
CERT_FILE="${SSL_DIR}/silo-hci.crt"
KEY_FILE="${SSL_DIR}/silo-hci.key"
DAYS_VALID=3650  # 10 years

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Error: Please run as root${NC}"
    exit 1
fi

# Create SSL directory
echo -e "${YELLOW}Creating SSL directory...${NC}"
mkdir -p "${SSL_DIR}"

# Check if certificate already exists
if [ -f "${CERT_FILE}" ] && [ -f "${KEY_FILE}" ]; then
    echo -e "${YELLOW}SSL certificate already exists!${NC}"
    read -p "Do you want to regenerate it? (y/N): " -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${GREEN}Keeping existing certificate.${NC}"
        exit 0
    fi
fi

# Get server information
echo -e "${YELLOW}Please provide certificate information:${NC}"
echo ""

read -p "Country Code (e.g., TH): " COUNTRY
COUNTRY=${COUNTRY:-TH}

read -p "State/Province (e.g., Bangkok): " STATE
STATE=${STATE:-Bangkok}

read -p "City (e.g., Bangkok): " CITY
CITY=${CITY:-Bangkok}

read -p "Organization (e.g., Your Company): " ORG
ORG=${ORG:-Silo HCI}

read -p "Common Name (e.g., silo.example.com or IP): " CN
CN=${CN:-silo-hci.local}

echo ""
echo -e "${YELLOW}Generating SSL certificate...${NC}"

# Generate private key
openssl genrsa -out "${KEY_FILE}" 4096 2>/dev/null

# Generate certificate signing request
openssl req -new -key "${KEY_FILE}" \
    -out "${SSL_DIR}/silo-hci.csr" \
    -subj "/C=${COUNTRY}/ST=${STATE}/L=${CITY}/O=${ORG}/CN=${CN}" 2>/dev/null

# Generate self-signed certificate
openssl x509 -req \
    -days ${DAYS_VALID} \
    -in "${SSL_DIR}/silo-hci.csr" \
    -signkey "${KEY_FILE}" \
    -out "${CERT_FILE}" \
    -extensions v3_req \
    -extfile <(cat <<EOF
[v3_req]
basicConstraints = CA:FALSE
keyUsage = nonRepudiation, digitalSignature, keyEncipherment
subjectAltName = @alt_names

[alt_names]
DNS.1 = ${CN}
DNS.2 = localhost
DNS.3 = *.${CN}
IP.1 = 127.0.0.1
IP.2 = ::1
EOF
) 2>/dev/null

# Clean up CSR
rm -f "${SSL_DIR}/silo-hci.csr"

# Set proper permissions
chmod 600 "${KEY_FILE}"
chmod 644 "${CERT_FILE}"

echo ""
echo -e "${GREEN}✓ SSL certificate generated successfully!${NC}"
echo ""
echo -e "${GREEN}Certificate details:${NC}"
echo "  Certificate: ${CERT_FILE}"
echo "  Private Key: ${KEY_FILE}"
echo "  Valid for: ${DAYS_VALID} days (until $(date -d "+${DAYS_VALID} days" '+%Y-%m-%d'))"
echo "  Common Name: ${CN}"
echo ""

# Display certificate information
echo -e "${YELLOW}Certificate Information:${NC}"
openssl x509 -in "${CERT_FILE}" -noout -subject -dates -fingerprint 2>/dev/null | sed 's/^/  /'

echo ""
echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}Next Steps:${NC}"
echo -e "${GREEN}================================${NC}"
echo ""
echo "1. If using Docker:"
echo "   docker-compose restart frontend"
echo ""
echo "2. If using direct installation:"
echo "   systemctl restart nginx"
echo ""
echo "3. Access Silo HCI via HTTPS:"
echo "   https://${CN}:8889/"
echo "   or"
echo "   https://YOUR-SERVER-IP:8889/"
echo ""
echo -e "${YELLOW}Note: Your browser will show a security warning because${NC}"
echo -e "${YELLOW}this is a self-signed certificate. This is normal.${NC}"
echo -e "${YELLOW}Click 'Advanced' and 'Proceed' to continue.${NC}"
echo ""
echo -e "${GREEN}To add certificate to trusted store (optional):${NC}"
echo ""
echo "Ubuntu/Debian:"
echo "  sudo cp ${CERT_FILE} /usr/local/share/ca-certificates/silo-hci.crt"
echo "  sudo update-ca-certificates"
echo ""
echo "CentOS/RHEL/Rocky:"
echo "  sudo cp ${CERT_FILE} /etc/pki/ca-trust/source/anchors/"
echo "  sudo update-ca-trust"
echo ""

exit 0
