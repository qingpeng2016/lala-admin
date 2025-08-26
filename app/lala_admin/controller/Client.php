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
        if (isset($get['id']) && $get['id'] !== '') {
            $query->where('id', $get['id']);
        }
        if (isset($get['email']) && $get['email'] !== '') {
            $query->where('email', 'like', "%{$get['email']}%");
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
            $item['credit_formatted'] = number_format(floatval($item['credit'] ?? 0), 2);
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

}
