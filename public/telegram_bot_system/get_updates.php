<?php
// 配置信息
$bot_token = '7641427509:AAEJfgrtELcDkJfPn_oU0wkRlEAg_etCnj4';
$api_url = "https://api.telegram.org/bot{$bot_token}/";

echo "正在获取Bot信息...\n";

// 先删除webhook
echo "删除webhook...\n";
$delete_url = $api_url . 'deleteWebhook';
$delete_result = file_get_contents($delete_url);
$delete_data = json_decode($delete_result, true);

if ($delete_data['ok']) {
    echo "webhook删除成功\n";
} else {
    echo "webhook删除失败: " . ($delete_data['description'] ?? '未知错误') . "\n";
}

// 等待一下
sleep(2);

// 获取更新
echo "获取更新信息...\n";
$url = $api_url . 'getUpdates';
$result = file_get_contents($url);
$data = json_decode($result, true);

if ($data && $data['ok']) {
    echo "获取成功！找到 " . count($data['result']) . " 条更新\n\n";
    
    if (count($data['result']) == 0) {
        echo "没有找到任何更新。请在群组中发送一条消息，然后重新运行此脚本。\n";
    }
    
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
    echo "获取失败: " . ($data['description'] ?? '未知错误') . "\n";
    if ($data) {
        print_r($data);
    }
}

echo "\n注意：此脚本会删除webhook，请记得重新设置webhook！\n";
?>
