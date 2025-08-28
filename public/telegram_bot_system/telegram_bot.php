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

    // 处理普通消息
    private function handleMessage($message) {
        $chat_id = $message['chat']['id'];
        $text = $message['text'] ?? '';

        // 如果包含“点下面按钮”，就自动发送按钮
        if (mb_strpos($text, '点下面按钮') !== false) {
            $this->sendAdButtons($chat_id);
        }
    }

    // 处理按钮点击
    private function handleCallbackQuery($callback_query) {
        $callback_data = $callback_query['data'];
        $callback_query_id = $callback_query['id'];
        $user = $callback_query['from'];
        $chat_id = $callback_query['message']['chat']['id'];

        // 记录点击到数据库
        $this->logAction($user['id'], $user['username'] ?? 'unknown', $callback_data);

        // 回复回调，让 Telegram 不显示加载中
        $this->answerCallbackQuery($callback_query_id, "操作已记录");

        // 给用户发送私聊消息（群里不替换消息）
        $redirect_url = $this->getRedirectUrl($callback_data);
        $text = $this->getActionText($callback_data);
        $button_text = $this->getActionButtonText($callback_data);

        $this->sendPrivateMessageWithButton($user['id'], $text, $button_text, $redirect_url);
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

    // 获取跳转 URL
    private function getRedirectUrl($action) {
        switch ($action) {
            case 'kefu': return 'https://t.me/markqing2024';
            case 'usergroup': return 'https://t.me/lalanetworkchat';
            case 'website': return 'https://lala.gg';
            case 'app': return 'https://lala.gg';
            default: return 'https://t.me/markqing2024';
        }
    }

    // 获取私聊消息内容
    private function getActionText($action) {
        $texts = [
            'kefu' => "💬 联系客服\n点击下方按钮直接联系客服：",
            'usergroup' => "👥 进入用户群\n点击下方按钮进入群：",
            'website' => "🌐 访问官网\n点击下方按钮访问：",
            'app' => "📱 下载APP\n点击下方按钮下载："
        ];
        return $texts[$action] ?? "点击下方按钮：";
    }

    // 获取按钮文字
    private function getActionButtonText($action) {
        $texts = [
            'kefu' => "联系客服",
            'usergroup' => "进入用户群",
            'website' => "访问官网",
            'app' => "下载APP"
        ];
        return $texts[$action] ?? "打开链接";
    }

    // 发送带 URL 的私聊消息
    private function sendPrivateMessageWithButton($chat_id, $text, $button_text, $url) {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => $button_text, 'url' => $url]
                ]
            ]
        ];

        $data = [
            'chat_id' => $chat_id,
            'text' => $text,
            'reply_markup' => json_encode($keyboard),
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ];

        $this->sendRequest('sendMessage', $data);
    }

    // 发送群里广告按钮
    private function sendAdButtons($chat_id) {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '💬 联系客服', 'callback_data' => 'kefu'],
                    ['text' => '👥 进入用户群', 'callback_data' => 'usergroup']
                ],
                [
                    ['text' => '🌐 访问官网', 'callback_data' => 'website'],
                    ['text' => '📱 下载APP', 'callback_data' => 'app']
                ]
            ]
        ];

        $data = [
            'chat_id' => $chat_id,
            'text' => "👇 请选择：",
            'reply_markup' => json_encode($keyboard)
        ];

        $this->sendRequest('sendMessage', $data);
    }

    // 回复按钮点击
    private function answerCallbackQuery($callback_query_id, $text = null) {
        $data = ['callback_query_id' => $callback_query_id];
        if ($text) {
            $data['text'] = $text;
            $data['show_alert'] = false;
        }
        return $this->sendRequest('answerCallbackQuery', $data);
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
        return $this->sendRequest('setWebhook', ['url' => $webhook_url]);
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

// 配置
$bot_config = [
    'bot_token' => '你的BotToken',
    'database' => [
        'host' => '127.0.0.1',
        'database' => 'whmcs',
        'username' => 'whmcs',
        'password' => '你的数据库密码',
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
