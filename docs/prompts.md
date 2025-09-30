1. 幫我view過整個專案，並啟用docker，確認前端與後端有正常連線，然後確保後台可以正常運行，目前後臺無法使用，幫我修正
2. 前端可以用npm run dev啟用嗎
3. 登入的API，/api/auth/login，出現 "Login failed: Failed to parse JSON string. Error: Syntax error" ✅ 已修復
   - 根本原因：auth.ts 中 $api() 呼叫時，body 參數沒有 JSON.stringify
   - fetch API 需要字符串格式的 body，傳入 JavaScript 物件會導致解析錯誤
   - 解決方案：在 auth.ts login 方法中將 body: loginData 改為 body: JSON.stringify(loginData)
   - 測試帳號：admin / password 或 testuser / password
   - 前端登入頁面：http://localhost:9117/auth/login