# 本地開發環境設定指南

## 問題解決: CORS 跨域請求問題

本文檔說明如何解決 CORS 問題並正確設定本地開發環境，使其與生產環境兼容。

## 問題描述

當使用後台登入頁面時，會出現以下 CORS 錯誤：
```
已封鎖跨來源請求: 同源政策不允許讀取 https://promotion.mercylife.cc/api/auth/login 的遠端資源。
（原因: 缺少 CORS 'Access-Control-Allow-Origin' 檔頭）。狀態代碼: 404。
```

## 解決方案

### 1. 後端 CORS 配置更新

**檔案**: `backend/app/Config/App.php`

```php
// CORS configuration - Environment specific settings
public array $corsConfig = [
    'allowedOrigins' => [
        'http://localhost:3000',
        'http://localhost:3001',
        'http://localhost:9117',    // Admin template 開發端口
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        'http://127.0.0.1:9117',
        'https://promotion.mercylife.cc',
        'https://admin.promotion.mercylife.cc'
    ],
    'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH', 'HEAD'],
    'allowedHeaders' => [
        'Origin',
        'Content-Type',
        'Accept',
        'Authorization',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-API-KEY'
    ],
    'exposedHeaders' => [
        'Authorization',
        'X-Total-Count',
        'X-Page-Count'
    ],
    'maxAge' => 86400,
    'supportsCredentials' => true,
];
```

### 2. 前端環境配置

**檔案**: `admin_template/nuxt.config.ts`

```typescript
runtimeConfig: {
  public: {
    apiBase: process.env.API_BASE_URL || (
      process.env.NODE_ENV === 'development'
        ? 'http://localhost:9217/api'
        : 'https://promotion.mercylife.cc/api'
    )
  }
}
```

**檔案**: `admin_template/.env` (本地開發)

```env
# Local Development Environment Configuration
API_BASE_URL=http://localhost:9217/api
```

**檔案**: `admin_template/.env.production` (生產環境)

```env
# Production Environment Configuration
API_BASE_URL=https://promotion.mercylife.cc/api
```

### 3. Package.json 腳本更新

```json
{
  "scripts": {
    "dev": "nuxt dev --port 9117",
    "dev:local": "cross-env NODE_ENV=development API_BASE_URL=http://localhost:9217/api nuxt dev --port 9117",
    "dev:production": "cross-env NODE_ENV=production API_BASE_URL=https://promotion.mercylife.cc/api nuxt dev --port 9117",
    "build": "nuxt build",
    "build:production": "cross-env NODE_ENV=production API_BASE_URL=https://promotion.mercylife.cc/api nuxt build"
  }
}
```

## 開發環境啟動步驟

### 1. 啟動後端服務

```bash
# 進入後端目錄
cd backend

# 使用 Docker Compose 啟動服務 (推薦)
docker-compose up -d

# 或直接使用 PHP 開發伺服器
php spark serve --host 0.0.0.0 --port 9217
```

### 2. 啟動前端開發服務

```bash
# 進入前端目錄
cd admin_template

# 安裝依賴 (首次執行)
npm install

# 啟動本地開發模式
npm run dev:local

# 或測試生產環境模式
npm run dev:production
```

## 環境變數配置

### 本地開發模式
- 前端運行在: `http://localhost:9117`
- 後端運行在: `http://localhost:9217`
- API 基礎 URL: `http://localhost:9217/api`

### 生產環境模式
- 前端運行在: `https://admin.promotion.mercylife.cc`
- 後端運行在: `https://promotion.mercylife.cc`
- API 基礎 URL: `https://promotion.mercylife.cc/api`

## 故障排除

### 1. CORS 問題持續出現

檢查後端服務是否正常運行：
```bash
curl -i http://localhost:9217/api/health
```

### 2. 404 錯誤

確認後端路由配置正確：
```bash
# 檢查認證路由
curl -i -X POST http://localhost:9217/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

### 3. 環境變數未生效

清除 Nuxt 快取：
```bash
cd admin_template
rm -rf .nuxt node_modules/.cache
npm install
npm run dev:local
```

### 4. Docker 容器問題

重新建構 Docker 容器：
```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

## 部署注意事項

### 1. 生產環境部署

確保生產環境使用正確的環境變數：
```bash
# 設定生產環境變數
export API_BASE_URL=https://promotion.mercylife.cc/api
export NODE_ENV=production

# 建構生產版本
npm run build:production
```

### 2. Docker 部署

更新 Docker Compose 檔案以使用正確的環境變數：
```yaml
services:
  frontend:
    environment:
      - API_BASE_URL=https://promotion.mercylife.cc/api
      - NODE_ENV=production
```

## 安全性考量

1. **不要在生產環境使用 `allowedOrigins: ['*']`**
2. **確保 CORS 配置只包含信任的域名**
3. **在生產環境中啟用 HTTPS**
4. **定期更新依賴套件**

## 驗證設定

### 1. 檢查 CORS 設定

```bash
# 測試 CORS preflight 請求
curl -i -X OPTIONS http://localhost:9217/api/auth/login \
  -H "Origin: http://localhost:9117" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type,Authorization"
```

### 2. 測試 API 連接

```bash
# 測試健康檢查端點
curl -i http://localhost:9217/api/health

# 測試 CORS 測試端點
curl -i -X GET http://localhost:9217/api/cors-test \
  -H "Origin: http://localhost:9117"
```

成功設定後，應該能看到正確的 CORS 標頭返回，包括：
- `Access-Control-Allow-Origin`
- `Access-Control-Allow-Methods`
- `Access-Control-Allow-Headers`

## 常見問題

**Q: 為什麼我的 API 請求仍然被阻擋？**
A: 檢查瀏覽器開發者工具的網路標籤，確認請求是否真的發送到正確的 URL。

**Q: 如何在不同環境間切換？**
A: 使用不同的 npm 腳本：`npm run dev:local` 或 `npm run dev:production`

**Q: Docker 環境下如何配置？**
A: 確保 Docker 容器間的網路配置正確，並使用容器名稱作為主機名。