#!/bin/bash

# Quick Test Script for Storage Content Viewing Fix
# Tests the API endpoints to verify storage content viewing works

set -e

echo "════════════════════════════════════════════════════════════"
echo "  Storage Content Viewing - API Test Script"
echo "════════════════════════════════════════════════════════════"
echo ""

# Configuration
API_URL="${API_URL:-https://192.168.0.200:8889/api/v1}"
NODE="${NODE:-silo1}"
STORAGE="${STORAGE:-disk1}"
SKIP_SSL="${SKIP_SSL:-true}"

# SSL options
SSL_OPTS=""
if [ "$SKIP_SSL" = "true" ]; then
    SSL_OPTS="-k"
fi

echo "Configuration:"
echo "  API URL: $API_URL"
echo "  Node: $NODE"
echo "  Storage: $STORAGE"
echo ""

# Function to test API endpoint
test_endpoint() {
    local method=$1
    local endpoint=$2
    local data=$3
    local description=$4
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "TEST: $description"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "Endpoint: $method $API_URL$endpoint"
    
    if [ -n "$data" ]; then
        echo "Data: $data"
        echo ""
        curl $SSL_OPTS -X $method \
            -H "Content-Type: application/json" \
            -d "$data" \
            "$API_URL$endpoint" 2>/dev/null | jq . 2>/dev/null || echo "Response: (not JSON or error)"
    else
        echo ""
        curl $SSL_OPTS -X $method \
            -H "Content-Type: application/json" \
            "$API_URL$endpoint" 2>/dev/null | jq . 2>/dev/null || echo "Response: (not JSON or error)"
    fi
    echo ""
}

# Test 1: List all storage
echo "Test 1: List all storage"
test_endpoint "GET" "/storage" "" "Get all storage"

# Test 2: Get storage info
echo ""
echo "Test 2: Get storage info for '$STORAGE'"
test_endpoint "GET" "/storage/$STORAGE" "" "Get storage info"

# Test 3: Get storage content (the main fix)
echo ""
echo "Test 3: Get storage content for '$STORAGE' (MAIN TEST)"
test_endpoint "GET" "/nodes/$NODE/storage/$STORAGE/content" "" "Get storage content"

# Test 4: Browse root directory
echo ""
echo "Test 4: Browse /mnt directory"
test_endpoint "POST" "/nodes/$NODE/browse-directory" '{"path":"/mnt"}' "Browse /mnt"

# Test 5: Browse storage path
echo ""
echo "Test 5: Browse storage path"
test_endpoint "POST" "/nodes/$NODE/browse-directory" "{\"path\":\"/mnt/$STORAGE\"}" "Browse storage path"

echo ""
echo "════════════════════════════════════════════════════════════"
echo "  ✅ Test script completed"
echo "════════════════════════════════════════════════════════════"
echo ""
echo "Expected Results:"
echo "  Test 1: Shows list of all storage"
echo "  Test 2: Shows info about the storage"
echo "  Test 3: Shows files/folders in storage (NOT empty if storage has content)"
echo "  Test 4: Shows /mnt directory contents"
echo "  Test 5: Shows storage path contents"
echo ""
echo "If Test 3 shows empty data [], check:"
echo "  1. Does the storage path actually have files?"
echo "  2. Storage type in Proxmox"
echo "  3. Filesystem permissions"
echo "  4. Backend logs: docker logs silo-backend"
