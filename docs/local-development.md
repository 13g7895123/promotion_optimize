# 本地開發指南

本指南說明如何在本地環境使用 `npm run dev` 進行前端開發。

---

## 前置需求

### 必要軟體
- **Node.js**: 版本 18.x 或更高
- **npm**: 版本 9.x 或更高
- **後端服務**: Backend API 必須已經啟動

### 檢查版本
```bash
node -v    # 應該顯示 v18.x 或更高
npm -v     # 應該顯示 9.x 或更高
```

---

## 快速開始

### 1. 安裝依賴

進入 frontend 目錄並安裝依賴：

```bash
cd frontend
npm install
```

### 2. 啟動開發服務器

前端提供多種開發模式：

#### 標準開發模式 (Port 3000)
```bash
npm run dev
```
- 使用預設配置
- 監聽 `http://localhost:3000`
- 適合基本開發

#### 本地開發模式 (Port 3304)
```bash
npm run dev:local
```
- 連接本地後端 API (`http://localhost:9217/api`)
- 監聽 `http://localhost:3304`
- **推薦用於本地完整開發**

#### 生產環境模式 (Port 3304)
```bash
npm run dev:production
```
- 連接生產環境 API (`https://promotion.mercylife.cc/api`)
- 監聽 `http://localhost:3304`
- 用於測試生產環境連接

---

## 開發模式對照表

| 指令 | 端口 | API 地址 | 使用場景 |
|------|------|---------|---------|
| `npm run dev` | 3000 | 預設 | 基本開發 |
| `npm run dev:local` | 3304 | `http://localhost:9217/api` | **本地完整開發** ⭐ |
| `npm run dev:production` | 3304 | `https://promotion.mercylife.cc/api` | 生產環境測試 |

---

## 推薦開發流程

### 完整本地開發環境

1. **啟動後端服務** (Docker)
```bash
# 在專案根目錄
docker compose up -d

# 確認後端 API 正常
curl http://localhost:9217/api/test
```

2. **啟動前端開發服務器**
```bash
# 進入 frontend 目錄
cd frontend

# 啟動開發服務器（推薦使用 dev:local）
npm run dev:local
```

3. **開始開發**
- 訪問 `http://localhost:3304`
- 修改代碼會自動熱重載
- 後端 API 連接到 `http://localhost:9217/api`

### 純前端開發

如果只需要開發前端 UI，不需要後端功能：

```bash
cd frontend
npm run dev
```

訪問 `http://localhost:3000` 進行前端開發。

---

## 開發特性

### 🔥 熱模塊替換 (HMR)
- 修改 `.vue`、`.js`、`.ts` 文件會自動刷新
- 保持應用狀態
- 無需手動刷新瀏覽器

### 🐛 開發工具
- **Vue DevTools**: 按 `Shift + Alt + D` 啟動
- 詳細的錯誤訊息
- 組件樹檢查

### ⚡ 快速編譯
- 使用 Vite 進行極速編譯
- 按需編譯，只編譯修改的文件

---

## 端口說明

### 前端端口
- **Port 3000**: `npm run dev` 預設端口
- **Port 3304**: `npm run dev:local` 和 `npm run dev:production` 使用端口

### 後端端口
- **Port 9217**: 開發環境後端 API
- **Port 8217**: 測試環境後端 API

### 避免端口衝突
如果端口被佔用，可以修改 `package.json` 中的端口：

```json
{
  "scripts": {
    "dev": "nuxt dev --port 3001",
    "dev:local": "cross-env NODE_ENV=development API_BASE_URL=http://localhost:9217/api nuxt dev --port 3305"
  }
}
```

---

## 常見問題

### Q1: npm install 失敗

**解決方案**:
```bash
# 清理快取
npm cache clean --force

# 刪除舊依賴
rm -rf node_modules package-lock.json

# 重新安裝
npm install
```

### Q2: 端口被佔用

**解決方案**:
```bash
# 查找佔用端口的進程 (Linux/Mac)
lsof -i :3000

# 或使用 netstat
netstat -tulpn | grep 3000

# 終止進程
kill -9 <PID>
```

### Q3: API 連接失敗

**檢查清單**:
1. 確認後端服務已啟動
   ```bash
   curl http://localhost:9217/api/test
   ```

2. 檢查 CORS 設定
   - 後端 `Cors.php` 應包含前端地址

3. 確認環境變數
   - `npm run dev:local` 使用 `http://localhost:9217/api`

### Q4: 熱重載不工作

**解決方案**:
```bash
# 停止開發服務器
# Ctrl+C

# 清理建置緩存
rm -rf .nuxt dist

# 重新啟動
npm run dev:local
```

### Q5: 模組找不到

**解決方案**:
```bash
# 重新生成類型定義
npm run postinstall

# 或
npx nuxt prepare
```

---

## 建置生產版本

### 開發完成後建置

```bash
# 標準建置
npm run build

# 生產環境建置
npm run build:production

# 預覽建置結果
npm run preview
```

### 建置輸出
- 建置文件位於 `.output` 目錄
- 靜態資源位於 `.output/public`
- 服務端代碼位於 `.output/server`

---

## VS Code 推薦設定

### 推薦擴充套件
- **Volar**: Vue 3 語法支援
- **ESLint**: 代碼檢查
- **Prettier**: 代碼格式化
- **Vue VSCode Snippets**: Vue 代碼片段

### `.vscode/settings.json`
```json
{
  "editor.formatOnSave": true,
  "editor.defaultFormatter": "esbenp.prettier-vscode",
  "[vue]": {
    "editor.defaultFormatter": "Vue.volar"
  },
  "files.associations": {
    "*.vue": "vue"
  }
}
```

---

## 效能優化建議

### 開發環境
1. **使用 dev:local**: 連接本地後端，減少網絡延遲
2. **熱重載**: 利用 HMR 快速看到變更
3. **僅載入需要的模組**: 按需導入減少建置時間

### 生產環境
1. **程式碼分割**: 自動進行路由層級的代碼分割
2. **Tree-shaking**: 移除未使用的代碼
3. **壓縮**: 自動壓縮 JavaScript 和 CSS

---

## 相關指令參考

```bash
# 安裝依賴
npm install

# 開發模式
npm run dev              # Port 3000
npm run dev:local        # Port 3304 + 本地 API
npm run dev:production   # Port 3304 + 生產 API

# 建置
npm run build           # 標準建置
npm run build:production # 生產環境建置

# 其他
npm run generate        # 生成靜態網站
npm run preview         # 預覽建置結果
npm run lint            # 代碼檢查
npm run typecheck       # TypeScript 類型檢查
```

---

## 開發最佳實踐

1. **使用 dev:local**: 推薦使用 `npm run dev:local` 進行完整開發
2. **即時預覽**: 利用熱重載查看即時變更
3. **檢查類型**: 定期執行 `npm run typecheck` 檢查類型錯誤
4. **保持依賴更新**: 定期更新 npm 依賴保持安全
5. **使用 DevTools**: 善用 Vue DevTools 進行除錯

---

## 獲取幫助

遇到問題時：
1. 查看控制台錯誤訊息
2. 檢查網絡請求 (瀏覽器 DevTools)
3. 查看後端日誌 (`docker compose logs backend`)
4. 參考 [Nuxt 官方文檔](https://nuxt.com)
5. 查看專案 [CLAUDE.md](../CLAUDE.md) 開發規範