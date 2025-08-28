<?php
// 设置 Telegram Bot Webhook

$bot_token = '7641427509:AAEJfgrtELcDkJfPn_oU0wkRlEAg_etCnj4';

// 设置 webhook URL（根据你的服务器地址修改）
$webhook_url = 'https://lala.gg/telegram_bot_system/telegram_bot.php';

echo "设置 Webhook...\n";
echo "Webhook URL: $webhook_url\n\n";

// 设置 webhook
$api_url = "https://api.telegram.org/bot{$bot_token}/setWebhook";
$data = ['url' => $webhook_url];

$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($api_url, false, $context);
$response = json_decode($result, true);

echo "设置结果:\n";
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

if ($response['ok']) {
    echo "✅ Webhook 设置成功！\n";
    echo "现在可以测试按钮功能了。\n";
} else {
    echo "❌ Webhook 设置失败！\n";
    echo "错误信息: " . $response['description'] . "\n";
}

// 获取当前 webhook 信息
echo "\n获取当前 Webhook 信息...\n";
$get_webhook_url = "https://api.telegram.org/bot{$bot_token}/getWebhookInfo";
$webhook_info = file_get_contents($get_webhook_url);
$webhook_data = json_decode($webhook_info, true);

echo "当前 Webhook 信息:\n";
echo json_encode($webhook_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
?>
