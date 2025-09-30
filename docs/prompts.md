1. 幫我view過整個專案，並啟用docker，確認前端與後端有正常連線，然後確保後台可以正常運行，目前後臺無法使用，幫我修正
2. 前端可以用npm run dev啟用嗎
3. 登入的API，/api/auth/login，出現 "Login failed: Failed to parse JSON string. Error: Syntax error" ✅ 已修復
   - 問題：前端 auth store 使用 mock 資料，未串接真實後端 API
   - 解決：更新 stores/auth.js 的 login 函數，呼叫真實後端 /api/auth/login
   - 測試帳號：admin / password (super_admin) 或 testuser / password (user)