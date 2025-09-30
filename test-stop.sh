#!/bin/bash

# 測試環境停止腳本

echo "=========================================="
echo "停止測試環境"
echo "=========================================="

# 停止並移除容器
docker compose -f docker-compose.test.yml down

echo ""
echo "✓ 測試環境已停止"
echo ""
echo "如需完全清理（包含資料庫數據）："
echo "  docker compose -f docker-compose.test.yml down -v"
echo ""