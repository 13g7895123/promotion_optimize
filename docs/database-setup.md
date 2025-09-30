# 資料庫設置指南

## 快速開始

### 自動設置資料庫

使用提供的 SQL 腳本快速設置完整的資料庫結構：

```bash
# 執行資料庫設置腳本
docker exec -i promotion_mysql mysql -uroot -proot_password promotion_platform < backend/setup_database.sql
```

這將會：
1. 清除現有的表
2. 建立所有必要的表結構
3. 插入預設角色
4. 建立測試用戶

---

## 測試用戶帳號

### 管理員帳號
- **Username**: `admin`
- **Email**: `admin@test.com`
- **Password**: `password`
- **Role**: Super Administrator (super_admin)

### 一般用戶帳號
- **Username**: `testuser`
- **Email**: `user@test.com`
- **Password**: `password`
- **Role**: User (user)

---

## 資料庫結構

### 核心表

#### roles (角色表)
```sql
CREATE TABLE `roles` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `display_name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `level` TINYINT(1) UNSIGNED NOT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` DATETIME,
  `updated_at` DATETIME
);
```

**預設角色**:
1. `super_admin` - Super Administrator (level 1)
2. `admin` - Administrator (level 2)
3. `server_owner` - Server Owner (level 3)
4. `reviewer` - Reviewer (level 4)
5. `user` - User (level 5)

#### users (用戶表)
```sql
CREATE TABLE `users` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(50),
  `last_name` VARCHAR(50),
  `avatar` VARCHAR(255),
  `phone` VARCHAR(20),
  `line_id` VARCHAR(50),
  `discord_id` VARCHAR(50),
  `status` ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'active',
  `email_verified_at` DATETIME,
  `last_login_at` DATETIME,
  `last_login_ip` VARCHAR(45),
  `failed_login_attempts` TINYINT(3) UNSIGNED DEFAULT 0,
  `locked_until` DATETIME,
  `password_reset_token` VARCHAR(255),
  `password_reset_expires` DATETIME,
  `email_verification_token` VARCHAR(255),
  `two_factor_secret` VARCHAR(100),
  `two_factor_enabled` BOOLEAN DEFAULT FALSE,
  `preferences` JSON,
  `metadata` JSON,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  `deleted_at` DATETIME,
  UNIQUE KEY (`username`),
  UNIQUE KEY (`email`)
);
```

#### user_roles (用戶角色關聯表)
```sql
CREATE TABLE `user_roles` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `role_id` INT(11) UNSIGNED NOT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `assigned_at` DATETIME DEFAULT NOW(),
  `assigned_by` INT(11) UNSIGNED,
  `expires_at` DATETIME,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  UNIQUE KEY (`user_id`, `role_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
);
```

#### user_sessions (用戶會話表)
```sql
CREATE TABLE `user_sessions` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `session_token` VARCHAR(64) NOT NULL,
  `refresh_token` VARCHAR(64) NOT NULL,
  `ip_address` VARCHAR(45),
  `user_agent` VARCHAR(255),
  `expires_at` DATETIME NOT NULL,
  `refresh_expires_at` DATETIME NOT NULL,
  `created_at` DATETIME,
  UNIQUE KEY (`session_token`),
  UNIQUE KEY (`refresh_token`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);
```

#### permissions (權限表)
```sql
CREATE TABLE `permissions` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `display_name` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `category` VARCHAR(50),
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` DATETIME,
  `updated_at` DATETIME
);
```

#### role_permissions (角色權限關聯表)
```sql
CREATE TABLE `role_permissions` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `role_id` INT(11) UNSIGNED NOT NULL,
  `permission_id` INT(11) UNSIGNED NOT NULL,
  `created_at` DATETIME,
  UNIQUE KEY (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
);
```

---

## 測試資料庫連線

### 方法 1: 使用測試腳本

#### 測試基本資料庫連線和登入驗證
```bash
curl http://localhost:9217/test-login.php
```

預期輸出:
```json
{
  "success": true,
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@test.com",
    "status": "active"
  },
  "password_valid": true,
  "roles": [
    {
      "name": "super_admin",
      "display_name": "Super Administrator"
    }
  ]
}
```

### 方法 2: 直接查詢資料庫

```bash
# 查看所有用戶
docker exec promotion_mysql mysql -uroot -proot_password promotion_platform \
  -e "SELECT u.id, u.username, u.email, r.name as role FROM users u
      LEFT JOIN user_roles ur ON u.id = ur.user_id
      LEFT JOIN roles r ON ur.role_id = r.id;"
```

### 方法 3: 使用 API 端點

```bash
# 測試登入 API
curl -X POST http://localhost:9217/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"password"}'
```

---

## 常見操作

### 新增用戶

```sql
-- 插入新用戶
INSERT INTO users (username, email, password_hash, first_name, last_name, status, created_at, updated_at)
VALUES ('newuser', 'newuser@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'New', 'User', 'active', NOW(), NOW());

-- 分配角色給用戶
INSERT INTO user_roles (user_id, role_id, created_at)
SELECT u.id, r.id, NOW()
FROM users u, roles r
WHERE u.username = 'newuser' AND r.name = 'user';
```

### 修改用戶密碼

```bash
# 使用 PHP 生成密碼雜湊
docker exec promotion_backend php -r "echo password_hash('newpassword', PASSWORD_DEFAULT) . PHP_EOL;"

# 更新資料庫
docker exec promotion_mysql mysql -uroot -proot_password promotion_platform \
  -e "UPDATE users SET password_hash = '生成的雜湊值' WHERE username = 'admin';"
```

### 查看用戶角色和權限

```sql
-- 查看用戶的所有角色
SELECT u.username, r.name as role_name, r.display_name, ur.assigned_at
FROM users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
WHERE u.username = 'admin';

-- 查看角色的所有權限
SELECT r.name as role_name, p.name as permission_name, p.display_name
FROM roles r
JOIN role_permissions rp ON r.id = rp.role_id
JOIN permissions p ON rp.permission_id = p.id
WHERE r.name = 'super_admin';
```

---

## 重置資料庫

如果需要完全重置資料庫：

```bash
# 方法 1: 重新執行設置腳本
docker exec -i promotion_mysql mysql -uroot -proot_password promotion_platform < backend/setup_database.sql

# 方法 2: 手動刪除並重建
docker exec promotion_mysql mysql -uroot -proot_password -e "
DROP DATABASE IF EXISTS promotion_platform;
CREATE DATABASE promotion_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
"

# 然後執行設置腳本
docker exec -i promotion_mysql mysql -uroot -proot_password promotion_platform < backend/setup_database.sql
```

---

## 資料庫備份與還原

### 備份資料庫

```bash
# 完整備份
docker exec promotion_mysql mysqldump -uroot -proot_password promotion_platform > backup_$(date +%Y%m%d_%H%M%S).sql

# 只備份結構
docker exec promotion_mysql mysqldump -uroot -proot_password --no-data promotion_platform > schema.sql

# 只備份資料
docker exec promotion_mysql mysqldump -uroot -proot_password --no-create-info promotion_platform > data.sql
```

### 還原資料庫

```bash
# 從備份還原
docker exec -i promotion_mysql mysql -uroot -proot_password promotion_platform < backup_20250930_123456.sql
```

---

## 故障排除

### 資料庫連線失敗

```bash
# 檢查 MySQL 容器是否運行
docker ps | grep mysql

# 檢查連線
docker exec promotion_backend php -r "
\$mysqli = new mysqli('mysql', 'promotion_user', 'promotion_password', 'promotion_platform');
echo \$mysqli->connect_error ? 'Error: ' . \$mysqli->connect_error : 'Connected successfully';
"
```

### 查看資料庫錯誤

```bash
# 查看 MySQL 錯誤日誌
docker logs promotion_mysql --tail 50

# 查看 CodeIgniter 錯誤日誌
docker exec promotion_backend cat /var/www/html/writable/logs/log-$(date +%Y-%m-%d).log
```

### 檢查表結構

```bash
# 列出所有表
docker exec promotion_mysql mysql -uroot -proot_password promotion_platform -e "SHOW TABLES;"

# 查看表結構
docker exec promotion_mysql mysql -uroot -proot_password promotion_platform -e "DESCRIBE users;"

# 查看表的索引
docker exec promotion_mysql mysql -uroot -proot_password promotion_platform -e "SHOW INDEX FROM users;"
```

---

## 安全建議

1. **變更預設密碼**: 生產環境中務必變更測試用戶的密碼
2. **使用強密碼**: 密碼應至少 12 個字元，包含大小寫字母、數字和特殊符號
3. **定期備份**: 設置自動備份排程
4. **限制資料庫存取**: 只允許必要的 IP 連線到資料庫
5. **啟用 SSL/TLS**: 生產環境中使用加密連線

---

## 相關文檔

- [API 文檔](./api-documentation.md)
- [用戶管理指南](./user-management.md)
- [權限系統說明](./permissions.md)
- [開發環境設置](../README.md)