<?php
// 测试 Bot 功能

require_once 'telegram_bot.php';

echo "=== Telegram Bot 测试 ===\n\n";

// 测试 Bot Token
$bot_token = '7641427509:AAEJfgrtELcDkJfPn_oU0wkRlEAg_etCnj4';
$test_url = "https://api.telegram.org/bot{$bot_token}/getMe";

echo "测试 Bot Token...\n";
$result = file_get_contents($test_url);
$bot_info = json_decode($result, true);

if ($bot_info['ok']) {
    echo "✅ Bot 连接成功\n";
    echo "Bot 名称: " . $bot_info['result']['first_name'] . "\n";
    echo "Bot 用户名: @" . $bot_info['result']['username'] . "\n\n";
} else {
    echo "❌ Bot Token 无效或网络错误\n";
    echo "错误信息: " . json_encode($bot_info) . "\n\n";
    exit;
}

// 测试数据库连接
echo "测试数据库连接...\n";
try {
    $db_config = [
        'host' => '127.0.0.1',
        'database' => 'whmcs',
        'username' => 'whmcs',
        'password' => 'dwxDEfK6478WTSwZ',
        'port' => '3306',
        'charset' => 'utf8mb4'
    ];
    
    $pdo = new PDO(
        "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset={$db_config['charset']}",
        $db_config['username'],
        $db_config['password']
    );
    echo "✅ 数据库连接成功\n\n";
} catch (Exception $e) {
    echo "❌ 数据库连接失败: " . $e->getMessage() . "\n\n";
}

// 测试 URL 映射
echo "测试 URL 映射...\n";
$bot = new TelegramBot($bot_token, $db_config);

$test_actions = ['kefu', 'usergroup', 'website', 'app'];
foreach ($test_actions as $action) {
    $url = $bot->getRedirectUrl($action);
    echo "$action -> $url\n";
}

echo "\n=== 测试完成 ===\n";
echo "如果 Bot 连接成功，请检查：\n";
echo "1. Webhook 是否正确设置\n";
echo "2. 服务器是否能接收 POST 请求\n";
echo "3. 错误日志中是否有相关信息\n";
?>
