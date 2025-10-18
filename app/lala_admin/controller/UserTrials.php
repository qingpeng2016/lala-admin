<?php
declare (strict_types=1);

namespace app\lala_admin\controller;

use think\admin\Controller;
use think\facade\Db;
use think\facade\Log;

/**
 * 用户试用管理
 */
class UserTrials extends Controller
{
    /**
     * 试用列表
     * @auth true
     * @menu true
     */
    public function index()
    {
        $this->title = '用户试用管理';

        // 获取请求参数
        $get = $this->request->get();
        $query = Db::name('system_new_user_trials');

        // 添加搜索条件
        if (isset($get['user_id']) && $get['user_id'] !== '') {
            $query->where('user_id', $get['user_id']);
        }
        if (isset($get['server_id']) && $get['server_id'] !== '') {
            $query->where('server_id', $get['server_id']);
        }
        if (isset($get['product_name']) && $get['product_name'] !== '') {
            $query->where('product_name', 'like', "%{$get['product_name']}%");
        }
        if (isset($get['status']) && $get['status'] !== '') {
            $query->where('status', $get['status']);
        }
        if (isset($get['trial_start']) && $get['trial_start'] !== '') {
            $query->where('trial_start', '>=', $get['trial_start']);
        }
        if (isset($get['trial_end']) && $get['trial_end'] !== '') {
            $query->where('trial_end', '<=', $get['trial_end']);
        }

        // 执行分页查询
        $result = $query->order('id desc')->paginate([
            'list_rows' => isset($get['pageSize']) ? intval($get['pageSize']) : 20,
            'page' => isset($get['page']) ? intval($get['page']) : 1,
            'query' => $get,
            'path' => '/admin.html#/lala_admin/user_trials/index.html',
        ], false);

        // 格式化数据
        $list = $result->items();
        foreach ($list as &$item) {
            $item['user_id'] = $item['user_id'] ?? 0;
            $item['server_id'] = $item['server_id'] ?? 0;
            $item['product_name'] = $item['product_name'] ?? '';
            $item['trial_start'] = $item['trial_start'] ?? '';
            $item['trial_end'] = $item['trial_end'] ?? '';
            $item['status'] = $item['status'] ?? 'active';
            $item['remarks'] = $item['remarks'] ?? '';
            
            // 格式化时间
            if (!empty($item['trial_start'])) {
                $item['trial_start'] = date('Y-m-d H:i:s', strtotime($item['trial_start']));
            }
            if (!empty($item['trial_end'])) {
                $item['trial_end'] = date('Y-m-d H:i:s', strtotime($item['trial_end']));
            }
        }

        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get,
            'status_list' => $this->getStatusList()
        ]);

        return $this->fetch();
    }

    /**
     * 添加试用
     * @auth true
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            // 数据验证
            $validate = \think\facade\Validate::rule([
                'user_id' => 'require|integer|gt:0',
                'server_id' => 'require|integer|gt:0',
                'product_name' => 'max:128',
                'trial_start' => 'require|date',
                'trial_end' => 'date',
                'status' => 'in:active,ended,canceled',
                'remarks' => 'max:255'
            ])->message([
                'user_id.require' => '用户ID不能为空',
                'user_id.integer' => '用户ID必须是整数',
                'user_id.gt' => '用户ID必须大于0',
                'server_id.require' => '服务器ID不能为空',
                'server_id.integer' => '服务器ID必须是整数',
                'server_id.gt' => '服务器ID必须大于0',
                'product_name.max' => '产品名称最多128个字符',
                'trial_start.require' => '试用开始时间不能为空',
                'trial_start.date' => '试用开始时间格式不正确',
                'trial_end.date' => '试用结束时间格式不正确',
                'status.in' => '状态必须是active、ended或canceled',
                'remarks.max' => '备注最多255个字符'
            ]);
            
            if (!$validate->check($data)) {
                return $this->error($validate->getError());
            }
            
            try {
                // 设置默认字段
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
                
                // 插入数据
                $result = Db::name('system_new_user_trials')->insert($data);
                
                if ($result) {
                    return json(['code' => 1, 'info' => '添加成功', 'url' => '']);
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('添加失败: ' . $e->getMessage());
            }
        }
        
        // 分配变量到视图
        $this->assign([
            'status_list' => $this->getStatusList()
        ]);
        
        return $this->fetch('form');
    }

    /**
     * 编辑试用
     * @auth true
     */
    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            // 数据验证
            $validate = \think\facade\Validate::rule([
                'id' => 'require|integer|gt:0',
                'user_id' => 'require|integer|gt:0',
                'server_id' => 'require|integer|gt:0',
                'product_name' => 'max:128',
                'trial_start' => 'require|date',
                'trial_end' => 'date',
                'status' => 'in:active,ended,canceled',
                'remarks' => 'max:255'
            ])->message([
                'id.require' => 'ID不能为空',
                'id.integer' => 'ID必须是整数',
                'id.gt' => 'ID必须大于0',
                'user_id.require' => '用户ID不能为空',
                'user_id.integer' => '用户ID必须是整数',
                'user_id.gt' => '用户ID必须大于0',
                'server_id.require' => '服务器ID不能为空',
                'server_id.integer' => '服务器ID必须是整数',
                'server_id.gt' => '服务器ID必须大于0',
                'product_name.max' => '产品名称最多128个字符',
                'trial_start.require' => '试用开始时间不能为空',
                'trial_start.date' => '试用开始时间格式不正确',
                'trial_end.date' => '试用结束时间格式不正确',
                'status.in' => '状态必须是active、ended或canceled',
                'remarks.max' => '备注最多255个字符'
            ]);
            
            if (!$validate->check($data)) {
                return $this->error($validate->getError());
            }
            
            try {
                // 处理数据
                $updateData = [
                    'user_id' => $data['user_id'],
                    'server_id' => $data['server_id'],
                    'product_name' => $data['product_name'],
                    'trial_start' => $data['trial_start'],
                    'trial_end' => $data['trial_end'],
                    'status' => $data['status'],
                    'remarks' => $data['remarks'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // 更新数据
                $result = Db::name('system_new_user_trials')
                    ->where('id', $data['id'])
                    ->update($updateData);
                
                if ($result !== false) {
                    return json(['code' => 1, 'info' => '更新成功', 'url' => '']);
                } else {
                    return $this->error('更新失败或试用记录不存在');
                }
            } catch (\Exception $e) {
                return $this->error('更新失败: ' . $e->getMessage());
            }
        }
        
        // 获取要编辑的数据
        $id = $this->request->get('id');
        if (empty($id)) {
            return $this->error('参数错误');
        }
        
        $vo = Db::name('system_new_user_trials')
            ->where('id', $id)
            ->find();
        if (empty($vo)) {
            return $this->error('试用记录不存在');
        }
        
        $this->assign([
            'vo' => $vo,
            'status_list' => $this->getStatusList()
        ]);
        return $this->fetch('form');
    }

    /**
     * 删除试用
     * @auth true
     */
    public function remove()
    {
        $id = $this->request->post('id');
        if (empty($id)) {
            return $this->error('参数错误');
        }
        
        try {
            $result = Db::name('system_new_user_trials')
                ->where('id', $id)
                ->delete();
            
            if ($result) {
                return json(['code' => 1, 'info' => '删除成功']);
            } else {
                return $this->error('删除失败或试用记录不存在');
            }
        } catch (\Exception $e) {
            return $this->error('删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取状态列表
     * @return array
     */
    private function getStatusList()
    {
        return [
            'active' => '使用中',
            'ended' => '已结束',
            'canceled' => '取消'
        ];
    }
}
