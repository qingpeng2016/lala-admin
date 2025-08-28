#!/bin/bash

# Telegram Bot 重启脚本
# 使用方法: ./restart_bot.sh

echo "🔄 重启 Telegram Bot..."

# 停止现有Bot进程
echo "🛑 停止现有Bot进程..."
pkill -f telegram_bot_polling.php

# 等待进程完全停止
sleep 2

# 检查是否还有Bot进程在运行
if pgrep -f telegram_bot_polling.php > /dev/null; then
    echo "⚠️  强制停止Bot进程..."
    pkill -9 -f telegram_bot_polling.php
    sleep 1
fi

# 启动Bot
echo "🚀 启动Bot..."
./start_bot.sh
