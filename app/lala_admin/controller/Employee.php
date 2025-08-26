<?php
declare (strict_types = 1);

namespace app\lala_admin\controller;

use think\admin\Controller;
use think\facade\Db;

/**
 * 员工管理
 */
class Employee extends Controller
{
    /**
     * 员工列表
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title = '员工管理';
        
        // 获取请求参数
        $get = $this->request->get();
        
        // 创建查询对象
        $query = Db::name('system_user');
        
        // 添加基础条件：只查询未删除的记录
        $query->where('is_deleted', 0);
        
        // 添加搜索条件
        if (isset($get['username']) && $get['username'] !== '') {
            $query->where('username', 'like', "%{$get['username']}%");
        }
        if (isset($get['nickname']) && $get['nickname'] !== '') {
            $query->where('nickname', 'like', "%{$get['nickname']}%");
        }
        if (isset($get['usertype']) && $get['usertype'] !== '') {
            $query->where('usertype', $get['usertype']);
        }
        if (isset($get['status']) && $get['status'] !== '') {
            $query->where('status', $get['status']);
        }
        if (isset($get['contact_phone']) && $get['contact_phone'] !== '') {
            $query->where('contact_phone', 'like', "%{$get['contact_phone']}%");
        }
        
        // 执行分页查询
        $result = $query->order('sort desc, id desc')->paginate([
            'list_rows' => 20,
            'page' => $get['page'] ?? 1,
            'query' => $get, // 传递所有查询参数，确保分页链接包含搜索条件
            'path' => '/admin.html#/lala/employee/index.html', // 使用正确的路径
        ], false);
        
        // 格式化数据
        $list = $result->items();
        
        // 处理数据
        foreach ($list as &$item) {
            // 格式化时间
            if (!empty($item['create_at'])) {
                $item['create_at'] = date('Y-m-d H:i:s', strtotime($item['create_at']));
            }
            if (!empty($item['login_at'])) {
                $item['login_at'] = date('Y-m-d H:i:s', strtotime($item['login_at']));
            }
            
            // 格式化状态
            $item['status_text'] = $this->getStatusText($item['status']);
            
            // 格式化头像
            if (!empty($item['headimg'])) {
                $item['headimg_url'] = $item['headimg'];
            } else {
                $item['headimg_url'] = '/static/theme/img/avatar.png';
            }
            
            // 格式化用户类型
            $item['usertype_text'] = $this->getUserTypeText($item['usertype']);
        }
        
        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get,
            'status_list' => [
                '0' => '禁用',
                '1' => '启用'
            ],
            'usertype_list' => [
                'admin' => '管理员',
                'user' => '普通用户',
                'vip' => 'VIP用户'
            ]
        ]);
        
        // 渲染视图
        return $this->fetch();
    }
    
    /**
     * 获取状态文本
     * @param int $status
     * @return string
     */
    private function getStatusText($status)
    {
        $statusMap = [
            0 => '禁用',
            1 => '启用'
        ];
        return $statusMap[$status] ?? '未知';
    }
    
    /**
     * 获取用户类型文本
     * @param string $usertype
     * @return string
     */
    private function getUserTypeText($usertype)
    {
        $typeMap = [
            'admin' => '管理员',
            'user' => '普通用户',
            'vip' => 'VIP用户'
        ];
        return $typeMap[$usertype] ?? '未知';
    }
    
    /**
     * 下载员工数据
     * @auth true
     */
    public function download()
    {
        // 获取请求参数
        $get = $this->request->get();
        
        // 创建查询对象
        $query = Db::name('system_user');
        
        // 添加基础条件：只查询未删除的记录
        $query->where('is_deleted', 0);
        
        // 添加搜索条件
        if (isset($get['username']) && $get['username'] !== '') {
            $query->where('username', 'like', "%{$get['username']}%");
        }
        if (isset($get['nickname']) && $get['nickname'] !== '') {
            $query->where('nickname', 'like', "%{$get['nickname']}%");
        }
        if (isset($get['usertype']) && $get['usertype'] !== '') {
            $query->where('usertype', $get['usertype']);
        }
        if (isset($get['status']) && $get['status'] !== '') {
            $query->where('status', $get['status']);
        }
        if (isset($get['contact_phone']) && $get['contact_phone'] !== '') {
            $query->where('contact_phone', 'like', "%{$get['contact_phone']}%");
        }
        
        // 获取所有数据（不分页）
        $list = $query->order('sort desc, id desc')->select()->toArray();
        
        // 处理数据
        foreach ($list as &$item) {
            // 格式化时间
            if (!empty($item['create_at'])) {
                $item['create_at'] = date('Y-m-d H:i:s', strtotime($item['create_at']));
            }
            if (!empty($item['login_at'])) {
                $item['login_at'] = date('Y-m-d H:i:s', strtotime($item['login_at']));
            }
            
            // 格式化状态和用户类型
            $item['status_text'] = $this->getStatusText($item['status']);
            $item['usertype_text'] = $this->getUserTypeText($item['usertype']);
        }
        
        // 设置CSV文件名
        $filename = '员工数据_' . date('Y-m-d_H-i-s') . '.csv';
        
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
            '用户名',
            '昵称',
            '用户类型',
            '手机号',
            '邮箱',
            'QQ',
            '状态',
            '登录次数',
            '最后登录IP',
            '最后登录时间',
            '创建时间',
            '备注说明'
        ];
        echo implode(',', $headers) . "\n";
        
        // 输出数据
        foreach ($list as $item) {
            $row = [
                $this->escapeCsvField("=\"" . ($item['id'] ?? '') . "\""), // 强制文本格式
                $this->escapeCsvField($item['username'] ?? ''),
                $this->escapeCsvField($item['nickname'] ?? ''),
                $this->escapeCsvField($item['usertype_text'] ?? ''),
                $this->escapeCsvField($item['contact_phone'] ?? ''),
                $this->escapeCsvField($item['contact_mail'] ?? ''),
                $this->escapeCsvField($item['contact_qq'] ?? ''),
                $this->escapeCsvField($item['status_text'] ?? ''),
                $this->escapeCsvField("=\"" . ($item['login_num'] ?? '0') . "\""), // 强制文本格式
                $this->escapeCsvField($item['login_ip'] ?? ''),
                $this->escapeCsvField("=\"" . ($item['login_at'] ?? '') . "\""), // 强制文本格式
                $this->escapeCsvField("=\"" . ($item['create_at'] ?? '') . "\""), // 强制文本格式
                $this->escapeCsvField($item['describe'] ?? '')
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
