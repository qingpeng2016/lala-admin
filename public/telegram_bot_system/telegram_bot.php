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

    // 处理 webhook
    public function handleUpdate($update_data) {
        $update = json_decode($update_data, true);

        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        } elseif (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }
    }

    // 处理消息
    private function handleMessage($message) {
        $chat_id = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $user = $message['from'];

        // 新用户加入或关键词触发广告按钮
        if (isset($message['new_chat_members']) ||
            strpos($text, '点下面按钮') !== false ||
            strpos($text, '获取更多福利') !== false) {
            $this->sendAdButtons($chat_id);
        }
    }

    // 处理 callback_query
    private function handleCallbackQuery($callback_query) {
        $callback_data = $callback_query['data'];
        $callback_query_id = $callback_query['id'];
        $user = $callback_query['from'];
        $chat_id = $callback_query['message']['chat']['id'];

        // 记录点击到数据库
        $this->logAction($user['id'], $user['username'] ?? 'unknown', $callback_data);

        // 获取跳转 URL
        $redirect_url = $this->getRedirectUrl($callback_data);

        // 回答回调（群里不显示任何消息）
        $this->answerCallbackQuery($callback_query_id, "已记录，请点击打开");

        // 客服按钮私聊用户，其他按钮群里也可显示
        if ($callback_data === 'kefu') {
            $this->sendClickableLink($user['id'], $callback_data, $redirect_url);
        } else {
            $this->sendClickableLink($chat_id, $callback_data, $redirect_url);
        }
    }

    // 记录用户行为
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

    // 跳转 URL 映射
    public function getRedirectUrl($action) {
        switch ($action) {
            case 'kefu':
                return 'https://t.me/markqing2024';
            case 'usergroup':
                return 'https://t.me/lalanetworkchat';
            case 'website':
                return 'https://lala.gg';
            case 'app':
                return 'https://lala.gg';
            default:
                return 'https://t.me/markqing2024';
        }
    }

    // 发送广告按钮
    private function sendAdButtons($chat_id) {
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
            'text' => "欢迎！请选择：",
            'reply_markup' => json_encode($keyboard)
        ];

        $this->sendRequest('sendMessage', $data);
    }

    // 回答 callback_query
    private function answerCallbackQuery($callback_query_id, $text = null) {
        $data = ['callback_query_id' => $callback_query_id];
        if ($text) $data['text'] = $text;
        $data['show_alert'] = false; // 不弹框，只在客户端显示提示
        return $this->sendRequest('answerCallbackQuery', $data);
    }

    // 发送可点击链接消息
    private function sendClickableLink($chat_id, $action, $url) {
        $messages = [
            'kefu' => "💬 <b>联系客服</b>\n点击打开私聊：\n<a href='$url'>@markqing2024</a>",
            'usergroup' => "👥 <b>进入用户群</b>\n点击下方链接加入群组：\n<a href='$url'>@lalanetworkchat</a>",
            'website' => "🌐 <b>访问官网</b>\n点击下方链接访问：\n<a href='$url'>lala.gg</a>",
            'app' => "📱 <b>下载APP</b>\n点击下方链接下载：\n<a href='$url'>lala.gg</a>"
        ];

        $text = $messages[$action] ?? "点击链接：\n<a href='$url'>$url</a>";

        $data = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ];

        $this->sendRequest('sendMessage', $data);
    }

    // 发送 API 请求
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

    // 设置 webhook
    public function setWebhook($webhook_url) {
        $data = ['url' => $webhook_url];
        return $this->sendRequest('setWebhook', $data);
    }

    // 获取统计
    public function getStats() {
        $sql = "SELECT action, COUNT(*) as count, DATE(created_at) as date
                FROM system_new_user_actions 
                GROUP BY action, DATE(created_at)
                ORDER BY date DESC, count DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// 配置数据库和 bot
$bot_config = [
    'bot_token' => 'YOUR_BOT_TOKEN_HERE',
    'database' => [
        'host' => '127.0.0.1',
        'database' => 'whmcs',
        'username' => 'whmcs',
        'password' => 'YOUR_DB_PASSWORD',
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
