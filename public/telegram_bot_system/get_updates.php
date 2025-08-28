<?php
// 配置信息
$bot_token = '7641427509:AAEJfgrtELcDkJfPn_oU0wkRlEAg_etCnj4';
$api_url = "https://api.telegram.org/bot{$bot_token}/";

echo "正在获取Bot更新信息...\n";

// 获取更新
$url = $api_url . 'getUpdates';
$result = file_get_contents($url);
$data = json_decode($result, true);

if ($data['ok']) {
    echo "获取成功！找到 " . count($data['result']) . " 条更新\n\n";
    
    foreach ($data['result'] as $update) {
        if (isset($update['message'])) {
            $message = $update['message'];
            $chat = $message['chat'];
            
            echo "=== 消息信息 ===\n";
            echo "消息ID: " . $message['message_id'] . "\n";
            echo "群组ID: " . $chat['id'] . "\n";
            echo "群组类型: " . $chat['type'] . "\n";
            
            if (isset($chat['title'])) {
                echo "群组名称: " . $chat['title'] . "\n";
            }
            
            if (isset($message['text'])) {
                echo "消息内容: " . $message['text'] . "\n";
            }
            
            echo "时间: " . date('Y-m-d H:i:s', $message['date']) . "\n";
            echo "================\n\n";
        }
    }
} else {
    echo "获取失败: " . $data['description'] . "\n";
}
?>
