<?php
declare (strict_types=1);

namespace app\lala_admin\controller;

use app\cool_pay\model\PaymentOrder as PaymentOrderModel;
use think\admin\Controller;
use think\facade\Db;
use think\facade\Log;
use app\cool_pay\const\EnumTool;
use app\cool_pay\const\Enum;

/**
 * 收款详情管理
 */
class IncomeDetail extends Controller
{
    /**
     * 收款列表
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title = '收款详情';

        // 记录日志
        $this->app->log->info('IncomeDetail index method called');

        // 获取请求参数
        $get = $this->request->get();
        $this->app->log->info('Request parameters: ' . json_encode($get));

        $query = Db::name('payment_order');
        $this->app->log->info('Query object created');

        // 默认添加收款条件
        $query->where('order_type', 'Deposit');
        $this->app->log->info('Added default order_type condition: Deposit');

        // 添加搜索条件
        if (isset($get['id']) && $get['id'] !== '') {
            $query->where('id', $get['id']);
            $this->app->log->info('Added id condition: ' . $get['id']);
        }
        if (isset($get['user_id']) && $get['user_id'] !== '') {
            $query->where('user_id', $get['user_id']);
            $this->app->log->info('Added user_id condition: ' . $get['user_id']);
        }
        if (isset($get['channel_id']) && $get['channel_id'] !== '') {
            $query->where('channel_id', $get['channel_id']);
            $this->app->log->info('Added channel_id condition: ' . $get['channel_id']);
        }
        if (isset($get['channel_name']) && $get['channel_name'] !== '') {
            $query->where('channel_name', 'like', "%{$get['channel_name']}%");
            $this->app->log->info('Added channel_name condition: ' . $get['channel_name']);
        }
        if (isset($get['payment_type']) && $get['payment_type'] !== '') {
            $query->where('payment_type', $get['payment_type']);
            $this->app->log->info('Added payment_type condition: ' . $get['payment_type']);
        }
        if (isset($get['business_order_no']) && $get['business_order_no'] !== '') {
            $query->where('business_order_no', 'like', "%{$get['business_order_no']}%");
            $this->app->log->info('Added business_order_no condition: ' . $get['business_order_no']);
        }
        if (isset($get['channel_order_no']) && $get['channel_order_no'] !== '') {
            $query->where('channel_order_no', 'like', "%{$get['channel_order_no']}%");
            $this->app->log->info('Added channel_order_no condition: ' . $get['channel_order_no']);
        }
        if (isset($get['order_status']) && $get['order_status'] !== '') {
            $query->where('order_status', $get['order_status']);
            $this->app->log->info('Added order_status condition: ' . $get['order_status']);
        }
        if (isset($get['date_range']) && $get['date_range'] !== '') {
            [$start_date, $end_date] = explode(' - ', $get['date_range']);
            $query->whereBetween('created_at', ["{$start_date} 00:00:00", "{$end_date} 23:59:59"]);
            $this->app->log->info('Added date_range condition: ' . $get['date_range']);
        }

        // 执行分页查询
        $this->app->log->info('Executing pagination query');
        $result = $query->order('id desc')->paginate([
            'list_rows' => isset($get['pageSize']) ? intval($get['pageSize']) : 20,
            'page' => isset($get['page']) ? intval($get['page']) : 1,
            'query' => $get, // 传递所有查询参数，确保分页链接包含搜索条件
            'path' => '/admin.html#/cool_pay/income_detail/index.html', // 使用正确的路径
        ], false);
        $this->app->log->info('Pagination query executed, total: ' . $result->total());

        // 格式化数据
        $list = $result->items();

        foreach ($list as &$item) {
            // 处理NULL值，避免htmlentities错误
            $item['channel_order_no'] = $item['channel_order_no'] ?? '';
            $item['business_extra'] = $item['business_extra'] ?? '';
            $item['channel_extra'] = $item['channel_extra'] ?? '';
            $item['remark'] = $item['remark'] ?? '';
            $item['notify_url'] = $item['notify_url'] ?? '';
            
            if ($item['payment_type'] === 'Bitcoin') {
                // 解析channel_extra字段
                $channelExtra = json_decode($item['channel_extra'], true);
                $item['bitcoin_name'] = $channelExtra['token'] ?? '--';
            } else {
                $item['bitcoin_name'] = '--';
            }
            
            // 处理时间
            if (!empty($item['created_at'])) {
                $item['created_at'] = date('Y-m-d H:i:s', strtotime($item['created_at']));
            }
            if (!empty($item['complete_time'])) {
                $item['complete_time'] = date('Y-m-d H:i:s', strtotime($item['complete_time']));
            }
        }

        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get,
            'order_statuses' => EnumTool::getOrderStatuses(),
            'payment_types' => EnumTool::getPaymentTypes()
        ]);
        $this->app->log->info('Variables assigned to view');

        // 渲染视图
        $this->app->log->info('Rendering view');
        return $this->fetch();
    }

    /**
     * 添加收款订单
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add()
    {
        // 记录日志
        Log::info('IncomeDetail add method called');
        
        // 如果是POST请求，处理表单提交
        if ($this->request->isPost()) {
            // 记录日志
            Log::info('IncomeDetail add POST request received');
            
            // 获取表单数据
            $data = $this->request->post();
            Log::info('Form data: ' . json_encode($data));
            
            // 数据验证
            $validate = \think\facade\Validate::rule([
                'payment_type' => 'require|in:Alipay,WechatPay,Bitcoin',
                'amount' => 'require|float|egt:0',
                'remark' => 'max:1000'
            ])->message([
                'payment_type.require' => '支付类型不能为空',
                'payment_type.in' => '支付类型必须是Alipay、WechatPay或Bitcoin',
                'amount.require' => '订单金额不能为空',
                'amount.float' => '订单金额必须是数字',
                'amount.egt' => '订单金额必须大于等于0',
                'remark.max' => '备注最多1000个字符'
            ]);
            
            // 验证数据
            if (!$validate->check($data)) {
                $error = $validate->getError();
                Log::error('Validation error: ' . $error);
                return $this->error($error);
            }
            
            try {
                // 设置写死的字段，默认为收款
                $data['channel_id'] = 0;
                $data['business_id'] = 3;
                $data['user_id'] = 0;
                $data['order_type'] = 'Deposit'; // 默认为收款
                $data['channel_name'] = $data['payment_type']; // 渠道名称等于支付类型
                $data['business_order_no'] = "";
                $data['channel_order_no'] = "";
                $data['currency'] = 'CNY';
                $data['pay_amount'] = $data['amount'];
                $data['origin_amount'] = $data['amount'];
                $data['fee_amount'] = 0;
                $data['expire_time'] = date('Y-m-d H:i:s');
                $data['complete_time'] = date('Y-m-d H:i:s');
                $data['return_url'] = "";
                $data['fail_return_url'] = "";
                $data['notify_url'] = "";
                $data['business_extra'] = "";
                $data['channel_extra'] = "";
                $data['order_status'] = "Success";
                $data['remark'] = isset($data['remark']) ? $data['remark'] : '';
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
                
                // 插入数据
                $result = Db::name('payment_order')->insert($data);
                Log::info('Insert result: ' . ($result ? 'success' : 'failed'));
                
                if ($result) {
                    // 返回JSON响应
                    return json(['code' => 1, 'info' => '添加成功', 'url' => '']);
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                Log::error('Exception in add method: ' . $e->getMessage());
                Log::error('Stack trace: ' . $e->getTraceAsString());
                return $this->error('添加失败: ' . $e->getMessage());
            }
        }
        
        // 分配变量到视图（简化版，不需要传递枚举数据）
        $this->assign([]);
        
        // 渲染添加表单
        return $this->fetch('form');
    }

    /**
     * 编辑收款订单
     * @auth true
     */
    public function edit()
    {
        Log::info('IncomeDetail edit method called');
        
        if ($this->request->isPost()) {
            Log::info('IncomeDetail edit POST request received');
            
            // 获取表单数据
            $data = $this->request->post();
            Log::info('Form data: ' . json_encode($data));
            
            // 数据验证
            $validate = \think\facade\Validate::rule([
                'id' => 'require|integer|gt:0',
                'payment_type' => 'require|in:Alipay,WechatPay,Bitcoin',
                'amount' => 'require|float|egt:0',
                'remark' => 'max:1000'
            ])->message([
                'id.require' => 'ID不能为空',
                'id.integer' => 'ID必须是整数',
                'id.gt' => 'ID必须大于0',
                'payment_type.require' => '支付类型不能为空',
                'payment_type.in' => '支付类型必须是Alipay、WechatPay或Bitcoin',
                'amount.require' => '订单金额不能为空',
                'amount.float' => '订单金额必须是数字',
                'amount.egt' => '订单金额必须大于等于0',
                'remark.max' => '备注最多1000个字符'
            ]);
            
            if (!$validate->check($data)) {
                $error = $validate->getError();
                Log::error('Validation error: ' . $error);
                return $this->error($error);
            }
            
            try {
                // 处理数据
                $updateData = [
                    'payment_type' => $data['payment_type'],
                    'channel_name' => $data['payment_type'], // 渠道名称等于支付类型
                    'amount' => $data['amount'],
                    'pay_amount' => $data['amount'],
                    'origin_amount' => $data['amount'],
                    'remark' => isset($data['remark']) ? $data['remark'] : '',
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // 更新数据
                $result = Db::name('payment_order')
                    ->where('id', $data['id'])
                    ->where('order_type', 'Deposit') // 确保只能编辑收款订单
                    ->update($updateData);
                Log::info('Update result: ' . ($result !== false ? 'success' : 'failed'));
                
                if ($result !== false) {
                    return json(['code' => 1, 'info' => '更新成功', 'url' => '']);
                } else {
                    return $this->error('更新失败或订单不存在');
                }
            } catch (\Exception $e) {
                Log::error('Exception in edit method: ' . $e->getMessage());
                return $this->error('更新失败: ' . $e->getMessage());
            }
        }
        
        // 获取要编辑的数据
        $id = $this->request->get('id');
        if (empty($id)) {
            return $this->error('参数错误');
        }
        
        $vo = Db::name('payment_order')
            ->where('id', $id)
            ->where('order_type', 'Deposit') // 确保只能编辑收款订单
            ->find();
        if (empty($vo)) {
            return $this->error('收款订单不存在');
        }
        
        $this->assign(['vo' => $vo]);
        return $this->fetch('form');
    }

    /**
     * 删除收款订单
     * @auth true
     */
    public function remove()
    {
        Log::info('IncomeDetail remove method called');
        
        $id = $this->request->post('id');
        if (empty($id)) {
            return $this->error('参数错误');
        }
        
        try {
            $result = Db::name('payment_order')
                ->where('id', $id)
                ->where('order_type', 'Deposit') // 确保只能删除收款订单
                ->delete();
            Log::info('Delete result: ' . ($result ? 'success' : 'failed'));
            
            if ($result) {
                return json(['code' => 1, 'info' => '删除成功']);
            } else {
                return $this->error('删除失败或收款订单不存在');
            }
        } catch (\Exception $e) {
            Log::error('Exception in remove method: ' . $e->getMessage());
            return $this->error('删除失败: ' . $e->getMessage());
        }
    }
}
