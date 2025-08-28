<?php
class TelegramBot {
    private $bot_token;
    private $api_url;
    private $db;

    public function __construct($bot_token, $db_config) {
        $this->bot_token = $bot_token;
        $this->api_url = "https://api.telegram.org/bot{$bot_token}/";
        $this->initDatabase($db_config);
    }

    private function initDatabase($config) {
        try {
            $port = $config['port'] ?? 3306;
            $charset = $config['charset'] ?? 'utf8mb4';
            $this->db = new PDO(
                "mysql:host={$config['host']};port={$port};dbname={$config['database']};charset={$charset}",
                $config['username'],
                $config['password']
            );
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("数据库连接失败: " . $e->getMessage());
        }
    }

    // 处理webhook
    public function handleUpdate($update_data) {
        $update = json_decode($update_data, true);

        // 新用户加入群
        if (isset($update['message']['new_chat_members'])) {
            $chat_id = $update['message']['chat']['id'];
            $this->sendAdButtons($chat_id);
        }
        // 按钮回调
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }
    }

    // 处理按钮回调
    private function handleCallbackQuery($callback_query) {
        $callback_data = $callback_query['data'];
        $callback_query_id = $callback_query['id'];
        $user = $callback_query['from'];
        $chat_id = $callback_query['message']['chat']['id'];

        // 记录点击
        $this->logAction($user['id'], $user['username'] ?? 'unknown', $callback_data);

        // 回答 callbackQuery，不弹框，不私聊
        $this->answerCallbackQuery($callback_query_id);

        // 不发送私聊消息，URL按钮直接跳转
    }

    private function logAction($user_id, $username, $action) {
        try {
            $sql = "INSERT INTO system_new_user_actions (user_id, username, action, chat_id, created_at) 
                    VALUES (:user_id, :username, :action, :chat_id, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $user_id ?: 0,
                'username' => $username ?: 'unknown',
                'action' => 'callback_' . $action,
                'chat_id' => 0
            ]);
        } catch (Exception $e) {
            error_log("数据库错误: " . $e->getMessage());
        }
    }

    private function getRedirectUrl($action) {
        switch ($action) {
            case 'kefu': return 'https://t.me/markqing2024';
            case 'usergroup': return 'https://t.me/lalanetworkchat';
            case 'website': return 'https://lala.gg';
            case 'app': return 'https://lala.gg';
            default: return 'https://t.me/markqing2024';
        }
    }

    // 自动发送广告 + URL 按钮
    private function sendAdButtons($chat_id) {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '联系客服', 'url' => $this->getRedirectUrl('kefu')],
                    ['text' => '进入用户群', 'url' => $this->getRedirectUrl('usergroup')]
                ],
                [
                    ['text' => '访问官网', 'url' => $this->getRedirectUrl('website')],
                    ['text' => '下载APP', 'url' => $this->getRedirectUrl('app')]
                ]
            ]
        ];

        $text = "⚡️⚡️⚡️⚡️⚡️⚡️⚡️⚡️⚡️\n
❤️‍🔥全球服务器
❤️‍🔥高防CDN
👍香港/新加坡/日本/欧洲/awk等
👍高配/定制
🟠专线/托管

👍免实名, 免备案
👍❤️*❤️❤️小时技术支持  
👍支持USDT付款

点下面按钮，获取更多福利";

        $data = [
            'chat_id' => $chat_id,
            'text' => $text,
            'reply_markup' => json_encode($keyboard),
            'parse_mode' => 'HTML'
        ];

        $this->sendRequest('sendMessage', $data);
    }

    private function answerCallbackQuery($callback_query_id) {
        $data = ['callback_query_id' => $callback_query_id];
        $this->sendRequest('answerCallbackQuery', $data);
    }

    private function sendRequest($method, $data) {
        $url = $this->api_url . $method;
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return json_decode($result, true);
    }

    public function setWebhook($webhook_url) {
        $data = ['url' => $webhook_url];
        return $this->sendRequest('setWebhook', $data);
    }
}

// 配置
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

$bot = new TelegramBot($bot_config['bot_token'], $bot_config['database']);

// 处理 webhook
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $bot->handleUpdate($input);
}
?>
