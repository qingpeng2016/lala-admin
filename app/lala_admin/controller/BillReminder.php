<?php
declare (strict_types=1);

namespace app\lala_admin\controller;

use think\admin\Controller;
use think\facade\Db;

/**
 * 账单提醒管理
 */
class BillReminder extends Controller
{
    /**
     * 账单提醒列表
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title = '账单提醒管理';
        
        // 获取请求参数
        $get = $this->request->get();
        
        // 创建查询对象
        $query = Db::name('system_new_tblhosting_notes');
        
        // 添加搜索条件
        if (isset($get['userid']) && $get['userid'] !== '') {
            $query->where('userid', $get['userid']);
        }
        if (isset($get['email']) && $get['email'] !== '') {
            $query->where('email', 'like', "%{$get['email']}%");
        }
        if (isset($get['employee_name']) && $get['employee_name'] !== '') {
            $query->where('employee_name', 'like', "%{$get['employee_name']}%");
        }
        if (isset($get['invoice_id']) && $get['invoice_id'] !== '') {
            $query->where('invoice_id', $get['invoice_id']);
        }
        
        // 日期范围搜索
        if (isset($get['start_date']) && $get['start_date'] !== '') {
            $query->where('created_at', '>=', $get['start_date'] . ' 00:00:00');
        }
        if (isset($get['end_date']) && $get['end_date'] !== '') {
            $query->where('created_at', '<=', $get['end_date'] . ' 23:59:59');
        }
        
        // 执行分页查询
        $result = $query->order('id desc')->paginate([
            'list_rows' => 20,
            'page' => $get['page'] ?? 1,
            'query' => $get,
            'path' => '/admin.html#/lala_admin/bill_reminder/index.html',
        ], false);
        
        // 格式化数据
        $list = $result->items();
        
        // 处理数据
        foreach ($list as &$item) {
            // 格式化时间
            if (!empty($item['created_at'])) {
                $item['created_at_formatted'] = date('Y-m-d H:i:s', strtotime($item['created_at']));
            }
            if (!empty($item['updated_at'])) {
                $item['updated_at_formatted'] = date('Y-m-d H:i:s', strtotime($item['updated_at']));
            }
            
            // 格式化商品信息显示
            if (!empty($item['product_info'])) {
                $item['product_info_formatted'] = nl2br(htmlspecialchars($item['product_info']));
            }
            
            // 格式化备注内容显示
            if (!empty($item['content'])) {
                $item['content_formatted'] = nl2br(htmlspecialchars($item['content']));
            }
            
            // 获取发票状态
            if (!empty($item['invoice_id'])) {
                $invoice = Db::name('tblinvoices')->where('id', $item['invoice_id'])->find();
                $item['invoice_status'] = $invoice['status'] ?? '未知';
                $item['invoice_total'] = $invoice['total'] ?? '0.00';
            } else {
                $item['invoice_status'] = '无发票';
                $item['invoice_total'] = '0.00';
            }
        }
        
        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get
        ]);
        
        // 渲染视图
        return $this->fetch();
    }
    
    /**
     * 删除账单提醒
     * @auth true
     */
    public function delete()
    {
        if ($this->request->isPost()) {
            $id = $this->request->post('id');
            
            if (empty($id)) {
                return json(['code' => 0, 'msg' => '参数错误']);
            }
            
            try {
                $result = Db::name('system_new_tblhosting_notes')->where('id', $id)->delete();
                
                if ($result) {
                    return json(['code' => 1, 'msg' => '删除成功']);
                } else {
                    return json(['code' => 0, 'msg' => '删除失败']);
                }
            } catch (\Exception $e) {
                return json(['code' => 0, 'msg' => '删除失败：' . $e->getMessage()]);
            }
        }
        
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }
    
    /**
     * 批量删除账单提醒
     * @auth true
     */
    public function batchDelete()
    {
        if ($this->request->isPost()) {
            $ids = $this->request->post('ids');
            
            if (empty($ids) || !is_array($ids)) {
                return json(['code' => 0, 'msg' => '参数错误']);
            }
            
            try {
                $result = Db::name('system_new_tblhosting_notes')->whereIn('id', $ids)->delete();
                
                if ($result) {
                    return json(['code' => 1, 'msg' => "成功删除 {$result} 条记录"]);
                } else {
                    return json(['code' => 0, 'msg' => '删除失败']);
                }
            } catch (\Exception $e) {
                return json(['code' => 0, 'msg' => '删除失败：' . $e->getMessage()]);
            }
        }
        
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }
}
