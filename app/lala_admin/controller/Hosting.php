<?php
declare (strict_types = 1);

namespace app\lala_admin\controller;

use think\admin\Controller;
use think\facade\Db;

/**
 * 持有商品管理
 */
class Hosting extends Controller
{
    /**
     * 持有商品列表
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title = '持有商品管理';
        
        // 获取请求参数
        $get = $this->request->get();
        
        // 创建查询对象
        $query = Db::name('tblhosting')->field('tblhosting.*');
        
        // 添加搜索条件
        if (isset($get['id']) && $get['id'] !== '') {
            $query->where('id', $get['id']);
        }
        if (isset($get['userid']) && $get['userid'] !== '') {
            $query->where('userid', $get['userid']);
        }
        if (isset($get['email']) && $get['email'] !== '') {
            // 通过邮箱关联查询tblclients表
            $query->join('tblclients c', 'tblhosting.userid = c.id')
                  ->field('tblhosting.*, c.email')
                  ->where('c.email', 'like', "%{$get['email']}%");
        }
        if (isset($get['domainstatus']) && $get['domainstatus'] !== '') {
            $query->where('domainstatus', $get['domainstatus']);
        }
        
        // 执行分页查询
        $result = $query->order('tblhosting.id desc')->paginate([
            'list_rows' => 20,
            'page' => $get['page'] ?? 1,
            'query' => $get, // 传递所有查询参数，确保分页链接包含搜索条件
            'path' => '/admin.html#/lala/hosting/index.html', // 使用正确的路径
        ], false);
        
        // 格式化数据
        $list = $result->items();
        
        // 处理数据
        foreach ($list as &$item) {
            // 格式化时间
            if (!empty($item['regdate'])) {
                $item['regdate'] = date('Y-m-d', strtotime($item['regdate']));
            }
            if (!empty($item['nextduedate'])) {
                $item['nextduedate'] = date('Y-m-d', strtotime($item['nextduedate']));
            }
            if (!empty($item['nextinvoicedate'])) {
                $item['nextinvoicedate'] = date('Y-m-d', strtotime($item['nextinvoicedate']));
            }
            if (!empty($item['created_at'])) {
                $item['created_at'] = date('Y-m-d H:i:s', strtotime($item['created_at']));
            }
            
            // 格式化状态
            $item['domainstatus_text'] = $this->getDomainStatusText($item['domainstatus']);
            
            // 格式化金额
            $item['firstpaymentamount_formatted'] = number_format(floatval($item['firstpaymentamount'] ?? 0), 2);
            $item['amount_formatted'] = number_format(floatval($item['amount'] ?? 0), 2);
            
            // 格式化磁盘使用率
            if ($item['disklimit'] > 0) {
                $item['disk_usage_percent'] = round(($item['diskusage'] / $item['disklimit']) * 100, 2);
            } else {
                $item['disk_usage_percent'] = 0;
            }
            
            // 格式化带宽使用率
            if ($item['bwlimit'] > 0) {
                $item['bw_usage_percent'] = round(($item['bwusage'] / $item['bwlimit']) * 100, 2);
            } else {
                $item['bw_usage_percent'] = 0;
            }
        }
        
        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get,
            'domainstatus_list' => [
                'Pending' => '待处理',
                'Active' => '活跃',
                'Suspended' => '已暂停',
                'Terminated' => '已终止',
                'Cancelled' => '已取消',
                'Fraud' => '欺诈',
                'Completed' => '已完成'
            ]
        ]);
        
        // 渲染视图
        return $this->fetch();
    }
    
    /**
     * 获取域名状态文本
     * @param string $status
     * @return string
     */
    private function getDomainStatusText($status)
    {
        $statusMap = [
            'Pending' => '待处理',
            'Active' => '活跃',
            'Suspended' => '已暂停',
            'Terminated' => '已终止',
            'Cancelled' => '已取消',
            'Fraud' => '欺诈',
            'Completed' => '已完成'
        ];
        return $statusMap[$status] ?? '未知';
    }
}
