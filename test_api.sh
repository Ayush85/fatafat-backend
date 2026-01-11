#!/bin/bash

# API Endpoint Testing Script
# Tests all major endpoints of the Fatafat Sewa API

BASE_URL="http://localhost:8002"
API_KEY="test-key-123"
TOKEN=""

echo "========================================="
echo "Fatafat Sewa API Endpoint Testing"
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test function
test_endpoint() {
    local method=$1
    local endpoint=$2
    local description=$3
    local data=$4
    local auth_header=$5
    
    echo -n "Testing: $description ... "
    
    if [ "$method" = "GET" ]; then
        if [ -n "$auth_header" ]; then
            response=$(curl -s -w "\n%{http_code}" -H "API-Key: $API_KEY" -H "Authorization: Bearer $TOKEN" "$BASE_URL$endpoint")
        else
            response=$(curl -s -w "\n%{http_code}" -H "API-Key: $API_KEY" "$BASE_URL$endpoint")
        fi
    else
        if [ -n "$auth_header" ]; then
            response=$(curl -s -w "\n%{http_code}" -X $method -H "Content-Type: application/json" -H "API-Key: $API_KEY" -H "Authorization: Bearer $TOKEN" -d "$data" "$BASE_URL$endpoint")
        else
            response=$(curl -s -w "\n%{http_code}" -X $method -H "Content-Type: application/json" -H "API-Key: $API_KEY" -d "$data" "$BASE_URL$endpoint")
        fi
    fi
    
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    
    if [ "$http_code" -ge 200 ] && [ "$http_code" -lt 300 ]; then
        echo -e "${GREEN}✓ PASS${NC} (HTTP $http_code)"
        return 0
    else
        echo -e "${RED}✗ FAIL${NC} (HTTP $http_code)"
        echo "  Response: $(echo $body | jq -r '.message // .error // .' 2>/dev/null || echo $body)"
        return 1
    fi
}

passed=0
failed=0

echo "=== PUBLIC ENDPOINTS ==="
echo ""

# Products
test_endpoint "GET" "/api/v1/products?per_page=5" "Get products (minimal)" && ((passed++)) || ((failed++))
test_endpoint "GET" "/api/v1/products?per_page=5&include=brand,categories" "Get products (with relationships)" && ((passed++)) || ((failed++))
test_endpoint "GET" "/api/v1/products/search?search=phone&per_page=3" "Search products" && ((passed++)) || ((failed++))
test_endpoint "GET" "/api/v1/products/1" "Get product by ID" && ((passed++)) || ((failed++))

# Categories
test_endpoint "GET" "/api/v1/categories?per_page=5" "Get categories" && ((passed++)) || ((failed++))
test_endpoint "GET" "/api/v1/categories/parents" "Get parent categories" && ((passed++)) || ((failed++))

# Brands
test_endpoint "GET" "/api/get-all-brands" "Get all brands" && ((passed++)) || ((failed++))

# Blogs
test_endpoint "GET" "/api/v1/blogs?per_page=5" "Get blogs" && ((passed++)) || ((failed++))

# Banners
test_endpoint "GET" "/api/v1/banners?per_page=5" "Get banners" && ((passed++)) || ((failed++))

echo ""
echo "=== AUTHENTICATION ENDPOINTS ==="
echo ""

# Register (create unique email each time)
RANDOM_EMAIL="test$(date +%s)@example.com"
REGISTER_DATA="{\"name\":\"Test User\",\"email\":\"$RANDOM_EMAIL\",\"password\":\"password123\",\"password_confirmation\":\"password123\",\"contact_number\":\"9841234567\"}"
if test_endpoint "POST" "/api/v1/register" "Register new user" "$REGISTER_DATA"; then
    ((passed++))
    # Extract token from response
    TOKEN=$(echo "$body" | jq -r '.data.access_token')
    echo "  Token obtained: ${TOKEN:0:20}..."
else
    ((failed++))
fi

# Login
LOGIN_DATA="{\"email\":\"$RANDOM_EMAIL\",\"password\":\"password123\"}"
if test_endpoint "POST" "/api/v1/login" "Login" "$LOGIN_DATA"; then
    ((passed++))
    TOKEN=$(echo "$body" | jq -r '.data.access_token')
else
    ((failed++))
fi

echo ""
echo "=== AUTHENTICATED ENDPOINTS ==="
echo ""

# Get user profile
test_endpoint "GET" "/api/v1/me" "Get user profile" "" "auth" && ((passed++)) || ((failed++))

# Cart
test_endpoint "GET" "/api/v1/cart" "Get cart" "" "auth" && ((passed++)) || ((failed++))

# Add to cart
CART_DATA="{\"product_id\":1,\"quantity\":2}"
test_endpoint "POST" "/api/v1/cart/items" "Add to cart" "$CART_DATA" "auth" && ((passed++)) || ((failed++))

# Orders
test_endpoint "GET" "/api/v1/orders" "Get orders" "" "auth" && ((passed++)) || ((failed++))

# Logout
test_endpoint "POST" "/api/v1/logout" "Logout" "" "auth" && ((passed++)) || ((failed++))

echo ""
echo "========================================="
echo "Test Results"
echo "========================================="
echo -e "${GREEN}Passed: $passed${NC}"
echo -e "${RED}Failed: $failed${NC}"
echo "Total: $((passed + failed))"
echo ""

if [ $failed -eq 0 ]; then
    echo -e "${GREEN}All tests passed! ✓${NC}"
    exit 0
else
    echo -e "${YELLOW}Some tests failed. Check the output above.${NC}"
    exit 1
fi
