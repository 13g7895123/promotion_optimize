1. 幫我view過整個專案，並啟用docker，確認前端與後端有正常連線，然後確保後台可以正常運行，目前後臺無法使用，幫我修正
2. 前端可以用npm run dev啟用嗎
3. 登入的API，/api/auth/login，出現 "Login failed: Failed to parse JSON string. Error: Syntax error" ✅ 已修復
   - 根本原因：auth.ts 中 $api() 呼叫時，body 參數沒有 JSON.stringify
   - fetch API 需要字符串格式的 body，傳入 JavaScript 物件會導致解析錯誤
   - 解決方案：在 auth.ts login 方法中將 body: loginData 改為 body: JSON.stringify(loginData)
   - 測試帳號：admin / password 或 testuser / password
   - 前端登入頁面：http://localhost:9117/auth/login
4. 為甚麼登入成功不是到後台去，而是跑到前台 ✅ 已修復
   - 根本原因：userRoleLevel getter 只檢查中文角色名（'超管'、'管理員'），但後端回傳英文角色名（'super_admin'、'admin'）
   - 導致角色檢查失敗，所有用戶都被判定為 'user' 並導向 /dashboard
   - 解決方案：修正 userRoleLevel getter，同時支援英文和中文角色名稱
   - 路由邏輯：
     * super_admin/admin → /admin/dashboard (後台管理)
     * server_owner → /server/dashboard (服主管理)
     * reviewer → /reviewer/dashboard (審核員)
     * user → /dashboard (一般用戶前台)