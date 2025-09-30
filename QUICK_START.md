# 快速開始指南

快速啟動專案的三種方式。

---

## 🐳 方式一：完整 Docker 環境 (最簡單)

**適用場景**: 完整開發、測試、演示

```bash
# 1. 啟動所有服務
docker compose up -d

# 2. 訪問應用
# 前端: http://localhost:9117
# 後端: http://localhost:9217/api
# PHPMyAdmin: http://localhost:9517
```

**優點**:
- ✅ 一鍵啟動，無需配置
- ✅ 環境一致，避免「在我機器上可以運行」問題
- ✅ 包含所有服務（前端、後端、資料庫、Redis）

---

## 💻 方式二：本地前端開發 (推薦給前端開發者)

**適用場景**: 前端開發、快速熱重載

```bash
# 1. 啟動後端服務
docker compose up -d backend mysql redis

# 2. 進入前端目錄
cd frontend

# 3. 安裝依賴 (首次)
npm install

# 4. 啟動開發服務器
npm run dev:local
```

**訪問**: http://localhost:3304

**優點**:
- ✅ 極速熱重載 (HMR)
- ✅ 即時看到代碼變更
- ✅ 使用本地 Node.js 環境
- ✅ Vue DevTools 支援

**可用的開發模式**:
```bash
npm run dev              # Port 3000, 預設配置
npm run dev:local        # Port 3304, 連接本地後端 ⭐
npm run dev:production   # Port 3304, 連接生產環境
```

📖 [完整說明](docs/local-development.md)

---

## 🧪 方式三：測試環境 (獨立端口)

**適用場景**: 功能測試、與開發環境同時運行

```bash
# 啟動測試環境
./test.sh

# 訪問測試環境
# 前端: http://localhost:8117
# 後端: http://localhost:8217/api
# PHPMyAdmin: http://localhost:8517

# 停止測試環境
./test-stop.sh
```

**優點**:
- ✅ 獨立端口，不干擾開發環境
- ✅ 可與開發環境同時運行
- ✅ 獨立資料庫，測試數據隔離

📖 [完整說明](TEST_ENVIRONMENT.md)

---

## 📊 環境對照表

| 項目 | Docker 環境 | 本地開發 | 測試環境 |
|------|------------|---------|---------|
| **啟動指令** | `docker compose up -d` | `npm run dev:local` | `./test.sh` |
| **前端端口** | 9117 | 3304 | 8117 |
| **後端端口** | 9217 | 9217 | 8217 |
| **熱重載** | ✅ (較慢) | ✅ (極快) | ✅ (較慢) |
| **資源消耗** | 高 | 中 | 高 |
| **使用場景** | 完整開發 | 前端開發 | 功能測試 |

---

## 🔧 常用指令

### Docker 環境
```bash
# 啟動
docker compose up -d

# 停止
docker compose down

# 查看日誌
docker compose logs -f

# 重啟服務
docker compose restart backend
docker compose restart frontend

# 查看容器狀態
docker compose ps
```

### 本地開發
```bash
# 啟動 (推薦)
npm run dev:local

# 建置
npm run build

# 型別檢查
npm run typecheck

# 預覽建置
npm run preview
```

### 測試環境
```bash
# 啟動
./test.sh

# 停止
./test-stop.sh

# 完全清理
docker compose -f docker-compose.test.yml down -v
```

---

## 🚀 首次設置

### 1. 檢查環境

```bash
# Node.js 版本 (需要 18+)
node -v

# Docker 版本
docker --version
docker compose version

# 檢查端口佔用
lsof -i :9117  # 前端
lsof -i :9217  # 後端
```

### 2. 配置環境變數

```bash
# 複製範例文件
cp .env.example .env

# 根據需要編輯 .env
vim .env
```

### 3. 安裝依賴

```bash
# 前端依賴
cd frontend
npm install
cd ..

# 後端依賴已在 Docker 容器中安裝
```

---

## 🐛 常見問題快速解決

### 端口被佔用
```bash
# 查找佔用的進程
sudo lsof -i :9217

# 終止進程
kill -9 <PID>
```

### Docker 容器啟動失敗
```bash
# 查看錯誤日誌
docker compose logs backend

# 重新建置
docker compose build --no-cache
docker compose up -d
```

### npm install 失敗
```bash
# 清理並重新安裝
cd frontend
rm -rf node_modules package-lock.json
npm cache clean --force
npm install
```

### API 連接失敗
```bash
# 測試後端
curl http://localhost:9217/api/test

# 檢查後端日誌
docker compose logs backend -f

# 重啟後端
docker compose restart backend
```

### 前端熱重載不工作
```bash
# 清理建置緩存
cd frontend
rm -rf .nuxt dist
npm run dev:local
```

---

## 📖 延伸閱讀

- [完整 README](README.md)
- [本地開發指南](docs/local-development.md)
- [測試環境說明](TEST_ENVIRONMENT.md)
- [環境配置總覽](docs/environment-summary.md)
- [開發規範](CLAUDE.md)

---

## 💡 開發建議

### 前端開發者
使用 **方式二 (npm run dev:local)**
- 快速熱重載
- 即時看到變更
- 最佳開發體驗

### 全端開發者
使用 **方式一 (Docker)** 或 **方式二**
- Docker: 完整環境，包含資料庫
- 本地: 專注前端，使用 Docker 後端

### 測試人員
使用 **方式三 (測試環境)**
- 獨立測試數據
- 不影響開發環境
- 可同時測試多個版本

---

## ✨ 推薦工作流程

### 日常開發
```bash
# 1. 啟動後端
docker compose up -d backend mysql redis

# 2. 啟動前端 (本地)
cd frontend
npm run dev:local

# 3. 開始開發
# 訪問 http://localhost:3304
```

### 功能測試
```bash
# 1. 啟動測試環境
./test.sh

# 2. 測試功能
# 訪問 http://localhost:8117

# 3. 測試完成後停止
./test-stop.sh
```

### 部署前檢查
```bash
# 1. 建置生產版本
cd frontend
npm run build:production

# 2. 啟動 Docker 生產環境
docker compose -f docker-compose.prod.yml up -d

# 3. 測試生產環境
curl http://localhost:9217/api/test
```

---

開始享受開發吧！🎉