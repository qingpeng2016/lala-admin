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

        // 检测关键词
        if (strpos($text, '点下面按钮') !== false || strpos($text, '获取更多福利') !== false) {
            // 发送按钮
            $this->sendAdButtons($chat_id, $message['message_id']);
        }
    }

    // 处理按钮回调（现在使用callback_data按钮）
    private function handleCallbackQuery($callback_query) {
        $callback_data = $callback_query['data'];
        $callback_query_id = $callback_query['id'];
        $user = $callback_query['from'];
        $chat_id = $callback_query['message']['chat']['id'];

        // 记录点击到数据库
        $this->logAction($user['id'], $user['username'] ?? 'unknown', $callback_data);

        // 根据callback_data确定跳转URL
        $redirect_url = $this->getRedirectUrl($callback_data);

        // 先回答回调查询
        $this->answerCallbackQuery($callback_query_id);

        // 发送包含可点击链接的消息
        $this->sendClickableLink($chat_id, $callback_data, $redirect_url);
    }



    // 记录用户行为到数据库
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
            // 记录错误到日志
            error_log("数据库错误: " . $e->getMessage());
        }
    }

    // 根据action获取跳转URL
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
            'text' => "请选择：",  // 简短文字，让按钮显示
            'reply_markup' => json_encode($keyboard),
            'reply_to_message_id' => $reply_to_message_id
        ];

        $this->sendRequest('sendMessage', $data);
    }







    // 回答回调查询
    private function answerCallbackQuery($callback_query_id, $url = null) {
        $data = [
            'callback_query_id' => $callback_query_id
        ];

        // 如果提供了URL，添加到参数中，这样Telegram客户端会直接打开该URL
        if ($url) {
            $data['url'] = $url;
        }

        return $this->sendRequest('answerCallbackQuery', $data);
    }

    // 发送可点击链接消息
    private function sendClickableLink($chat_id, $action, $url) {
        $messages = [
            'kefu' => "💬 <b>联系客服</b>\n点击下方链接直接联系客服：\n<a href='$url'>@markqing2024</a>",
            'usergroup' => "👥 <b>进入用户群</b>\n点击下方链接进入用户群：\n<a href='$url'>@lalanetworkchat</a>",
            'website' => "🌐 <b>访问官网</b>\n点击下方链接访问官网：\n<a href='$url'>lala.gg</a>",
            'app' => "📱 <b>下载APP</b>\n点击下方链接下载APP：\n<a href='$url'>lala.gg</a>"
        ];

        $text = $messages[$action] ?? "点击下方链接：\n<a href='$url'>$url</a>";

        $data = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ];

        $this->sendRequest('sendMessage', $data);
    }

    // 发送API请求
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

    // 设置webhook
    public function setWebhook($webhook_url) {
        $data = ['url' => $webhook_url];
        return $this->sendRequest('setWebhook', $data);
    }

    // 获取统计信息
    public function getStats() {
        $sql = "SELECT 
                    action,
                    COUNT(*) as count,
                    DATE(created_at) as date
                FROM system_new_user_actions 
                GROUP BY action, DATE(created_at)
                ORDER BY date DESC, count DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// 直接配置数据库信息（避免依赖其他函数）
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

// 处理webhook
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $bot->handleUpdate($input);
}
?>
