# 私人遊戲伺服器推廣平台

一個多服主共用的遊戲伺服器推廣平台，整合推廣獎勵管理、活動系統及完整的伺服器管理功能。

## 快速開始

### 開發環境

1. **克隆專案**
   ```bash
   git clone <repository-url>
   cd promotion
   ```

2. **環境配置**
   ```bash
   cp .env.example .env
   # 編輯 .env 文件設置必要參數
   ```

3. **啟動開發環境**
   ```bash
   docker-compose up -d
   ```

4. **訪問應用**
   - 前端: http://localhost:9017
   - 後端 API: http://localhost:9017/api
   - phpMyAdmin: http://localhost:9517

### 生產環境部署

1. **使用自動部署腳本**
   ```bash
   ./deploy.sh
   ```

2. **手動部署**
   ```bash
   # 拉取最新代碼
   git pull origin master
   
   # 使用生產配置
   docker-compose -f docker-compose.prod.yml up -d
   
   # 運行資料庫遷移
   docker-compose exec backend php spark migrate
   ```

## 技術架構

- **前端**: Nuxt.js 3.x + TypeScript + TailwindCSS
- **後端**: CodeIgniter 4.x + PHP 8.1+
- **資料庫**: MySQL 8.0+
- **快取**: Redis 7
- **容器化**: Docker + Docker Compose
- **反向代理**: Nginx

## CI/CD 流程

### GitHub Actions

- **CI 流程** (`.github/workflows/ci.yml`)
  - 程式碼檢查和測試
  - 安全掃描
  - Docker 映像建置測試

- **CD 流程** (`.github/workflows/deploy.yml`)
  - 自動部署到生產環境
  - 健康檢查
  - 部署狀態通知

### 部署策略

1. **藍綠部署**: 使用 `docker-compose.prod.yml` 配置
2. **滾動更新**: 逐步更新容器實例
3. **回滾機制**: 快速回到穩定版本

## 監控與日誌

### 監控工具
- **Prometheus**: 指標收集
- **Grafana**: 視覺化監控面板
- **Docker 日誌**: 容器日誌管理

### 健康檢查端點
- Frontend: `GET /`
- Backend: `GET /api/health`
- Database: 透過 CodeIgniter 連接檢查

## 安全配置

### 生產環境安全檢查清單

- [ ] 更改默認密碼和密鑰
- [ ] 啟用 HTTPS (SSL/TLS)
- [ ] 配置防火牆規則
- [ ] 設置備份策略
- [ ] 配置監控告警
- [ ] 定期安全更新

### 環境變數

關鍵環境變數必須在生產環境中設置：
- `JWT_SECRET`: JWT 簽名密鑰
- `DB_ROOT_PASSWORD`: 資料庫 root 密碼
- `DB_PASSWORD`: 應用資料庫密碼
- `REDIS_PASSWORD`: Redis 密碼

## 備份與還原

### 自動備份
部署腳本會自動建立備份：
```bash
backup/YYYYMMDD_HHMMSS/
├── database.sql
└── storage/
```

### 手動備份
```bash
# 資料庫備份
docker exec promotion_mysql mysqldump -u root -p promotion_platform > backup.sql

# 文件備份
cp -r backend/storage backup/
```

### 還原
```bash
# 還原資料庫
docker exec -i promotion_mysql mysql -u root -p promotion_platform < backup.sql

# 還原文件
cp -r backup/storage backend/
```

## 故障排除

### 常見問題

1. **容器啟動失敗**
   ```bash
   docker-compose logs [service-name]
   ```

2. **資料庫連接問題**
   - 檢查 `.env` 配置
   - 確認 MySQL 容器狀態
   - 驗證網路連接

3. **權限問題**
   ```bash
   # 修復文件權限
   sudo chown -R www-data:www-data backend/storage
   sudo chmod -R 755 backend/storage
   ```

## 貢獻指南

1. Fork 專案
2. 建立功能分支 (`git checkout -b feature/amazing-feature`)
3. 提交更改 (`git commit -m 'Add amazing feature'`)
4. 推送到分支 (`git push origin feature/amazing-feature`)
5. 開啟 Pull Request

## 授權

本專案採用 MIT 授權 - 詳見 [LICENSE](LICENSE) 文件。

## 聯絡資訊

- 專案維護者: [Your Name]
- 問題回報: [GitHub Issues](https://github.com/your-repo/issues)
- 郵件: your-email@example.com