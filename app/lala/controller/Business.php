<?php
declare (strict_types = 1);

namespace app\lala\controller;

use think\admin\Controller;
use think\facade\Db;
use app\lala\const\Enum;
use app\lala\const\EnumTool;
use think\facade\Log;
use think\facade\Validate;

/**
 * 业务配置管理
 */
class Business extends Controller
{
    /**
     * 业务配置管理
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title = '业务配置管理';
        
        // 获取请求参数
        $get = $this->request->get();
        
        // 创建查询对象
        $query = Db::name('business');
        
        // 添加搜索条件
        if (isset($get['name']) && $get['name'] !== '') {
            $query->where('name', 'like', "%{$get['name']}%");
        }
        if (isset($get['route_mode']) && $get['route_mode'] !== '') {
            $query->where('route_mode', 'like', "%{$get['route_mode']}%");
        }
        if (isset($get['status']) && $get['status'] !== '') {
            $query->where('status', $get['status']);
        }
        
        // 执行分页查询
        $result = $query->order('id desc')->paginate([
            'list_rows' => 20,
            'page' => $get['page'] ?? 1,
        ], false);
        
        // 格式化数据
        $list = $result->items();
        
        // 处理时间
        foreach ($list as &$item) {
            if (!empty($item['created_at'])) {
                $item['created_at'] = date('Y-m-d H:i:s', strtotime($item['created_at']) + 8 * 3600);
            }
        }
        
        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get,
            'route_modes' => EnumTool::getRouteModes()
        ]);
        
        // 渲染视图
        return $this->fetch();
    }
    
    /**
     * 添加业务配置
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add()
    {
        // 记录日志
        Log::info('Business add method called');
        
        // 如果是POST请求，处理表单提交
        if ($this->request->isPost()) {
            // 记录日志
            Log::info('Business add POST request received');
            
            // 获取表单数据
            $data = $this->request->post();
            Log::info('Form data: ' . json_encode($data));
            
            // 数据验证
            $validate = Validate::rule([
                'name' => 'require|max:100',
                'route_mode' => 'require|in:' . Enum::ROUTE_MODE_AUTO . ',' . Enum::ROUTE_MODE_FIXED,
                'business_sign_key' => 'require|max:255',
                'status' => 'require|in:0,1'
            ])->message([
                'name.require' => '业务名称不能为空',
                'name.max' => '业务名称最多100个字符',
                'route_mode.require' => '路由模式不能为空',
                'route_mode.in' => '路由模式不正确',
                'business_sign_key.require' => '签名私钥不能为空',
                'business_sign_key.max' => '签名私钥最多255个字符',
                'status.require' => '状态不能为空',
                'status.in' => '状态值不正确'
            ]);
            
            // 验证数据
            if (!$validate->check($data)) {
                $error = $validate->getError();
                Log::error('Validation error: ' . $error);
                return $this->error($error);
            }
            
            try {
                // 设置默认值
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
                
                // 插入数据
                $result = Db::name('business')->insert($data);
                Log::info('Insert result: ' . ($result ? 'success' : 'failed'));
                
                if ($result) {
                    // 使用 JSON 响应而不是 success 方法
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
        
        // 分配变量到视图
        $this->assign('route_modes', EnumTool::getRouteModes());
        
        // 渲染添加表单
        return $this->fetch('form');
    }

    /**
     * 编辑业务配置
     * @auth true
     */
    public function edit()
    {
        // 获取ID
        $id = $this->request->get('id');
        
        // 如果是POST请求，处理表单提交
        if ($this->request->isPost()) {
            // 获取表单数据
            $data = $this->request->post();
            
            // 数据验证
            $validate = Validate::rule([
                'name' => 'require|max:100',
                'route_mode' => 'require|in:' . Enum::ROUTE_MODE_AUTO . ',' . Enum::ROUTE_MODE_FIXED,
                'business_sign_key' => 'require|max:255',
                'status' => 'require|in:0,1'
            ])->message([
                'name.require' => '业务名称不能为空',
                'name.max' => '业务名称最多100个字符',
                'route_mode.require' => '路由模式不能为空',
                'route_mode.in' => '路由模式不正确',
                'business_sign_key.require' => '签名私钥不能为空',
                'business_sign_key.max' => '签名私钥最多255个字符',
                'status.require' => '状态不能为空',
                'status.in' => '状态值不正确'
            ]);
            
            // 验证数据
            if (!$validate->check($data)) {
                return $this->error($validate->getError());
            }
            
            // 设置更新时间
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // 更新数据
            $result = Db::name('business')->where('id', $id)->update($data);
            
            if ($result !== false) {
                // 使用 JSON 响应而不是 success 方法
                return json(['code' => 1, 'info' => '更新成功', 'url' => '']);
            } else {
                return $this->error('更新失败');
            }
        }
        
        // 获取业务配置信息
        $vo = Db::name('business')->where('id', $id)->find();
        
        // 分配变量到视图
        $this->assign([
            'vo' => $vo,
            'route_modes' => EnumTool::getRouteModes()
        ]);
        
        // 渲染编辑表单
        return $this->fetch('form');
    }

    /**
     * 修改业务配置状态
     * @auth true
     */
    public function state()
    {
        // 获取ID和状态
        $id = $this->request->get('id');
        $status = $this->request->get('status');
        
        // 更新状态
        $result = Db::name('business')->where('id', $id)->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
        
        if ($result !== false) {
            // 使用 JSON 响应而不是 success 方法
            return json(['code' => 1, 'info' => '状态更新成功']);
        } else {
            return $this->error('状态更新失败');
        }
    }

    /**
     * 删除业务配置
     * @auth true
     */
    public function remove()
    {
        // 获取ID
        $id = $this->request->get('id');
        
        // 删除数据
        $result = Db::name('business')->where('id', $id)->delete();
        
        if ($result) {
            // 使用 JSON 响应而不是 success 方法
            return json(['code' => 1, 'info' => '删除成功']);
        } else {
            return $this->error('删除失败');
        }
    }
} 