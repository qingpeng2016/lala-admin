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

        // 检测新用户加入
        if (isset($message['new_chat_members'])) {
            foreach ($message['new_chat_members'] as $new_member) {
                // 如果是新用户加入（不是机器人自己）
                if (!$new_member['is_bot']) {
                    // 发送欢迎消息和按钮
                    $this->sendWelcomeMessage($chat_id, $new_member);
                }
            }
        }

        // 保留原有的关键词检测功能
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

        // 发送欢迎消息和按钮
    private function sendWelcomeMessage($chat_id, $new_member) {
        $username = $new_member['username'] ?? $new_member['first_name'] ?? '新朋友';
        
        // 发送文字消息和按钮
        $welcome_text = "🎉 欢迎 $username 加入！\n\n";
        $welcome_text .= "🚀【全球精品3C网络 VPS】🚀\n";
        $welcome_text .= "价格：¥25-32/月起  \n";
        $welcome_text .= "带宽：10M-10G  \n";
        $welcome_text .= "流量：1T - 无限  \n";
        $welcome_text .= "机房：新加坡 / 香港 / 日本 / 美国 / 台湾  \n";
        $welcome_text .= "解锁流媒体：Netflix / HBO / Disney+  \n\n";
        $welcome_text .= "🚀【IEPL NAT 高速通道】🚀\n";
        $welcome_text .= "价格：¥580/月  \n";
        $welcome_text .= "带宽：1Gbps  \n";
        $welcome_text .= "流量：800G  \n";
        $welcome_text .= "线路：厦门->香港 / 深圳->香港 / 广州->香港  \n";
        $welcome_text .= "联通/电信/移动三网入口，20个NAT端口  \n";
        $welcome_text .= "✅ 无需实名、快速开通、双端高防，抗投诉  \n\n";
        $welcome_text .= "💡 获取更多福利和服务，请点击下方按钮：";

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
            'text' => $welcome_text,
            'reply_markup' => json_encode($keyboard),
            'parse_mode' => 'HTML'
        ];

        $this->sendRequest('sendMessage', $data);
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

    // 发送图片
    public function sendPhoto($chat_id, $photo_path, $caption = '') {
        // 检查文件是否存在
        if (!file_exists($photo_path)) {
            error_log("图片文件不存在: $photo_path");
            return false;
        }

        // 使用 CURLFile 发送本地文件
        $data = [
            'chat_id' => $chat_id,
            'photo' => new CURLFile($photo_path),
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ];

        return $this->sendRequest('sendPhoto', $data);
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

        // 检查是否包含文件上传
        $has_file = false;
        foreach ($data as $value) {
            if ($value instanceof CURLFile) {
                $has_file = true;
                break;
            }
        }

        if ($has_file) {
            // 使用 cURL 处理文件上传
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $result = curl_exec($ch);
            curl_close($ch);
        } else {
            // 普通请求使用 file_get_contents
            $options = [
                'http' => [
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data)
                ]
            ];

            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
        }

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
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $bot->handleUpdate($input);
}
?>
