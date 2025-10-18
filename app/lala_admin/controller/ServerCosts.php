<?php
declare (strict_types=1);

namespace app\lala_admin\controller;

use think\admin\Controller;
use think\facade\Db;
use think\facade\Log;

/**
 * 上游服务器管理
 */
class ServerCosts extends Controller
{
    /**
     * 服务器列表
     * @auth true
     * @menu true
     */
    public function index()
    {
        $this->title = '上游服务器管理';

        // 获取请求参数
        $get = $this->request->get();
        $query = Db::name('system_new_server_costs');

        // 添加搜索条件
        if (isset($get['provider_name']) && $get['provider_name'] !== '') {
            $query->where('provider_name', 'like', "%{$get['provider_name']}%");
        }
        if (isset($get['region']) && $get['region'] !== '') {
            $query->where('region', 'like', "%{$get['region']}%");
        }
        if (isset($get['product_name']) && $get['product_name'] !== '') {
            $query->where('product_name', 'like', "%{$get['product_name']}%");
        }

        // 执行分页查询
        $result = $query->order('id desc')->paginate([
            'list_rows' => isset($get['pageSize']) ? intval($get['pageSize']) : 20,
            'page' => isset($get['page']) ? intval($get['page']) : 1,
            'query' => $get,
            'path' => '/admin.html#/lala_admin/server_costs/index.html',
        ], false);

        // 格式化数据
        $list = $result->items();
        foreach ($list as &$item) {
            $item['provider_name'] = $item['provider_name'] ?? '';
            $item['region'] = $item['region'] ?? '';
            $item['product_name'] = $item['product_name'] ?? '';
            // 处理textarea字段中的<br />标签，转换为换行符以便正确显示
            $item['hardware_info'] = isset($item['hardware_info']) ? str_replace(['<br />', '<br>', '<br/>'], "\n", $item['hardware_info']) : '';
            $item['network_info'] = isset($item['network_info']) ? str_replace(['<br />', '<br>', '<br/>'], "\n", $item['network_info']) : '';
            $item['host_login_info'] = isset($item['host_login_info']) ? str_replace(['<br />', '<br>', '<br/>'], "\n", $item['host_login_info']) : '';
            $item['remarks'] = isset($item['remarks']) ? str_replace(['<br />', '<br>', '<br/>'], "\n", $item['remarks']) : '';
            $item['os_type'] = $item['os_type'] ?? '';
            $item['startup_mode'] = $item['startup_mode'] ?? '';
            $item['price'] = $item['price'] ?? 0;
            $item['billing_model'] = $item['billing_model'] ?? '';
            $item['billing_period'] = $item['billing_period'] ?? '';
            $item['end_date'] = $item['end_date'] ?? '';
        }

        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get,
            'os_type_list' => $this->getOsTypeList(),
            'startup_mode_list' => $this->getStartupModeList(),
            'billing_model_list' => $this->getBillingModelList(),
            'billing_period_list' => $this->getBillingPeriodList()
        ]);

        return $this->fetch();
    }

    /**
     * 添加服务器
     * @auth true
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            // 数据验证
            $validate = \think\facade\Validate::rule([
                'provider_name' => 'require|max:128',
                'region' => 'max:128',
                'product_name' => 'max:128',
                'os_type' => 'max:64',
                'startup_mode' => 'max:64',
                'price' => 'float',
                'billing_model' => 'max:64',
                'billing_period' => 'max:64',
                'end_date' => 'date'
            ])->message([
                'provider_name.require' => '上游名称不能为空',
                'provider_name.max' => '上游名称最多128个字符',
                'region.max' => '地区最多128个字符',
                'product_name.max' => '产品名称最多128个字符',
                'os_type.max' => '操作系统最多64个字符',
                'startup_mode.max' => '开机方式最多64个字符',
                'price.float' => '价格必须是数字',
                'billing_model.max' => '计费模式最多64个字符',
                'billing_period.max' => '付费周期最多64个字符',
                'end_date.date' => '到期日期格式不正确'
            ]);
            
            if (!$validate->check($data)) {
                return $this->error($validate->getError());
            }
            
            try {
                // 设置默认字段
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
                
                // 插入数据
                $result = Db::name('system_new_server_costs')->insert($data);
                
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
            'os_type_list' => $this->getOsTypeList(),
            'startup_mode_list' => $this->getStartupModeList(),
            'billing_model_list' => $this->getBillingModelList(),
            'billing_period_list' => $this->getBillingPeriodList()
        ]);
        
        return $this->fetch('form');
    }

    /**
     * 编辑服务器
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
                'region' => 'max:128',
                'product_name' => 'max:128',
                'os_type' => 'max:64',
                'startup_mode' => 'max:64',
                'price' => 'float',
                'billing_model' => 'max:64',
                'billing_period' => 'max:64',
                'end_date' => 'date'
            ])->message([
                'id.require' => 'ID不能为空',
                'id.integer' => 'ID必须是整数',
                'id.gt' => 'ID必须大于0',
                'provider_name.require' => '上游名称不能为空',
                'provider_name.max' => '上游名称最多128个字符',
                'region.max' => '地区最多128个字符',
                'product_name.max' => '产品名称最多128个字符',
                'os_type.max' => '操作系统最多64个字符',
                'startup_mode.max' => '开机方式最多64个字符',
                'price.float' => '价格必须是数字',
                'billing_model.max' => '计费模式最多64个字符',
                'billing_period.max' => '付费周期最多64个字符',
                'end_date.date' => '到期日期格式不正确'
            ]);
            
            if (!$validate->check($data)) {
                return $this->error($validate->getError());
            }
            
            try {
                // 处理数据
                $updateData = [
                    'provider_name' => $data['provider_name'],
                    'region' => $data['region'],
                    'product_name' => $data['product_name'],
                    'hardware_info' => $data['hardware_info'],
                    'os_type' => $data['os_type'],
                    'startup_mode' => $data['startup_mode'],
                    'network_info' => $data['network_info'],
                    'host_login_info' => $data['host_login_info'],
                    'price' => $data['price'],
                    'billing_model' => $data['billing_model'],
                    'billing_period' => $data['billing_period'],
                    'end_date' => $data['end_date'],
                    'remarks' => $data['remarks'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // 更新数据
                $result = Db::name('system_new_server_costs')
                    ->where('id', $data['id'])
                    ->update($updateData);
                
                if ($result !== false) {
                    return json(['code' => 1, 'info' => '更新成功', 'url' => '']);
                } else {
                    return $this->error('更新失败或服务器不存在');
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
        
        $vo = Db::name('system_new_server_costs')
            ->where('id', $id)
            ->find();
        if (empty($vo)) {
            return $this->error('服务器不存在');
        }
        
        $this->assign([
            'vo' => $vo,
            'os_type_list' => $this->getOsTypeList(),
            'startup_mode_list' => $this->getStartupModeList(),
            'billing_model_list' => $this->getBillingModelList(),
            'billing_period_list' => $this->getBillingPeriodList()
        ]);
        return $this->fetch('form');
    }

    /**
     * 删除服务器
     * @auth true
     */
    public function remove()
    {
        $id = $this->request->post('id');
        if (empty($id)) {
            return $this->error('参数错误');
        }
        
        try {
            $result = Db::name('system_new_server_costs')
                ->where('id', $id)
                ->delete();
            
            if ($result) {
                return json(['code' => 1, 'info' => '删除成功']);
            } else {
                return $this->error('删除失败或服务器不存在');
            }
        } catch (\Exception $e) {
            return $this->error('删除失败: ' . $e->getMessage());
        }
    }


    /**
     * 获取操作系统列表
     * @return array
     */
    private function getOsTypeList()
    {
        return [
            'proxmox_ve' => 'Proxmox VE',
            'centos' => 'CentOS',
            'ubuntu' => 'Ubuntu',
            'windows' => 'Windows',
            'debian' => 'Debian',
            'almalinux' => 'AlmaLinux',
            'rocky_linux' => 'Rocky Linux'
        ];
    }

    /**
     * 获取开机方式列表
     * @return array
     */
    private function getStartupModeList()
    {
        return [
            'manual' => '手动',
            'auto' => '自动'
        ];
    }

    /**
     * 获取计费模式列表
     * @return array
     */
    private function getBillingModelList()
    {
        return [
            'bandwidth_95' => '带宽95',
            'bandwidth_package' => '带宽包断'
        ];
    }

    /**
     * 获取付费周期列表
     * @return array
     */
    private function getBillingPeriodList()
    {
        return [
            'monthly' => '月度',
            'quarterly' => '季度',
            'semi_annual' => '半年',
            'annual' => '1年'
        ];
    }
}
