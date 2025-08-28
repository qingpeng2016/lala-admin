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

    public function handleUpdate($update_data) {
        $update = json_decode($update_data, true);
        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        } elseif (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }
    }

    private function handleMessage($message) {
        $chat_id = $message['chat']['id'];
        $text = $message['text'] ?? '';

        // 超管发广告时，自动发送广告 + 按钮（可根据条件限制）
        if (strpos($text, '点下面按钮') !== false || strpos($text, '获取更多福利') !== false) {
            $this->sendAdButtons($chat_id, $message['message_id']);
        }
    }

    private function handleCallbackQuery($callback_query) {
        $callback_data = $callback_query['data'];
        $callback_query_id = $callback_query['id'];
        $user = $callback_query['from'];
        $chat_id = $callback_query['message']['chat']['id'];

        // 记录点击到数据库
        $this->logAction($user['id'], $user['username'] ?? 'unknown', $callback_data);

        // 回调提示用户已记录，群里不显示任何 URL
        $this->answerCallbackQuery($callback_query_id, "操作已记录");

        // 私聊用户，提示 Telegram 内信息（客服账号/群组账号）
        $user_chat_id = $user['id'];
        $redirect_url = $this->getRedirectUrl($callback_data);
        $this->sendClickableLink($user_chat_id, $callback_data, $redirect_url);
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

    public function getRedirectUrl($action) {
        // 这里仅提示 Telegram 内信息，不直接跳浏览器
        switch ($action) {
            case 'kefu': return "@markqing2024";
            case 'usergroup': return "@lalanetworkchat";
            case 'website': return "访问官网：lala.gg";
            case 'app': return "下载APP：lala.gg";
            default: return "@markqing2024";
        }
    }

    private function sendAdButtons($chat_id, $reply_to_message_id) {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '联系客服', 'callback_data' => 'kefu'],
                    ['text' => '进入用户群', 'callback_data' => 'usergroup']
                ],
                [
                    ['text' => '访问官网', 'callback_data' => 'website'],
                    ['text' => '下载APP', 'callback_data' => 'app']
                ]
            ]
        ];
        $data = [
            'chat_id' => $chat_id,
            'text' => "请选择：",
            'reply_markup' => json_encode($keyboard),
            'reply_to_message_id' => $reply_to_message_id
        ];
        $this->sendRequest('sendMessage', $data);
    }

    private function answerCallbackQuery($callback_query_id, $text = null) {
        $data = ['callback_query_id' => $callback_query_id];
        if ($text) $data['text'] = $text;
        return $this->sendRequest('answerCallbackQuery', $data);
    }

    private function sendClickableLink($chat_id, $action, $text) {
        $messages = [
            'kefu' => "💬 <b>联系客服</b>\n请在 Telegram 内联系：$text",
            'usergroup' => "👥 <b>进入用户群</b>\n请在 Telegram 内加入：$text",
            'website' => "🌐 <b>访问官网</b>\n$text",
            'app' => "📱 <b>下载APP</b>\n$text"
        ];
        $msg = $messages[$action] ?? $text;
        $data = [
            'chat_id' => $chat_id,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ];
        $this->sendRequest('sendMessage', $data);
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
        return $this->sendRequest('setWebhook', ['url' => $webhook_url]);
    }
}

// 配置数据库和机器人
$bot_config = [
    'bot_token' => 'YOUR_BOT_TOKEN',
    'database' => [
        'host' => '127.0.0.1',
        'database' => 'whmcs',
        'username' => 'whmcs',
        'password' => 'YOUR_PASSWORD',
        'port' => '3306',
        'charset' => 'utf8mb4'
    ]
];

$bot = new TelegramBot($bot_config['bot_token'], $bot_config['database']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $bot->handleUpdate($input);
}
?>
