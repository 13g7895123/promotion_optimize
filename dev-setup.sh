#!/bin/bash

# 私人遊戲伺服器推廣平台 - 開發環境部署腳本

echo "🚀 開始部署私人遊戲伺服器推廣平台開發環境..."

# 檢查Docker是否安裝
if ! command -v docker &> /dev/null; then
    echo "❌ Docker未安裝，請先安裝Docker"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose未安裝，請先安裝Docker Compose"
    exit 1
fi

# 檢查.env文件是否存在
if [ ! -f .env ]; then
    echo "❌ .env文件不存在，請先創建.env文件"
    exit 1
fi

echo "✅ 環境檢查完成"

# 停止並移除現有容器
echo "🛑 停止現有容器..."
docker-compose down

# 移除舊的images（可選）
read -p "是否要移除舊的Docker images？(y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "🗑️ 移除舊的Docker images..."
    docker-compose down --rmi all
fi

# 建立Docker images
echo "🔨 建立Docker images..."
docker-compose build --no-cache

# 啟動服務
echo "🚀 啟動服務..."
docker-compose up -d

# 等待服務啟動
echo "⏳ 等待服務啟動..."
sleep 10

# 檢查服務狀態
echo "📊 檢查服務狀態..."
docker-compose ps

# 等待MySQL啟動完成
echo "⏳ 等待MySQL服務完全啟動..."
while ! docker-compose exec mysql mysqladmin ping -h"mysql" -uroot -p"$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2)" --silent; do
    echo "等待MySQL..."
    sleep 2
done

echo "✅ MySQL服務已啟動"

# 檢查是否需要運行migrations
echo "🔄 檢查資料庫migration..."
docker-compose exec backend php spark migrate

# 檢查是否需要運行seeders
read -p "是否要運行資料庫seeders？(y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "🌱 運行資料庫seeders..."
    docker-compose exec backend php spark db:seed
fi

# 安裝前端依賴
echo "📦 安裝前端依賴..."
docker-compose exec frontend npm install

# 顯示訪問信息
echo ""
echo "🎉 部署完成！"
echo ""
echo "📍 服務訪問地址："
echo "   前端應用: http://localhost:$(grep FRONTEND_PORT .env | cut -d '=' -f2)"
echo "   後端API:  http://localhost:$(grep BACKEND_PORT .env | cut -d '=' -f2)"
echo "   phpMyAdmin: http://localhost:$(grep PHPMYADMIN_PORT .env | cut -d '=' -f2)"
echo ""
echo "📝 資料庫連接信息："
echo "   主機: localhost:$(grep DB_PORT .env | cut -d '=' -f2)"
echo "   資料庫: $(grep DB_NAME .env | cut -d '=' -f2)"
echo "   用戶: $(grep DB_USER .env | cut -d '=' -f2)"
echo ""
echo "🔧 常用命令："
echo "   查看日誌: docker-compose logs -f [service_name]"
echo "   進入容器: docker-compose exec [service_name] bash"
echo "   停止服務: docker-compose down"
echo "   重啟服務: docker-compose restart"
echo ""
echo "✨ 開發環境已準備就緒！"