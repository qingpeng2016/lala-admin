<?php
declare (strict_types = 1);

namespace app\lala_admin\controller;

use think\admin\Controller;
use think\facade\Db;

/**
 * 客户管理
 */
class Client extends Controller
{
    /**
     * 客户列表
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title = '客户管理';
        
        // 获取请求参数
        $get = $this->request->get();
        
        // 创建查询对象
        $query = Db::name('tblclients');
        
        // 添加搜索条件
        if (isset($get['firstname']) && $get['firstname'] !== '') {
            $query->where('firstname', 'like', "%{$get['firstname']}%");
        }
        if (isset($get['lastname']) && $get['lastname'] !== '') {
            $query->where('lastname', 'like', "%{$get['lastname']}%");
        }
        if (isset($get['companyname']) && $get['companyname'] !== '') {
            $query->where('companyname', 'like', "%{$get['companyname']}%");
        }
        if (isset($get['email']) && $get['email'] !== '') {
            $query->where('email', 'like', "%{$get['email']}%");
        }
        if (isset($get['phonenumber']) && $get['phonenumber'] !== '') {
            $query->where('phonenumber', 'like', "%{$get['phonenumber']}%");
        }
        if (isset($get['status']) && $get['status'] !== '') {
            $query->where('status', $get['status']);
        }
        if (isset($get['country']) && $get['country'] !== '') {
            $query->where('country', 'like', "%{$get['country']}%");
        }
        
        // 执行分页查询
        $result = $query->order('id desc')->paginate([
            'list_rows' => 20,
            'page' => $get['page'] ?? 1,
            'query' => $get, // 传递所有查询参数，确保分页链接包含搜索条件
            'path' => '/admin.html#/lala/client/index.html', // 使用正确的路径
        ], false);
        
        // 格式化数据
        $list = $result->items();
        
        // 处理数据
        foreach ($list as &$item) {
            // 格式化时间
            if (!empty($item['datecreated'])) {
                $item['datecreated'] = date('Y-m-d', strtotime($item['datecreated']));
            }
            if (!empty($item['lastlogin'])) {
                $item['lastlogin'] = date('Y-m-d H:i:s', strtotime($item['lastlogin']));
            }
            if (!empty($item['created_at'])) {
                $item['created_at'] = date('Y-m-d H:i:s', strtotime($item['created_at']));
            }
            if (!empty($item['updated_at'])) {
                $item['updated_at'] = date('Y-m-d H:i:s', strtotime($item['updated_at']));
            }
            
            // 格式化状态
            $item['status_text'] = $this->getStatusText($item['status']);
            
            // 格式化完整姓名
            $item['fullname'] = trim($item['firstname'] . ' ' . $item['lastname']);
            
            // 格式化完整地址
            $address_parts = [];
            if (!empty($item['address1'])) $address_parts[] = $item['address1'];
            if (!empty($item['address2'])) $address_parts[] = $item['address2'];
            if (!empty($item['city'])) $address_parts[] = $item['city'];
            if (!empty($item['state'])) $address_parts[] = $item['state'];
            if (!empty($item['postcode'])) $address_parts[] = $item['postcode'];
            if (!empty($item['country'])) $address_parts[] = $item['country'];
            $item['full_address'] = implode(', ', $address_parts);
            
            // 格式化信用额度
            $item['credit_formatted'] = number_format($item['credit'], 2);
        }
        
        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get,
            'status_list' => [
                'Active' => '活跃',
                'Inactive' => '非活跃',
                'Closed' => '已关闭'
            ]
        ]);
        
        // 渲染视图
        return $this->fetch();
    }
    
    /**
     * 获取状态文本
     * @param string $status
     * @return string
     */
    private function getStatusText($status)
    {
        $statusMap = [
            'Active' => '活跃',
            'Inactive' => '非活跃',
            'Closed' => '已关闭'
        ];
        return $statusMap[$status] ?? '未知';
    }
    
    /**
     * 下载客户数据
     * @auth true
     */
    public function download()
    {
        // 获取请求参数
        $get = $this->request->get();
        
        // 创建查询对象
        $query = Db::name('tblclients');
        
        // 添加搜索条件
        if (isset($get['firstname']) && $get['firstname'] !== '') {
            $query->where('firstname', 'like', "%{$get['firstname']}%");
        }
        if (isset($get['lastname']) && $get['lastname'] !== '') {
            $query->where('lastname', 'like', "%{$get['lastname']}%");
        }
        if (isset($get['companyname']) && $get['companyname'] !== '') {
            $query->where('companyname', 'like', "%{$get['companyname']}%");
        }
        if (isset($get['email']) && $get['email'] !== '') {
            $query->where('email', 'like', "%{$get['email']}%");
        }
        if (isset($get['phonenumber']) && $get['phonenumber'] !== '') {
            $query->where('phonenumber', 'like', "%{$get['phonenumber']}%");
        }
        if (isset($get['status']) && $get['status'] !== '') {
            $query->where('status', $get['status']);
        }
        if (isset($get['country']) && $get['country'] !== '') {
            $query->where('country', 'like', "%{$get['country']}%");
        }
        
        // 获取所有数据（不分页）
        $list = $query->order('id desc')->select()->toArray();
        
        // 处理数据
        foreach ($list as &$item) {
            // 格式化时间
            if (!empty($item['datecreated'])) {
                $item['datecreated'] = date('Y-m-d', strtotime($item['datecreated']));
            }
            if (!empty($item['lastlogin'])) {
                $item['lastlogin'] = date('Y-m-d H:i:s', strtotime($item['lastlogin']));
            }
            if (!empty($item['created_at'])) {
                $item['created_at'] = date('Y-m-d H:i:s', strtotime($item['created_at']));
            }
            if (!empty($item['updated_at'])) {
                $item['updated_at'] = date('Y-m-d H:i:s', strtotime($item['updated_at']));
            }
            
            // 格式化状态
            $item['status_text'] = $this->getStatusText($item['status']);
            
            // 格式化完整姓名
            $item['fullname'] = trim($item['firstname'] . ' ' . $item['lastname']);
            
            // 格式化完整地址
            $address_parts = [];
            if (!empty($item['address1'])) $address_parts[] = $item['address1'];
            if (!empty($item['address2'])) $address_parts[] = $item['address2'];
            if (!empty($item['city'])) $address_parts[] = $item['city'];
            if (!empty($item['state'])) $address_parts[] = $item['state'];
            if (!empty($item['postcode'])) $address_parts[] = $item['postcode'];
            if (!empty($item['country'])) $address_parts[] = $item['country'];
            $item['full_address'] = implode(', ', $address_parts);
            
            // 格式化信用额度
            $item['credit_formatted'] = number_format($item['credit'], 2);
        }
        
        // 设置CSV文件名
        $filename = '客户数据_' . date('Y-m-d_H-i-s') . '.csv';
        
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
            'UUID',
            '姓名',
            '公司名称',
            '邮箱',
            '电话',
            '完整地址',
            '国家',
            '信用额度',
            '状态',
            '注册日期',
            '最后登录时间',
            '最后登录IP',
            '创建时间',
            '备注'
        ];
        echo implode(',', $headers) . "\n";
        
        // 输出数据
        foreach ($list as $item) {
            $row = [
                $this->escapeCsvField("=\"" . ($item['id'] ?? '') . "\""), // 强制文本格式
                $this->escapeCsvField($item['uuid'] ?? ''),
                $this->escapeCsvField($item['fullname'] ?? ''),
                $this->escapeCsvField($item['companyname'] ?? ''),
                $this->escapeCsvField($item['email'] ?? ''),
                $this->escapeCsvField($item['phonenumber'] ?? ''),
                $this->escapeCsvField($item['full_address'] ?? ''),
                $this->escapeCsvField($item['country'] ?? ''),
                $this->escapeCsvField("=\"" . ($item['credit_formatted'] ?? '0.00') . "\""), // 强制文本格式
                $this->escapeCsvField($item['status_text'] ?? ''),
                $this->escapeCsvField("=\"" . ($item['datecreated'] ?? '') . "\""), // 强制文本格式
                $this->escapeCsvField("=\"" . ($item['lastlogin'] ?? '') . "\""), // 强制文本格式
                $this->escapeCsvField($item['ip'] ?? ''),
                $this->escapeCsvField("=\"" . ($item['created_at'] ?? '') . "\""), // 强制文本格式
                $this->escapeCsvField($item['notes'] ?? '')
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
