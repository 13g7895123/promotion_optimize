#!/bin/bash

# 測試環境啟動腳本
# 使用獨立的 PORT 配置，避免與開發環境衝突

echo "=========================================="
echo "啟動測試環境"
echo "=========================================="

# 載入測試環境變數
if [ -f .env.test ]; then
    export $(cat .env.test | grep -v '^#' | xargs)
    echo "✓ 載入測試環境變數"
else
    echo "✗ 找不到 .env.test 文件"
    exit 1
fi

# 顯示測試環境端口
echo ""
echo "測試環境端口配置："
echo "  Frontend:   http://localhost:$FRONTEND_PORT"
echo "  Backend:    http://localhost:$BACKEND_PORT"
echo "  MySQL:      localhost:$MYSQL_PORT"
echo "  Redis:      localhost:$REDIS_PORT"
echo "  PHPMyAdmin: http://localhost:$PHPMYADMIN_PORT"
echo ""

# 啟動測試環境
echo "啟動 Docker 容器..."
docker compose -f docker-compose.test.yml --env-file .env.test up -d

# 檢查容器狀態
echo ""
echo "等待容器啟動..."
sleep 5

echo ""
echo "容器狀態："
docker compose -f docker-compose.test.yml ps

echo ""
echo "=========================================="
echo "測試環境已啟動完成"
echo "=========================================="
echo ""
echo "快速測試指令："
echo "  curl http://localhost:$BACKEND_PORT/api/test"
echo ""
echo "停止測試環境："
echo "  ./test-stop.sh"
echo "  或: docker compose -f docker-compose.test.yml down"
echo ""