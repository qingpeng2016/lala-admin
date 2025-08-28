#!/bin/bash

# Telegram Bot 停止脚本
# 使用方法: ./stop_bot.sh

echo "🛑 停止 Telegram Bot..."

# 查找Bot进程
BOT_PIDS=$(pgrep -f telegram_bot_polling.php)

if [ -z "$BOT_PIDS" ]; then
    echo "ℹ️  Bot进程未运行"
    exit 0
fi

echo "📋 找到Bot进程: $BOT_PIDS"

# 优雅停止
echo "🔄 正在停止Bot进程..."
pkill -f telegram_bot_polling.php

# 等待进程停止
sleep 3

# 检查是否还有进程在运行
REMAINING_PIDS=$(pgrep -f telegram_bot_polling.php)

if [ -n "$REMAINING_PIDS" ]; then
    echo "⚠️  强制停止剩余进程: $REMAINING_PIDS"
    pkill -9 -f telegram_bot_polling.php
    sleep 1
fi

# 最终检查
FINAL_CHECK=$(pgrep -f telegram_bot_polling.php)

if [ -z "$FINAL_CHECK" ]; then
    echo "✅ Bot已成功停止"
else
    echo "❌ Bot停止失败，进程仍在运行: $FINAL_CHECK"
    exit 1
fi
