#!/bin/bash

# Telegram Bot 启动脚本
# 使用方法: ./start_bot.sh

echo "🤖 启动 Telegram Bot..."

# 检查PHP是否安装
if ! command -v php &> /dev/null; then
    echo "❌ PHP未安装，请先安装PHP"
    exit 1
fi

# 检查配置文件
if [ ! -f "telegram_bot_polling.php" ]; then
    echo "❌ telegram_bot_polling.php 文件不存在"
    exit 1
fi

# 创建日志目录
mkdir -p logs

# 启动Bot
echo "✅ 开始启动Bot..."
echo "📝 日志文件: logs/bot.log"
echo "🛑 停止命令: pkill -f telegram_bot_polling.php"
echo ""

# 后台运行Bot
nohup php telegram_bot_polling.php > logs/bot.log 2>&1 &

# 获取进程ID
BOT_PID=$!
echo "Bot进程ID: $BOT_PID"

# 等待几秒检查是否启动成功
sleep 3

# 检查进程是否还在运行
if ps -p $BOT_PID > /dev/null; then
    echo "✅ Bot启动成功！"
    echo "📊 查看日志: tail -f logs/bot.log"
    echo "🔄 重启Bot: ./restart_bot.sh"
else
    echo "❌ Bot启动失败，请检查日志文件"
    echo "📝 查看错误: cat logs/bot.log"
fi
