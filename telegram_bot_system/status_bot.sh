#!/bin/bash

# Telegram Bot 状态检查脚本
# 使用方法: ./status_bot.sh

echo "📊 Telegram Bot 状态检查"
echo "=========================="

# 检查Bot进程
BOT_PIDS=$(pgrep -f telegram_bot_polling.php)

if [ -z "$BOT_PIDS" ]; then
    echo "❌ Bot状态: 未运行"
    echo "💡 启动Bot: ./start_bot.sh"
    exit 1
else
    echo "✅ Bot状态: 运行中"
    echo "📋 进程ID: $BOT_PIDS"
fi

# 检查日志文件
if [ -f "logs/bot.log" ]; then
    echo "📝 日志文件: logs/bot.log"
    echo "📊 日志大小: $(du -h logs/bot.log | cut -f1)"
    echo "🕐 最后更新: $(stat -c %y logs/bot.log | cut -d' ' -f1,2)"
    
    # 显示最近的日志
    echo ""
    echo "📋 最近日志 (最后10行):"
    echo "------------------------"
    tail -10 logs/bot.log
else
    echo "⚠️  日志文件不存在"
fi

# 检查数据库连接
echo ""
echo "🗄️  数据库状态:"
if command -v mysql &> /dev/null; then
    # 这里可以添加数据库连接检查
    echo "✅ MySQL客户端可用"
else
    echo "⚠️  MySQL客户端未安装"
fi

echo ""
echo "🔧 管理命令:"
echo "  启动: ./start_bot.sh"
echo "  停止: ./stop_bot.sh"
echo "  重启: ./restart_bot.sh"
echo "  状态: ./status_bot.sh"
echo "  日志: tail -f logs/bot.log"
