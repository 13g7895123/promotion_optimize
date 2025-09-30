# 環境配置總覽

## 專案環境架構

本專案支援三種獨立環境：**開發環境**、**測試環境** 和 **生產環境**。

---

## 環境對照表

| 項目 | 開發環境 | 測試環境 | 生產環境 |
|------|---------|---------|---------|
| **配置文件** | `.env` | `.env.test` | `.env.production` |
| **Docker Compose** | `docker-compose.yml` | `docker-compose.test.yml` | `docker-compose.prod.yml` |
| **Frontend Port** | 9117 | 8117 | 9117 |
| **Backend Port** | 9217 | 8217 | 9217 |
| **MySQL Port** | 9317 | 8317 | 9317 |
| **Redis Port** | 6379 | 8417 | 9417 |
| **PHPMyAdmin Port** | 9517 | 8517 | 9517 |
| **資料庫名稱** | `promotion_platform` | `promotion_platform_test` | `promotion_platform` |
| **APP_ENV** | `development` | `testing` | `production` |
| **APP_DEBUG** | `true` | `true` | `false` |

---

## 快速啟動指令

### 開發環境
```bash
# 啟動
docker compose up -d

# 停止
docker compose down

# 查看日誌
docker compose logs -f
```

### 測試環境
```bash
# 啟動
./test.sh

# 停止
./test-stop.sh

# 或使用 Docker Compose
docker compose -f docker-compose.test.yml --env-file .env.test up -d
docker compose -f docker-compose.test.yml down
```

### 生產環境
```bash
# 啟動
docker compose -f docker-compose.prod.yml up -d

# 停止
docker compose -f docker-compose.prod.yml down
```

---

## 環境隔離設計

### 容器隔離
每個環境使用不同的容器名稱：
- **開發環境**: `promotion_*`
- **測試環境**: `promotion_*_test`
- **生產環境**: `promotion_*` (但使用不同的 compose 文件)

### 網絡隔離
每個環境使用獨立的 Docker 網絡：
- **開發環境**: `promotion_network`
- **測試環境**: `promotion_test_network`
- **生產環境**: `promotion_network`

### 資料隔離
每個環境使用獨立的 Volume：
- **開發環境**: `mysql_data`, `redis_data`
- **測試環境**: `mysql_test_data`, `redis_test_data`
- **生產環境**: 獨立 Volume 配置

---

## 同時運行多環境

開發環境和測試環境可以同時運行：

```bash
# 1. 啟動開發環境
docker compose up -d

# 2. 啟動測試環境
./test.sh

# 3. 查看所有運行的容器
docker ps | grep promotion

# 4. 測試兩個環境的 API
curl http://localhost:9217/api/test  # 開發環境
curl http://localhost:8217/api/test  # 測試環境
```

---

## API 端點

### 開發環境
- Frontend: http://localhost:9117
- Backend API: http://localhost:9217/api
- PHPMyAdmin: http://localhost:9517

### 測試環境
- Frontend: http://localhost:8117
- Backend API: http://localhost:8217/api
- PHPMyAdmin: http://localhost:8517

---

## 環境變數說明

### 共用變數
- `FRONTEND_PORT`: 前端服務端口
- `BACKEND_PORT`: 後端 API 端口
- `MYSQL_PORT`: MySQL 資料庫端口
- `REDIS_PORT`: Redis 快取端口
- `PHPMYADMIN_PORT`: PHPMyAdmin 管理介面端口

### 資料庫變數
- `DB_HOST`: 資料庫主機名稱（容器名稱）
- `DB_NAME`: 資料庫名稱
- `DB_USER`: 資料庫使用者
- `DB_PASSWORD`: 資料庫密碼
- `DB_ROOT_PASSWORD`: 資料庫 root 密碼

### Redis 變數
- `REDIS_HOST`: Redis 主機名稱（容器名稱）
- `REDIS_PASSWORD`: Redis 密碼

### 應用程式變數
- `APP_ENV`: 應用程式環境 (development/testing/production)
- `APP_DEBUG`: 除錯模式開關
- `JWT_SECRET`: JWT Token 加密金鑰

---

## 最佳實踐

1. **開發時使用開發環境**
   - 即時熱重載
   - 詳細錯誤訊息
   - 除錯工具啟用

2. **功能測試使用測試環境**
   - 獨立的資料庫避免汙染開發資料
   - 可重複測試
   - 模擬生產環境配置

3. **部署前在生產環境測試**
   - 關閉除錯模式
   - 啟用快取
   - 驗證安全性配置

4. **環境隔離**
   - 各環境使用不同端口避免衝突
   - 資料庫完全隔離
   - 獨立的 Docker 網絡

---

## 故障排除

### 端口衝突
```bash
# 檢查端口佔用
sudo lsof -i :9217

# 或使用 netstat
netstat -tulpn | grep 9217
```

### 容器無法啟動
```bash
# 查看容器狀態
docker compose ps

# 查看錯誤日誌
docker compose logs [service_name]

# 重新建置
docker compose build --no-cache
```

### 資料庫連線失敗
```bash
# 檢查資料庫容器
docker logs promotion_mysql

# 進入容器測試
docker exec -it promotion_mysql mysql -u promotion_user -p
```

---

## 相關文件

- [測試環境詳細說明](../TEST_ENVIRONMENT.md)
- [專案README](../README.md)
- [CLAUDE開發指南](../CLAUDE.md)