<?php
declare (strict_types = 1);

namespace app\lala_admin\controller;

use think\admin\Controller;
use think\facade\Db;

/**
 * 用户日志管理
 */
class UserLogs extends Controller
{
    /**
     * 用户日志列表
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title = '用户日志管理';
        
        // 获取请求参数
        $get = $this->request->get();
        
        // 创建查询对象
        $query = Db::name('custom_user_behavior_log');
        
        // 添加基础条件：排除user_id为0的记录，只查询普通用户日志（非管理员）
        $query->where('userid', '<>', 0)->where('is_manager', 0);
        
        // 添加搜索条件
        if (isset($get['userid']) && $get['userid'] !== '') {
            $query->where('userid', 'like', "%{$get['userid']}%");
        }
        if (isset($get['action']) && $get['action'] !== '') {
            $query->where('action', $get['action']);
        }
        if (isset($get['description']) && $get['description'] !== '') {
            $query->where('description', 'like', "%{$get['description']}%");
        }
        if (isset($get['ipaddr']) && $get['ipaddr'] !== '') {
            $query->where('ipaddr', 'like', "%{$get['ipaddr']}%");
        }
        
        // 执行分页查询
        $result = $query->order('id desc')->paginate([
            'list_rows' => 20,
            'page' => $get['page'] ?? 1,
            'query' => $get, // 传递所有查询参数，确保分页链接包含搜索条件
            'path' => '/admin.html#/lala_admin/user_logs/index.html', // 使用正确的路径
        ], false);
        
        // 格式化数据
        $list = $result->items();
        
        // 处理时间
        foreach ($list as &$item) {
            if (!empty($item['created_at'])) {
                $item['created_at'] = date('Y-m-d H:i:s', strtotime($item['created_at']));
            }
            // 生成浏览器指纹
            if (!empty($item['user_agent'])) {
                $item['browser_fingerprint'] = $this->generateBrowserFingerprint($item['user_agent']);
            } else {
                $item['browser_fingerprint'] = '';
            }
        }
        
        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get,
            'action_list' => [
                '页面访问' => '页面访问',
                '用户点击' => '用户点击'
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
        // 多特征指纹算法
        $components = [
            $userAgent,
            $this->request->header('Accept-Language', ''),
            $this->request->header('Accept-Encoding', ''),
            $this->request->header('Accept', ''),
            $this->request->header('User-Agent', ''),
            $this->request->ip(),
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
     * 下载用户日志数据
     * @auth true
     */
    public function download()
    {
        // 获取请求参数
        $get = $this->request->get();
        
        // 创建查询对象
        $query = Db::name('custom_user_behavior_log');
        
        // 添加基础条件：排除user_id为0的记录，只查询普通用户日志（非管理员）
        $query->where('userid', '<>', 0)->where('is_manager', 0);
        
        // 添加搜索条件
        if (isset($get['userid']) && $get['userid'] !== '') {
            $query->where('userid', 'like', "%{$get['userid']}%");
        }
        if (isset($get['action']) && $get['action'] !== '') {
            $query->where('action', $get['action']);
        }
        if (isset($get['description']) && $get['description'] !== '') {
            $query->where('description', 'like', "%{$get['description']}%");
        }
        if (isset($get['ipaddr']) && $get['ipaddr'] !== '') {
            $query->where('ipaddr', 'like', "%{$get['ipaddr']}%");
        }
        
        // 获取所有数据（不分页）
        $list = $query->order('id desc')->select()->toArray();
        
        // 处理数据
        foreach ($list as &$item) {
            if (!empty($item['created_at'])) {
                $item['created_at'] = date('Y-m-d H:i:s', strtotime($item['created_at']));
            }
            // 生成浏览器指纹
            if (!empty($item['user_agent'])) {
                $item['browser_fingerprint'] = $this->generateBrowserFingerprint($item['user_agent']);
            } else {
                $item['browser_fingerprint'] = '';
            }
        }
        
        // 设置CSV文件名
        $filename = '用户日志数据_' . date('Y-m-d_H-i-s') . '.csv';
        
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
            '操作类型',
            '操作描述',
            'IP地址',
            '浏览器指纹',
            '用户代理',
            '创建时间'
        ];
        echo implode(',', $headers) . "\n";
        
        // 输出数据
        foreach ($list as $item) {
            $row = [
                $this->escapeCsvField("=\"" . ($item['id'] ?? '') . "\""), // 强制文本格式
                $this->escapeCsvField("=\"" . ($item['userid'] ?? '') . "\""), // 强制文本格式
                $this->escapeCsvField($item['action'] ?? ''),
                $this->escapeCsvField($item['description'] ?? ''),
                $this->escapeCsvField($item['ipaddr'] ?? ''),
                $this->escapeCsvField($item['browser_fingerprint'] ?? ''),
                $this->escapeCsvField($item['user_agent'] ?? ''),
                $this->escapeCsvField("=\"" . ($item['created_at'] ?? '') . "\"") // 强制文本格式
            ];
            echo implode(',', $row) . "\n";
        }
        
        exit;
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