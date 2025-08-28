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
        
        // 检测是否是广告消息（包含特定关键词）
        if ($this->isAdMessage($text)) {
            $this->sendAdButtons($chat_id, $message['message_id']);
        }
    }
    
    // 检测是否是广告消息
    private function isAdMessage($text) {
        $ad_keywords = [
            '想联系客服？点下面按钮',
            '想体验请点下面按钮',
            '点击下面按钮',
            '联系客服',
            '特价',
            '优惠'
        ];
        
        foreach ($ad_keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
    
    // 发送广告按钮
    private function sendAdButtons($chat_id, $reply_to_message_id) {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '👨‍💼 联系客服', 'callback_data' => 'action_kefu'],
                    ['text' => '👥 进入用户群', 'callback_data' => 'action_usergroup']
                ],
                [
                    ['text' => '🌐 访问官网', 'callback_data' => 'action_website'],
                    ['text' => '📱 下载APP', 'callback_data' => 'action_app']
                ]
            ]
        ];
        
        $data = [
            'chat_id' => $chat_id,
            'text' => "请选择您需要的服务 👇",
            'reply_markup' => json_encode($keyboard),
            'reply_to_message_id' => $reply_to_message_id
        ];
        
        $this->sendRequest('sendMessage', $data);
    }
    
    // 处理按钮回调
    private function handleCallbackQuery($callback_query) {
        $user = $callback_query['from'];
        $action = $callback_query['data'];
        $chat_id = $callback_query['message']['chat']['id'];
        
        // 记录用户行为
        $this->logUserAction($user['id'], $user['username'], $action, $chat_id);
        
        // 回复用户
        $this->handleUserAction($user['id'], $action);
        
        // 回答回调查询（消除按钮加载状态）
        $this->answerCallbackQuery($callback_query['id']);
    }
    
    // 记录用户行为
    private function logUserAction($user_id, $username, $action, $chat_id) {
        // 1. 插入用户行为记录
        $sql = "INSERT INTO system_new_user_actions (user_id, username, action, chat_id, created_at) 
                VALUES (:user_id, :username, :action, :chat_id, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'username' => $username,
            'action' => $action,
            'chat_id' => $chat_id
        ]);
        
        // 2. 更新用户统计
        $this->updateUserStats($user_id, $username, $action);
        
        // 记录日志
        error_log("用户 {$user_id} (@{$username}) 点击了 {$action}");
    }
    
    // 更新用户统计
    private function updateUserStats($user_id, $username, $action) {
        $sql = "INSERT INTO system_new_user_stats (user_id, username, total_actions, kefu_clicks, usergroup_clicks, website_clicks, app_clicks) 
                VALUES (:user_id, :username, 1, 
                        CASE WHEN :action = 'action_kefu' THEN 1 ELSE 0 END,
                        CASE WHEN :action = 'action_usergroup' THEN 1 ELSE 0 END,
                        CASE WHEN :action = 'action_website' THEN 1 ELSE 0 END,
                        CASE WHEN :action = 'action_app' THEN 1 ELSE 0 END)
                ON DUPLICATE KEY UPDATE
                    total_actions = total_actions + 1,
                    kefu_clicks = kefu_clicks + CASE WHEN :action = 'action_kefu' THEN 1 ELSE 0 END,
                    usergroup_clicks = usergroup_clicks + CASE WHEN :action = 'action_usergroup' THEN 1 ELSE 0 END,
                    website_clicks = website_clicks + CASE WHEN :action = 'action_website' THEN 1 ELSE 0 END,
                    app_clicks = app_clicks + CASE WHEN :action = 'action_app' THEN 1 ELSE 0 END,
                    last_seen = CURRENT_TIMESTAMP";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'username' => $username,
            'action' => $action
        ]);
    }
    
    // 处理用户操作
    private function handleUserAction($user_id, $action) {
        $response_text = '';
        
        switch ($action) {
            case 'action_kefu':
                $response_text = "👨‍💼 联系客服\n\n";
                $response_text .= "📞 客服电话: +86-xxx-xxxx-xxxx\n";
                $response_text .= "💬 在线客服: @your_kefu_bot\n";
                $response_text .= "📧 邮箱: support@yourcompany.com\n\n";
                $response_text .= "⏰ 服务时间: 9:00-18:00 (周一至周五)";
                break;
                
            case 'action_usergroup':
                $response_text = "👥 用户交流群\n\n";
                $response_text .= "🔗 主群链接: https://t.me/your_main_group\n";
                $response_text .= "🔗 技术群: https://t.me/your_tech_group\n";
                $response_text .= "🔗 VIP群: https://t.me/your_vip_group\n\n";
                $response_text .= "💡 加入群组，与其他用户交流经验！";
                break;
                
            case 'action_website':
                $response_text = "🌐 官方网站\n\n";
                $response_text .= "🔗 官网: https://yourwebsite.com\n";
                $response_text .= "🔗 产品介绍: https://yourwebsite.com/products\n";
                $response_text .= "🔗 价格方案: https://yourwebsite.com/pricing\n\n";
                $response_text .= "📖 了解更多产品详情！";
                break;
                
            case 'action_app':
                $response_text = "📱 下载APP\n\n";
                $response_text .= "🍎 iOS版本: https://apps.apple.com/your-app\n";
                $response_text .= "🤖 Android版本: https://play.google.com/your-app\n";
                $response_text .= "💻 桌面版本: https://yourwebsite.com/download\n\n";
                $response_text .= "📲 随时随地使用我们的服务！";
                break;
        }
        
        // 发送回复
        $data = [
            'chat_id' => $user_id,
            'text' => $response_text
        ];
        
        $this->sendRequest('sendMessage', $data);
    }
    
    // 回答回调查询
    private function answerCallbackQuery($callback_query_id) {
        $data = [
            'callback_query_id' => $callback_query_id
        ];
        
        $this->sendRequest('answerCallbackQuery', $data);
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
