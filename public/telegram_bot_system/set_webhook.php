<?php
/**
 * Telegram机器人Webhook设置接口
 * 用于设置和删除webhook
 */


 # 进入脚本目录
/*

cd /path/to/telegram_bot_system

# 设置webhook
php set_webhook.php --action=set --domain=http://admin.tslala.com

# 删除webhook
php set_webhook.php --action=delete --domain=example.com

# 查看webhook信息
php set_webhook.php --action=info --domain=example.com
 */
 
// 机器人配置
$bot_token = '7641427509:AAEJfgrtELcDkJfPn_oU0wkRlEAg_etCnj4';
$api_url = "https://api.telegram.org/bot{$bot_token}/";

// 支持命令行参数
if (php_sapi_name() === 'cli') {
    // 命令行模式
    $options = getopt('', ['domain:', 'action:']);
    $domain = $options['domain'] ?? 'localhost';
    $action = $options['action'] ?? 'set';
} else {
    // Web模式
    $domain = $_GET['domain'] ?? $_SERVER['HTTP_HOST'];
    $action = $_GET['action'] ?? 'set';
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

// 如果参数中包含了协议，直接使用
if (strpos($domain, 'http://') === 0 || strpos($domain, 'https://') === 0) {
    $webhook_url = $domain . "/telegram_bot_system/telegram_bot.php";
} else {
    $webhook_url = "{$protocol}://{$domain}/telegram_bot_system/telegram_bot.php";
}

switch ($action) {
    case 'set':
        $result = setWebhook($webhook_url);
        break;
    case 'delete':
        $result = deleteWebhook();
        break;
    case 'info':
        $result = getWebhookInfo();
        break;
    default:
        $result = ['error' => '无效的操作'];
}

// 返回JSON响应
header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

/**
 * 设置Webhook
 */
function setWebhook($url) {
    global $api_url;
    
    $data = [
        'url' => $url,
        'allowed_updates' => ['message', 'callback_query']
    ];
    
    $response = sendRequest('setWebhook', $data);
    
    if ($response['ok']) {
        return [
            'success' => true,
            'message' => 'Webhook设置成功',
            'url' => $url,
            'response' => $response
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Webhook设置失败',
            'error' => $response['description'] ?? '未知错误',
            'response' => $response
        ];
    }
}

/**
 * 删除Webhook
 */
function deleteWebhook() {
    global $api_url;
    
    $response = sendRequest('deleteWebhook');
    
    if ($response['ok']) {
        return [
            'success' => true,
            'message' => 'Webhook删除成功',
            'response' => $response
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Webhook删除失败',
            'error' => $response['description'] ?? '未知错误',
            'response' => $response
        ];
    }
}

/**
 * 获取Webhook信息
 */
function getWebhookInfo() {
    global $api_url;
    
    $response = sendRequest('getWebhookInfo');
    
    if ($response['ok']) {
        return [
            'success' => true,
            'message' => '获取Webhook信息成功',
            'info' => $response['result'],
            'response' => $response
        ];
    } else {
        return [
            'success' => false,
            'message' => '获取Webhook信息失败',
            'error' => $response['description'] ?? '未知错误',
            'response' => $response
        ];
    }
}

/**
 * 发送API请求
 */
function sendRequest($method, $data = null) {
    global $api_url;
    
    $url = $api_url . $method;
    
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => $data ? json_encode($data) : ''
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    return json_decode($result, true);
}
?>
