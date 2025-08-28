<?php
require_once 'telegram_bot.php';

// 创建机器人实例
$bot = new TelegramBot();

// 测试图片发送（替换为你的测试群组ID）
$test_chat_id = -1001234567890; // 替换为你的群组ID

echo "正在测试图片发送...\n";

// 测试发送图片
$result = $bot->sendPhoto($test_chat_id, '1.jpg', "台湾云-精品配置表");

if ($result) {
    echo "图片发送成功！\n";
    print_r($result);
} else {
    echo "图片发送失败！\n";
}
?>
