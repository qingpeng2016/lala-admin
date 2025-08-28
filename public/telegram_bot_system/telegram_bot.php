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
        
        // 记录点击到数据库（埋点）
        $this->logAction($user['id'], $user['username'] ?? 'unknown', $callback_data);
        
        // 根据callback_data确定跳转URL
        $redirect_url = $this->getRedirectUrl($callback_data);
        
        // 检查是否是第二次点击（通过检查最近是否有相同用户的相同action记录）
        if ($this->isSecondClick($user['id'], $callback_data)) {
            // 第二次点击，直接跳转
            $this->answerCallbackQuery($callback_query_id, $redirect_url);
        } else {
            // 第一次点击，显示弹窗
            $this->showAlertWithLink($callback_query_id, $callback_data, $redirect_url);
        }
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
    
    // 检查是否是第二次点击
    private function isSecondClick($user_id, $action) {
        try {
            $sql = "SELECT COUNT(*) as count FROM system_new_user_actions 
                    WHERE user_id = :user_id AND action = :action 
                    AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $user_id,
                'action' => 'callback_' . $action
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 1; // 如果1分钟内点击超过1次，就是第二次点击
        } catch (Exception $e) {
            error_log("检查第二次点击错误: " . $e->getMessage());
            return false;
        }
    }
    
    // 显示弹窗提示
    private function showAlertWithLink($callback_query_id, $action, $url) {
        $messages = [
            'kefu' => "💬 联系客服\n\n已记录您的点击！\n\n请再次点击按钮跳转到客服",
            'usergroup' => "👥 进入用户群\n\n已记录您的点击！\n\n请再次点击按钮跳转到用户群",
            'website' => "🌐 访问官网\n\n已记录您的点击！\n\n请再次点击按钮跳转到官网",
            'app' => "📱 下载APP\n\n已记录您的点击！\n\n请再次点击按钮跳转到APP下载"
        ];
        
        $text = $messages[$action] ?? "已记录您的点击！\n\n请再次点击按钮跳转";
        
        $data = [
            'callback_query_id' => $callback_query_id,
            'text' => $text,
            'show_alert' => true  // 显示弹窗
        ];
        
        return $this->sendRequest('answerCallbackQuery', $data);
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

// 添加调试信息
error_log("Bot配置: " . json_encode($bot_config));

$bot = new TelegramBot($bot_config['bot_token'], $bot_config['database']);

// 处理webhook
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $bot->handleUpdate($input);
}
?>
