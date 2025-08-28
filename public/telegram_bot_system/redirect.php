<?php
// redirect.php - 中转页面，统计点击并跳转

// 获取参数
$action = $_GET['action'] ?? '';
$user_id = $_GET['user_id'] ?? 0;
$username = $_GET['username'] ?? '';

// 记录点击到日志文件
$log_entry = date('Y-m-d H:i:s') . " 点击了: $action, 用户ID: $user_id, 用户名: $username\n";
file_put_contents('click.log', $log_entry, FILE_APPEND);

// 数据库配置
$db_config = [
    'host' => '127.0.0.1',
    'database' => 'whmcs',
    'username' => 'whmcs',
    'password' => 'dwxDEfK6478WTSwZ',
    'port' => '3306',
    'charset' => 'utf8mb4'
];

// 记录到数据库
try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset={$db_config['charset']}",
        $db_config['username'],
        $db_config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 插入点击记录
    $sql = "INSERT INTO system_new_user_actions (user_id, username, action, chat_id, created_at) 
            VALUES (:user_id, :username, :action, :chat_id, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'user_id' => $user_id ?: 0,
        'username' => $username ?: 'unknown',
        'action' => 'redirect_' . $action,
        'chat_id' => 0
    ]);
    
} catch (Exception $e) {
    // 记录错误到日志
    file_put_contents('error.log', date('Y-m-d H:i:s') . " 数据库错误: " . $e->getMessage() . "\n", FILE_APPEND);
}

// 根据action跳转到不同链接
switch ($action) {
    case 'kefu':
        $redirect_url = 'https://t.me/markqing2024';
        break;
    case 'usergroup':
        $redirect_url = 'https://t.me/lalanetworkchat';
        break;
    case 'website':
        $redirect_url = 'https://yourwebsite.com';  // 请替换为您的官网
        break;
    case 'app':
        $redirect_url = 'https://yourwebsite.com/download';  // 请替换为您的APP下载链接
        break;
    default:
        $redirect_url = 'https://t.me/markqing2024';
}

// 跳转
header("Location: $redirect_url");
exit;
?>
