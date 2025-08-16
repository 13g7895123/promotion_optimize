#!/bin/bash

# 私人遊戲伺服器推廣平台部署腳本
# Deployment script for Promotion Platform

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 日誌函數
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 檢查必要依賴
check_dependencies() {
    log_info "檢查必要依賴..."
    
    if ! command -v docker &> /dev/null; then
        log_error "Docker 未安裝"
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose 未安裝"
        exit 1
    fi
    
    log_success "依賴檢查完成"
}

# 備份現有資料
backup_data() {
    log_info "備份現有資料..."
    
    BACKUP_DIR="backup/$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    
    # 備份資料庫
    if docker ps | grep -q promotion_mysql; then
        log_info "備份資料庫..."
        docker exec promotion_mysql mysqldump -u root -p$DB_ROOT_PASSWORD promotion_platform > "$BACKUP_DIR/database.sql"
        log_success "資料庫備份完成"
    fi
    
    # 備份文件
    if [ -d "backend/storage" ]; then
        cp -r backend/storage "$BACKUP_DIR/"
        log_success "文件備份完成"
    fi
    
    log_success "備份完成: $BACKUP_DIR"
}

# 拉取最新代碼
pull_code() {
    log_info "拉取最新代碼..."
    
    if [ -d ".git" ]; then
        git fetch origin
        git reset --hard origin/master
        log_success "代碼更新完成"
    else
        log_warning "非 Git 倉庫，跳過代碼拉取"
    fi
}

# 建置應用
build_app() {
    log_info "建置應用..."
    
    # 停止舊容器
    log_info "停止現有容器..."
    docker-compose down
    
    # 清理舊映像（可選）
    if [ "$1" = "--clean" ]; then
        log_info "清理舊映像..."
        docker system prune -f
    fi
    
    # 建置新映像
    log_info "建置新映像..."
    docker-compose build --no-cache
    
    log_success "應用建置完成"
}

# 啟動服務
start_services() {
    log_info "啟動服務..."
    
    # 啟動容器
    docker-compose up -d
    
    # 等待服務啟動
    log_info "等待服務啟動..."
    sleep 30
    
    # 運行資料庫遷移
    log_info "運行資料庫遷移..."
    docker-compose exec -T backend php spark migrate
    
    log_success "服務啟動完成"
}

# 健康檢查
health_check() {
    log_info "執行健康檢查..."
    
    # 檢查前端
    if curl -f http://localhost:${NGINX_PORT:-9017} > /dev/null 2>&1; then
        log_success "前端服務正常"
    else
        log_error "前端服務異常"
        return 1
    fi
    
    # 檢查後端 API
    if curl -f http://localhost:${NGINX_PORT:-9017}/api/health > /dev/null 2>&1; then
        log_success "後端 API 正常"
    else
        log_error "後端 API 異常"
        return 1
    fi
    
    # 檢查資料庫連接
    if docker-compose exec -T backend php spark list > /dev/null 2>&1; then
        log_success "資料庫連接正常"
    else
        log_error "資料庫連接異常"
        return 1
    fi
    
    log_success "健康檢查通過"
}

# 回滾函數
rollback() {
    log_warning "開始回滾..."
    
    # 停止當前服務
    docker-compose down
    
    # 恢復之前的版本（需要實作版本標記）
    git reset --hard HEAD~1
    
    # 重新建置和啟動
    docker-compose build
    docker-compose up -d
    
    log_success "回滾完成"
}

# 主要部署流程
main() {
    log_info "開始部署私人遊戲伺服器推廣平台..."
    
    # 載入環境變數
    if [ -f ".env" ]; then
        source .env
    else
        log_error ".env 文件不存在"
        exit 1
    fi
    
    # 檢查依賴
    check_dependencies
    
    # 備份資料
    backup_data
    
    # 拉取代碼
    pull_code
    
    # 建置應用
    build_app "$1"
    
    # 啟動服務
    start_services
    
    # 健康檢查
    if health_check; then
        log_success "部署完成！"
        log_info "前端訪問地址: http://localhost:${NGINX_PORT:-9017}"
        log_info "後端 API 地址: http://localhost:${NGINX_PORT:-9017}/api"
        log_info "phpMyAdmin 地址: http://localhost:${PHPMYADMIN_PORT:-9517}"
    else
        log_error "健康檢查失敗，考慮回滾"
        exit 1
    fi
}

# 腳本使用說明
usage() {
    echo "用法: $0 [選項]"
    echo "選項:"
    echo "  --clean    清理舊映像後建置"
    echo "  --rollback 回滾到上一個版本"
    echo "  --help     顯示此幫助信息"
}

# 處理命令行參數
case "$1" in
    --help)
        usage
        ;;
    --rollback)
        rollback
        ;;
    --clean)
        main --clean
        ;;
    *)
        main
        ;;
esac