# 測試環境說明

本專案提供獨立的測試環境配置，使用不同的 PORT 避免與開發環境衝突。

## 端口配置

### 開發環境 (使用 `.env`)
- Frontend: `http://localhost:9117`
- Backend: `http://localhost:9217`
- MySQL: `localhost:9317`
- Redis: `localhost:6379`
- PHPMyAdmin: `http://localhost:9517`

### 測試環境 (使用 `.env.test`)
- Frontend: `http://localhost:8117`
- Backend: `http://localhost:8217`
- MySQL: `localhost:8317`
- Redis: `localhost:8417`
- PHPMyAdmin: `http://localhost:8517`

## 啟動測試環境

### 方法 1: 使用腳本 (推薦)

```bash
# 啟動測試環境
./test.sh

# 停止測試環境
./test-stop.sh
```

### 方法 2: 使用 Docker Compose

```bash
# 啟動測試環境
docker compose -f docker-compose.test.yml --env-file .env.test up -d

# 查看容器狀態
docker compose -f docker-compose.test.yml ps

# 查看日誌
docker compose -f docker-compose.test.yml logs -f

# 停止測試環境
docker compose -f docker-compose.test.yml down

# 停止並刪除所有數據
docker compose -f docker-compose.test.yml down -v
```

## 測試 API 連線

```bash
# 測試後端 API
curl http://localhost:8217/api/test

# 預期輸出
{
    "success": true,
    "message": "API 連接正常",
    "data": {
        "server_time": "2025-09-30 13:54:36",
        "environment": "development",
        "version": "1.0.0"
    }
}
```

## 測試環境說明

### 資料庫隔離
測試環境使用獨立的 MySQL 容器和資料庫：
- 容器名稱: `promotion_mysql_test`
- 資料庫名稱: `promotion_platform_test`
- 使用獨立的 volume: `mysql_test_data`

### Redis 隔離
測試環境使用獨立的 Redis 容器：
- 容器名稱: `promotion_redis_test`
- 使用獨立的 volume: `redis_test_data`

### 網絡隔離
測試環境使用獨立的 Docker 網絡：
- 網絡名稱: `promotion_test_network`
- 與開發環境 `promotion_network` 完全隔離

## 同時運行開發與測試環境

開發環境和測試環境可以同時運行，互不干擾：

```bash
# 運行開發環境
docker compose up -d

# 同時運行測試環境
./test.sh

# 查看所有容器
docker ps | grep promotion
```

## 清理測試環境

```bash
# 停止並移除容器
docker compose -f docker-compose.test.yml down

# 完全清理（包含數據卷）
docker compose -f docker-compose.test.yml down -v

# 清理未使用的 Docker 資源
docker system prune
```

## 注意事項

1. **端口衝突**: 確保測試環境的端口 (8xxx) 沒有被其他服務佔用
2. **資源消耗**: 同時運行兩套環境會消耗較多系統資源
3. **資料庫**: 測試環境使用獨立資料庫，測試數據不會影響開發環境
4. **配置文件**: `.env.test` 包含測試環境專用的配置，請勿提交敏感資訊到版本控制

## 故障排除

### 端口被佔用
```bash
# 檢查端口使用情況
sudo lsof -i :8217

# 或使用 netstat
netstat -tulpn | grep 8217
```

### 容器無法啟動
```bash
# 查看錯誤日誌
docker compose -f docker-compose.test.yml logs

# 重新建置映像
docker compose -f docker-compose.test.yml build --no-cache
```

### 資料庫連線失敗
```bash
# 檢查 MySQL 容器狀態
docker logs promotion_mysql_test

# 測試資料庫連線
docker exec promotion_mysql_test mysql -u promotion_user_test -p promotion_platform_test
```