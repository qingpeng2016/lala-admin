# Telegram Bot 广告埋点系统

## 🎯 功能特点

- **人类账号发广告**: 保持真实感和会员特权效果
- **Bot自动响应**: 检测广告消息并发送按钮
- **精准埋点**: 记录用户点击行为和用户ID
- **数据统计**: 完整的用户行为分析后台

## 📋 系统架构

```
人类账号发广告 → Bot检测 → 发送按钮 → 用户点击 → 记录行为 → 返回链接
```

## 🚀 快速部署

### 1. 创建Bot
1. 在Telegram中搜索 `@BotFather`
2. 发送 `/newbot` 创建新机器人
3. 获取Bot Token

### 2. 配置数据库
```sql
-- 执行 database.sql 中的SQL语句
mysql -u root -p < database.sql
```

### 3. 配置Bot
编辑 `telegram_bot.php` 中的配置：
```php
$bot_config = [
    'bot_token' => 'YOUR_BOT_TOKEN_HERE', // 替换为你的Bot Token
    'database' => [
        'host' => 'localhost',
        'database' => 'telegram_bot',
        'username' => 'root',
        'password' => 'your_password'
    ]
];
```

### 4. 设置Webhook
```php
// 在浏览器中访问
https://api.telegram.org/bot{YOUR_BOT_TOKEN}/setWebhook?url=https://yourdomain.com/telegram_bot.php
```

## 📝 使用方法

### 1. 人类账号发广告
在群组中发送包含关键词的消息：
```
🚀 我们的云服务器特价中！
想联系客服？点下面按钮 👇
```

### 2. Bot自动响应
Bot会自动检测广告消息并发送按钮：
```
请选择您需要的服务 👇
[👨‍💼 联系客服] [👥 进入用户群]
[🌐 访问官网] [📱 下载APP]
```

### 3. 用户点击
用户点击按钮后：
- Bot记录用户行为到数据库
- 发送相应的链接给用户
- 在管理后台可以看到统计数据

## 📊 管理后台

访问 `admin_dashboard.php` 查看：
- 总点击次数
- 活跃用户数
- 各操作类型分布
- 每日趋势图表

## 🔧 自定义配置

### 修改广告关键词
编辑 `isAdMessage()` 方法中的关键词：
```php
$ad_keywords = [
    '想联系客服？点下面按钮',
    '想体验请点下面按钮',
    '点击下面按钮',
    '联系客服',
    '特价',
    '优惠'
];
```

### 修改按钮选项
编辑 `sendAdButtons()` 方法中的按钮配置：
```php
$keyboard = [
    'inline_keyboard' => [
        [
            ['text' => '👨‍💼 联系客服', 'callback_data' => 'action_kefu'],
            ['text' => '👥 进入用户群', 'callback_data' => 'action_usergroup']
        ],
        [
            ['text' => '🌐 访问官网', 'callback_data' => 'action_website'],
            ['text' => '📱 下载APP', 'callback_data' => 'action_app']
        ]
    ]
];
```

### 修改回复内容
编辑 `handleUserAction()` 方法中的回复内容。

## 📈 数据表说明

### user_actions
记录用户每次点击行为：
- user_id: Telegram用户ID
- action: 操作类型
- chat_id: 聊天ID
- created_at: 点击时间

### user_stats
用户统计汇总：
- total_actions: 总操作次数
- kefu_clicks: 客服点击次数
- usergroup_clicks: 用户群点击次数
- website_clicks: 官网点击次数
- app_clicks: APP点击次数

## 🔒 安全注意事项

1. **保护Bot Token**: 不要泄露Bot Token
2. **数据库安全**: 使用强密码，限制数据库访问
3. **HTTPS**: 生产环境必须使用HTTPS
4. **日志监控**: 定期检查错误日志

## 🐛 常见问题

### Q: Bot没有响应
A: 检查webhook设置和Bot Token是否正确

### Q: 数据库连接失败
A: 检查数据库配置和网络连接

### Q: 按钮点击无反应
A: 检查callback_query处理逻辑

## 📞 技术支持

如有问题，请联系技术支持或查看Telegram Bot API文档。
