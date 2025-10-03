<?php
declare (strict_types = 1);

namespace app\lala\controller;

use think\admin\Controller;
use think\facade\Db;

/**
 * 实名认证管理
 */
class Realnames extends Controller
{
    /**
     * 实名认证列表
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title = '实名认证管理';
        
        // 获取请求参数
        $get = $this->request->get();
        
        // 创建查询对象
        $query = Db::name('realname_personal_list');
        
        // 添加搜索条件
        if (isset($get['auth_real_name']) && $get['auth_real_name'] !== '') {
            $query->where('auth_real_name', 'like', "%{$get['auth_real_name']}%");
        }
        if (isset($get['email']) && $get['email'] !== '') {
            $query->where('email', 'like', "%{$get['email']}%");
        }
        if (isset($get['mobile']) && $get['mobile'] !== '') {
            $query->where('mobile', 'like', "%{$get['mobile']}%");
        }
        if (isset($get['phone']) && $get['phone'] !== '') {
            $query->where('mobile', 'like', "%{$get['phone']}%");
        }
        if (isset($get['auth_card_number']) && $get['auth_card_number'] !== '') {
            $query->where('auth_card_number', 'like', "%{$get['auth_card_number']}%");
        }
        
        // 执行分页查询
        $result = $query->order('id desc')->paginate([
            'list_rows' => 20,
            'page' => $get['page'] ?? 1,
            'query' => $get, // 传递所有查询参数，确保分页链接包含搜索条件
            'path' => '/admin.html#/lala/realnames/index.html', // 使用正确的路径
        ], false);
        
        // 格式化数据
        $list = $result->items();
        
        // 处理时间和脱敏
        foreach ($list as &$item) {
            if (!empty($item['create_time'])) {
                $item['create_time'] = date('Y-m-d H:i:s', strtotime($item['create_time']));
            }
            if (!empty($item['update_time'])) {
                $item['update_time'] = date('Y-m-d H:i:s', strtotime($item['update_time']));
            }
            // 生成浏览器指纹
            if (!empty($item['user_agent'])) {
                $item['browser_fingerprint'] = $this->generateBrowserFingerprint($item['user_agent']);
            } else {
                $item['browser_fingerprint'] = '';
            }
            // 手机号码脱敏
            if (!empty($item['mobile'])) {
                $item['mobile'] = $this->maskMobile($item['mobile']);
            }
            // 身份证号码脱敏
            if (!empty($item['auth_card_number'])) {
                $item['auth_card_number'] = $this->maskIdCard($item['auth_card_number']);
            }
        }
        
        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get,
            'status_list' => [
                0 => '待审核',
                1 => '已通过',
                2 => '已拒绝'
            ]
        ]);
        
        // 渲染视图
        return $this->fetch();
    }
    
    /**
     * 生成浏览器指纹
     * @param string $userAgent
     * @return string
     */
    private function generateBrowserFingerprint($userAgent)
    {
        // 简化指纹算法，只基于user_agent
        $components = [
            $userAgent,
            date('Y-m-d'), // 添加日期作为组件
        ];
        
        // 生成哈希
        $fingerprint = $this->hashString(implode('|', $components));
        
        // 返回32位指纹字符串
        return substr($fingerprint, 0, 32);
    }
    
    /**
     * 字符串哈希算法
     * @param string $str
     * @return string
     */
    private function hashString($str)
    {
        // 使用多种哈希算法组合
        $hash1 = md5($str);
        $hash2 = sha1($str);
        $hash3 = hash('sha256', $str);
        
        // 组合哈希值
        $combined = $hash1 . $hash2 . $hash3;
        
        // 再次哈希
        return md5($combined);
    }
    
    /**
     * 手机号码脱敏
     * @param string $mobile
     * @return string
     */
    private function maskMobile($mobile)
    {
        if (strlen($mobile) == 11) {
            // 手机号码格式：138****5678
            return substr($mobile, 0, 3) . '****' . substr($mobile, 7, 4);
        }
        return $mobile;
    }
    
    /**
     * 身份证号码脱敏
     * @param string $idCard
     * @return string
     */
    private function maskIdCard($idCard)
    {
        if (strlen($idCard) == 18) {
            // 身份证号码格式：110101********1234
            return substr($idCard, 0, 6) . '********' . substr($idCard, 14, 4);
        } elseif (strlen($idCard) == 15) {
            // 15位身份证号码格式：110101******123
            return substr($idCard, 0, 6) . '******' . substr($idCard, 12, 3);
        }
        return $idCard;
    }
    
    /**
     * 下载实名认证数据
     * @auth true
     */
    public function download()
    {
        try {
            error_log("=== 开始下载实名认证数据 ===", 3, "/tmp/qp.log");
            
            // 获取请求参数
            $get = $this->request->get();
            error_log("请求参数: " . json_encode($get), 3, "/tmp/qp.log");
            error_log("user_ids参数: " . ($get['user_ids'] ?? '未设置'), 3, "/tmp/qp.log");
            
            // 创建查询对象
            $query = Db::name('realname_personal_list');
            
            // 检查是否指定了用户ID
            if (isset($get['user_ids']) && $get['user_ids'] !== '') {
                error_log("接收到user_ids参数: " . $get['user_ids'], 3, "/tmp/qp.log");
                // 支持换行符和|分隔符
                $userIds = preg_split('/[\n|]/', $get['user_ids']);
                error_log("分割后的用户ID数组: " . json_encode($userIds), 3, "/tmp/qp.log");
                $validUserIds = [];
                foreach ($userIds as $userId) {
                    $userId = trim($userId);
                    if (is_numeric($userId) && $userId > 0) {
                        $validUserIds[] = $userId;
                    }
                }
                error_log("有效的用户ID: " . json_encode($validUserIds), 3, "/tmp/qp.log");
                if (!empty($validUserIds)) {
                    $query->whereIn('auth_user_id', $validUserIds);
                    error_log("指定用户ID: " . implode(',', $validUserIds), 3, "/tmp/qp.log");
                }
            } else {
                // 添加搜索条件（只有在没有指定用户ID时才应用）
                if (isset($get['auth_real_name']) && $get['auth_real_name'] !== '') {
                    $query->where('auth_real_name', 'like', "%{$get['auth_real_name']}%");
                }
                if (isset($get['email']) && $get['email'] !== '') {
                    $query->where('email', 'like', "%{$get['email']}%");
                }
                if (isset($get['mobile']) && $get['mobile'] !== '') {
                    $query->where('mobile', 'like', "%{$get['mobile']}%");
                }
                if (isset($get['phone']) && $get['phone'] !== '') {
                    $query->where('mobile', 'like', "%{$get['phone']}%");
                }
                if (isset($get['auth_card_number']) && $get['auth_card_number'] !== '') {
                    $query->where('auth_card_number', 'like', "%{$get['auth_card_number']}%");
                }
            }
            
            // 获取所有用户数据（不分页）
            $users = $query->order('id desc')->select()->toArray();
            error_log("查询到用户数量: " . count($users), 3, "/tmp/qp.log");
            
            // 如果没有找到任何用户，直接返回
            if (empty($users)) {
                error_log("没有找到任何用户数据", 3, "/tmp/qp.log");
                http_response_code(404);
                echo '没有找到指定的用户数据';
                exit;
            }
            
            // 设置CSV文件名
            if (isset($get['user_ids']) && $get['user_ids'] !== '') {
                $filename = '指定用户实名认证数据_' . date('Y-m-d_H-i-s') . '.csv';
            } else {
                $filename = '实名认证数据_' . date('Y-m-d_H-i-s') . '.csv';
            }
            error_log("CSV文件名: " . $filename, 3, "/tmp/qp.log");
            
            // 设置响应头
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
            
            // 添加BOM以支持中文
            echo "\xEF\xBB\xBF";
            
            // 输出CSV头部
            $headers = [
                'ID',
                '用户ID',
                '真实姓名',
                '身份证号码',
                '手机号码',
                '邮箱',
                '状态',
                '创建时间',
                '更新时间'
            ];
            echo implode(',', $headers) . "\n";
            error_log("CSV头部已输出", 3, "/tmp/qp.log");
            
            // 输出用户数据
            $userCount = 0;
            error_log("开始处理用户数据，用户数组长度: " . count($users), 3, "/tmp/qp.log");
            
            foreach ($users as $user) {
                $userCount++;
                error_log("开始处理第 {$userCount} 个用户", 3, "/tmp/qp.log");
                error_log("用户数据: " . json_encode($user), 3, "/tmp/qp.log");
                
                try {
                    // 处理时间
                    if (!empty($user['create_time'])) {
                        $user['create_time'] = date('Y-m-d H:i:s', strtotime($user['create_time']));
                    }
                    if (!empty($user['update_time'])) {
                        $user['update_time'] = date('Y-m-d H:i:s', strtotime($user['update_time']));
                    }
                    
                    // 状态转换
                    $statusText = '';
                    switch ($user['status']) {
                        case 0: $statusText = '待审核'; break;
                        case 1: $statusText = '已通过'; break;
                        case 2: $statusText = '已拒绝'; break;
                        default: $statusText = '未知'; break;
                    }
                    
                    $userData = [
                        $this->escapeCsvField("=\"" . ($user['id'] ?? '') . "\""), // 强制文本格式
                        $this->escapeCsvField("=\"" . ($user['user_id'] ?? '') . "\""), // 强制文本格式
                        $this->escapeCsvField($user['auth_real_name'] ?? ''),
                        $this->escapeCsvField("=\"" . ($user['auth_card_number'] ?? '') . "\""), // 强制文本格式
                        $this->escapeCsvField("=\"" . ($user['mobile'] ?? '') . "\""), // 强制文本格式
                        $this->escapeCsvField($user['email'] ?? ''),
                        $this->escapeCsvField($statusText),
                        $this->escapeCsvField("=\"" . ($user['create_time'] ?? '') . "\""), // 强制文本格式
                        $this->escapeCsvField("=\"" . ($user['update_time'] ?? '') . "\"") // 强制文本格式
                    ];
                    
                    echo implode(',', $userData) . "\n";
                    error_log("第 {$userCount} 个用户数据处理完成", 3, "/tmp/qp.log");
                } catch (\Exception $e) {
                    error_log("处理第 {$userCount} 个用户时出错: " . $e->getMessage(), 3, "/tmp/qp.log");
                }
            }
            error_log("用户数据处理完成，共处理 {$userCount} 个用户", 3, "/tmp/qp.log");
            
            // 创建临时目录
            $tempDir = sys_get_temp_dir() . '/realname_export_' . time();
            error_log("临时目录: " . $tempDir, 3, "/tmp/qp.log");
            
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
                error_log("创建临时目录成功", 3, "/tmp/qp.log");
            } else {
                error_log("临时目录已存在", 3, "/tmp/qp.log");
            }
            
            // 创建用户信息CSV文件
            $userCsvFile = $tempDir . '/用户信息.csv';
            $userCsvContent = "\xEF\xBB\xBF"; // BOM
            $userCsvContent .= implode(',', $headers) . "\n";
            
            foreach ($users as $user) {
                // 处理时间
                if (!empty($user['create_time'])) {
                    $user['create_time'] = date('Y-m-d H:i:s', strtotime($user['create_time']));
                }
                if (!empty($user['update_time'])) {
                    $user['update_time'] = date('Y-m-d H:i:s', strtotime($user['update_time']));
                }
                
                // 状态转换
                $statusText = '';
                switch ($user['status']) {
                    case 0: $statusText = '待审核'; break;
                    case 1: $statusText = '已通过'; break;
                    case 2: $statusText = '已拒绝'; break;
                    default: $statusText = '未知'; break;
                }
                
                                    $userData = [
                        $this->escapeCsvField("=\"" . ($user['id'] ?? '') . "\""), // 强制文本格式
                        $this->escapeCsvField("=\"" . ($user['auth_user_id'] ?? '') . "\""), // 强制文本格式
                        $this->escapeCsvField($user['auth_real_name'] ?? ''),
                        $this->escapeCsvField("=\"" . ($user['auth_card_number'] ?? '') . "\""), // 强制文本格式
                        $this->escapeCsvField("=\"" . ($user['mobile'] ?? '') . "\""), // 强制文本格式
                        $this->escapeCsvField($user['email'] ?? ''),
                        $this->escapeCsvField($statusText),
                        $this->escapeCsvField("=\"" . ($user['create_time'] ?? '') . "\""), // 强制文本格式
                        $this->escapeCsvField("=\"" . ($user['update_time'] ?? '') . "\"") // 强制文本格式
                    ];
                
                $userCsvContent .= implode(',', $userData) . "\n";
            }
            
            file_put_contents($userCsvFile, $userCsvContent);
            
            // 为每个用户创建日志CSV文件
            $logFileCount = 0;
            
            foreach ($users as $user) {
                // 调试：输出用户ID
                error_log("正在查询用户ID: {$user['auth_user_id']} 的日志数据", 3, "/tmp/qp.log");
                
                // 获取该用户的日志数据
                $logs = Db::name('custom_user_behavior_log')
                    ->where('userid', $user['auth_user_id'])
                    ->order('id desc')
                    ->select()
                    ->toArray();
                
                // 调试：输出查询结果数量
                error_log("用户ID {$user['auth_user_id']} 的日志数量: " . count($logs), 3, "/tmp/qp.log");
                
                if (!empty($logs)) {
                    // 创建用户日志CSV文件
                    $logCsvFile = $tempDir . '/用户' . $user['auth_user_id'] . '_' . $user['auth_real_name'] . '_日志.csv';
                    $logCsvContent = "\xEF\xBB\xBF"; // BOM
                    
                    // 日志表头
                    $logHeaders = [
                        'ID',
                        '用户ID',
                        '操作类型',
                        '操作描述',
                        'IP地址',
                        '创建时间'
                    ];
                    $logCsvContent .= implode(',', $logHeaders) . "\n";
                    
                    // 输出日志数据
                    foreach ($logs as $log) {
                        // 处理时间
                        if (!empty($log['created_at'])) {
                            $log['created_at'] = date('Y-m-d H:i:s', strtotime($log['created_at']));
                        }
                        
                        $logData = [
                            $this->escapeCsvField("=\"" . ($log['id'] ?? '') . "\""), // 强制文本格式
                            $this->escapeCsvField("=\"" . ($log['userid'] ?? '') . "\""), // 强制文本格式
                            $this->escapeCsvField($log['action'] ?? ''),
                            $this->escapeCsvField($log['description'] ?? ''),
                            $this->escapeCsvField($log['ipaddr'] ?? ''),
                            $this->escapeCsvField("=\"" . ($log['created_at'] ?? '') . "\"") // 强制文本格式
                        ];
                        
                        $logCsvContent .= implode(',', $logData) . "\n";
                    }
                    
                    file_put_contents($logCsvFile, $logCsvContent);
                    $logFileCount++;
                    error_log("成功创建日志文件: " . basename($logCsvFile), 3, "/tmp/qp.log");
                } else {
                    error_log("用户ID {$user['auth_user_id']} 没有找到日志数据", 3, "/tmp/qp.log");
                }
            }
            
            error_log("总共创建了 {$logFileCount} 个日志文件", 3, "/tmp/qp.log");
            
            // 创建ZIP文件
            if (isset($get['user_ids']) && $get['user_ids'] !== '') {
                $zipFile = $tempDir . '/指定用户实名认证数据_' . date('Y-m-d_H-i-s') . '.zip';
            } else {
                $zipFile = $tempDir . '/实名认证数据_' . date('Y-m-d_H-i-s') . '.zip';
            }
            error_log("ZIP文件路径: " . $zipFile, 3, "/tmp/qp.log");
            
            $zip = new \ZipArchive();
            
            if ($zip->open($zipFile, \ZipArchive::CREATE) === TRUE) {
                error_log("ZIP文件创建成功", 3, "/tmp/qp.log");
                
                // 创建文件夹结构
                if (isset($get['user_ids']) && $get['user_ids'] !== '') {
                    $folderName = '指定用户实名认证数据_' . date('Y-m-d_H-i-s');
                } else {
                    $folderName = '实名认证数据_' . date('Y-m-d_H-i-s');
                }
                error_log("文件夹名称: " . $folderName, 3, "/tmp/qp.log");
                
                // 添加用户信息文件到文件夹中
                $zip->addFile($userCsvFile, $folderName . '/用户信息.csv');
                error_log("添加用户信息文件成功", 3, "/tmp/qp.log");
                
                // 添加所有日志文件到文件夹中
                $files = glob($tempDir . '/*_日志.csv');
                error_log("找到日志文件数量: " . count($files), 3, "/tmp/qp.log");
                
                foreach ($files as $file) {
                    $zip->addFile($file, $folderName . '/' . basename($file));
                    error_log("添加日志文件: " . basename($file), 3, "/tmp/qp.log");
                }
                
                $zip->close();
                error_log("ZIP文件关闭成功", 3, "/tmp/qp.log");
                
                // 检查ZIP文件是否创建成功
                if (!file_exists($zipFile) || filesize($zipFile) == 0) {
                    throw new \Exception('ZIP文件创建失败或文件为空');
                }
                
                error_log("ZIP文件大小: " . filesize($zipFile) . " 字节", 3, "/tmp/qp.log");
                
                // 输出ZIP文件
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . basename($zipFile) . '"');
                header('Content-Length: ' . filesize($zipFile));
                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
                
                // 确保输出缓冲区为空
                if (ob_get_level()) {
                    ob_end_clean();
                }
                
                readfile($zipFile);
                error_log("ZIP文件输出完成", 3, "/tmp/qp.log");
                
                // 清理临时文件
                array_map('unlink', glob($tempDir . '/*'));
                rmdir($tempDir);
                error_log("临时文件清理完成", 3, "/tmp/qp.log");
                
                exit;
            } else {
                throw new \Exception('无法创建ZIP文件: ' . $zip->getStatusString());
            }
        } catch (\Exception $e) {
            error_log('下载实名认证数据失败: ' . $e->getMessage());
            http_response_code(500);
            echo '下载失败: ' . $e->getMessage();
            exit;
        }
    }
    
    /**
     * 转义CSV字段
     * @param string $field
     * @return string
     */
    private function escapeCsvField($field)
    {
        if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
            return '"' . str_replace('"', '""', $field) . '"';
        }
        return $field;
    }
} 