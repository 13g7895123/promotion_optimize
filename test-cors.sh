#!/bin/bash

# CORS and API Testing Script
# This script tests the backend API and CORS configuration

echo "========================================="
echo "CORS 和 API 測試腳本"
echo "========================================="

# Set API base URL (can be overridden with environment variable)
API_BASE=${API_BASE_URL:-"http://localhost:9217/api"}
FRONTEND_ORIGIN=${FRONTEND_ORIGIN:-"http://localhost:9117"}

echo "API Base URL: $API_BASE"
echo "Frontend Origin: $FRONTEND_ORIGIN"
echo ""

# Test 1: Health Check
echo "1. 測試健康檢查端點..."
echo "----------------------------------------"
curl -i -X GET "$API_BASE/health" \
  -H "Origin: $FRONTEND_ORIGIN" \
  -w "\n狀態碼: %{http_code}\n響應時間: %{time_total}s\n\n"

# Test 2: CORS GET Test
echo "2. 測試 CORS GET 請求..."
echo "----------------------------------------"
curl -i -X GET "$API_BASE/cors-test" \
  -H "Origin: $FRONTEND_ORIGIN" \
  -H "Content-Type: application/json" \
  -w "\n狀態碼: %{http_code}\n響應時間: %{time_total}s\n\n"

# Test 3: CORS OPTIONS (Preflight) Test
echo "3. 測試 CORS OPTIONS 預檢請求..."
echo "----------------------------------------"
curl -i -X OPTIONS "$API_BASE/cors-test" \
  -H "Origin: $FRONTEND_ORIGIN" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type,Authorization" \
  -w "\n狀態碼: %{http_code}\n響應時間: %{time_total}s\n\n"

# Test 4: CORS POST Test
echo "4. 測試 CORS POST 請求..."
echo "----------------------------------------"
curl -i -X POST "$API_BASE/cors-test" \
  -H "Origin: $FRONTEND_ORIGIN" \
  -H "Content-Type: application/json" \
  -d '{"message":"CORS test","timestamp":"'$(date -Iseconds)'"}' \
  -w "\n狀態碼: %{http_code}\n響應時間: %{time_total}s\n\n"

# Test 5: Auth Login Test (will fail but should not have CORS issues)
echo "5. 測試登入 API (應該返回認證錯誤，不是 CORS 錯誤)..."
echo "----------------------------------------"
curl -i -X POST "$API_BASE/auth/login" \
  -H "Origin: $FRONTEND_ORIGIN" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"wrongpassword"}' \
  -w "\n狀態碼: %{http_code}\n響應時間: %{time_total}s\n\n"

echo "========================================="
echo "測試完成！"
echo ""
echo "檢查點："
echo "1. 所有請求都應該包含 Access-Control-Allow-Origin 標頭"
echo "2. OPTIONS 請求應該返回 200 狀態碼"
echo "3. 不應該有 CORS 相關的錯誤訊息"
echo "4. 登入請求應該返回 401/422 而不是 CORS 錯誤"
echo "========================================="