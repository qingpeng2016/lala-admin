<?php
declare (strict_types=1);

namespace app\lala_admin\controller;

use think\admin\Controller;
use think\facade\Db;
use think\facade\Log;

/**
 * 上游商家管理
 */
class Providers extends Controller
{
    /**
     * 商家列表
     * @auth true
     * @menu true
     */
    public function index()
    {
        $this->title = '上游商家管理';

        // 获取请求参数
        $get = $this->request->get();
        $query = Db::name('system_new_providers');

        // 添加搜索条件
        if (isset($get['provider_name']) && $get['provider_name'] !== '') {
            $query->where('provider_name', 'like', "%{$get['provider_name']}%");
        }
        if (isset($get['contact_person']) && $get['contact_person'] !== '') {
            $query->where('contact_person', 'like', "%{$get['contact_person']}%");
        }
        if (isset($get['contact_phone']) && $get['contact_phone'] !== '') {
            $query->where('contact_phone', 'like', "%{$get['contact_phone']}%");
        }
        if (isset($get['supply_products']) && $get['supply_products'] !== '') {
            $query->where('supply_products', 'like', "%{$get['supply_products']}%");
        }

        // 执行分页查询
        $result = $query->order('id desc')->paginate([
            'list_rows' => isset($get['pageSize']) ? intval($get['pageSize']) : 20,
            'page' => isset($get['page']) ? intval($get['page']) : 1,
            'query' => $get,
            'path' => '/admin.html#/lala_admin/providers/index.html',
        ], false);

        // 格式化数据
        $list = $result->items();
        foreach ($list as &$item) {
            $item['provider_name'] = $item['provider_name'] ?? '';
            $item['supply_products'] = $item['supply_products'] ?? '';
            $item['contact_person'] = $item['contact_person'] ?? '';
            $item['contact_phone'] = $item['contact_phone'] ?? '';
            $item['contact_email'] = $item['contact_email'] ?? '';
            $item['admin_info'] = $item['admin_info'] ?? '';
            $item['tg_group'] = $item['tg_group'] ?? '';
            $item['wx_group'] = $item['wx_group'] ?? '';
            $item['remarks'] = $item['remarks'] ?? '';
        }

        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get
        ]);

        return $this->fetch();
    }

    /**
     * 添加商家
     * @auth true
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            // 数据验证
            $validate = \think\facade\Validate::rule([
                'provider_name' => 'require|max:128',
                'supply_products' => '',
                'contact_person' => 'max:64',
                'contact_phone' => 'max:32',
                'contact_email' => 'email|max:128',
                'admin_info' => '',
                'tg_group' => 'max:255',
                'wx_group' => 'max:255',
                'remarks' => 'max:255'
            ])->message([
                'provider_name.require' => '供应商名称不能为空',
                'provider_name.max' => '供应商名称最多128个字符',
                'contact_person.max' => '联系人姓名最多64个字符',
                'contact_phone.max' => '联系人电话最多32个字符',
                'contact_email.email' => '联系人邮箱格式不正确',
                'contact_email.max' => '联系人邮箱最多128个字符',
                'tg_group.max' => 'Telegram群组最多255个字符',
                'wx_group.max' => '微信群组最多255个字符',
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
                $result = Db::name('system_new_providers')->insert($data);
                
                if ($result) {
                    return json(['code' => 1, 'info' => '添加成功', 'url' => '']);
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('添加失败: ' . $e->getMessage());
            }
        }
        
        return $this->fetch('form');
    }

    /**
     * 编辑商家
     * @auth true
     */
    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            // 数据验证
            $validate = \think\facade\Validate::rule([
                'id' => 'require|integer|gt:0',
                'provider_name' => 'require|max:128',
                'supply_products' => '',
                'contact_person' => 'max:64',
                'contact_phone' => 'max:32',
                'contact_email' => 'email|max:128',
                'admin_info' => '',
                'tg_group' => 'max:255',
                'wx_group' => 'max:255',
                'remarks' => 'max:255'
            ])->message([
                'id.require' => 'ID不能为空',
                'id.integer' => 'ID必须是整数',
                'id.gt' => 'ID必须大于0',
                'provider_name.require' => '供应商名称不能为空',
                'provider_name.max' => '供应商名称最多128个字符',
                'contact_person.max' => '联系人姓名最多64个字符',
                'contact_phone.max' => '联系人电话最多32个字符',
                'contact_email.email' => '联系人邮箱格式不正确',
                'contact_email.max' => '联系人邮箱最多128个字符',
                'tg_group.max' => 'Telegram群组最多255个字符',
                'wx_group.max' => '微信群组最多255个字符',
                'remarks.max' => '备注最多255个字符'
            ]);
            
            if (!$validate->check($data)) {
                return $this->error($validate->getError());
            }
            
            try {
                // 处理数据
                $updateData = [
                    'provider_name' => $data['provider_name'],
                    'supply_products' => $data['supply_products'],
                    'contact_person' => $data['contact_person'],
                    'contact_phone' => $data['contact_phone'],
                    'contact_email' => $data['contact_email'],
                    'admin_info' => $data['admin_info'],
                    'tg_group' => $data['tg_group'],
                    'wx_group' => $data['wx_group'],
                    'remarks' => $data['remarks'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // 更新数据
                $result = Db::name('system_new_providers')
                    ->where('id', $data['id'])
                    ->update($updateData);
                
                if ($result !== false) {
                    return json(['code' => 1, 'info' => '更新成功', 'url' => '']);
                } else {
                    return $this->error('更新失败或商家不存在');
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
        
        $vo = Db::name('system_new_providers')
            ->where('id', $id)
            ->find();
        if (empty($vo)) {
            return $this->error('商家不存在');
        }
        
        $this->assign(['vo' => $vo]);
        return $this->fetch('form');
    }

    /**
     * 删除商家
     * @auth true
     */
    public function remove()
    {
        $id = $this->request->post('id');
        if (empty($id)) {
            return $this->error('参数错误');
        }
        
        try {
            $result = Db::name('system_new_providers')
                ->where('id', $id)
                ->delete();
            
            if ($result) {
                return json(['code' => 1, 'info' => '删除成功']);
            } else {
                return $this->error('删除失败或商家不存在');
            }
        } catch (\Exception $e) {
            return $this->error('删除失败: ' . $e->getMessage());
        }
    }
}
