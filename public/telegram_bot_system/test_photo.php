<?php
require_once 'telegram_bot.php';

// 配置信息
$bot_config = [
    'bot_token' => '7641427509:AAEJfgrtELcDkJfPn_oU0wkRlEAg_etCnj4',
    'database' => [
        'host' => '127.0.0.1',
        'database' => 'whmcs',
        'username' => 'whmcs',
        'password' => 'dwxDEfK6478WTSwZ',
        'port' => '3306',
        'charset' => 'utf8mb4'
    ]
];

// 创建机器人实例
$bot = new TelegramBot($bot_config['bot_token'], $bot_config['database']);

// 测试图片发送（替换为你的测试群组ID）
$test_chat_id = -1001234567890; // 替换为你的群组ID

echo "正在测试图片发送...\n";
echo "群组ID: $test_chat_id\n";
echo "图片文件: " . (file_exists('1.jpg') ? '存在' : '不存在') . "\n";

// 测试发送图片
$result = $bot->sendPhoto($test_chat_id, '1.jpg', "台湾云-精品配置表");

if ($result) {
    echo "图片发送成功！\n";
    print_r($result);
} else {
    echo "图片发送失败！\n";
}
?>
