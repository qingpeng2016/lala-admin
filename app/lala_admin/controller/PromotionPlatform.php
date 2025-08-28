<?php
declare (strict_types = 1);

namespace app\lala_admin\controller;

use think\admin\Controller;
use think\facade\Db;
use app\lala_admin\model\PromotionPlatform as PromotionPlatformModel;

/**
 * 推广平台管理
 */
class PromotionPlatform extends Controller
{
    /**
     * 推广平台列表
     * @auth true
     * @menu true
     */
    public function index()
    {
        $this->title = '推广平台管理';
        
        // 获取请求参数
        $get = $this->request->get();
        
        // 创建查询对象
        $query = Db::name('system_new_promotion_platforms');
        
        // 添加搜索条件
        if (isset($get['platform_name']) && $get['platform_name'] !== '') {
            $query->where('platform_name', 'like', "%{$get['platform_name']}%");
        }
        if (isset($get['channel']) && $get['channel'] !== '') {
            $query->where('channel', 'like', "%{$get['channel']}%");
        }
        if (isset($get['status']) && $get['status'] !== '') {
            $query->where('status', $get['status']);
        }
        
        // 执行分页查询
        $result = $query->order('id desc')->paginate([
            'list_rows' => 20,
            'page' => $get['page'] ?? 1,
            'query' => $get,
            'path' => '/admin.html#/lala_admin/promotion_platform/index.html',
        ], false);
        
        // 格式化数据
        $list = $result->items();
        foreach ($list as &$item) {
            // 格式化时间
            if (!empty($item['created_at'])) {
                $item['created_at'] = date('Y-m-d H:i:s', strtotime($item['created_at']));
            }
            if (!empty($item['updated_at'])) {
                $item['updated_at'] = date('Y-m-d H:i:s', strtotime($item['updated_at']));
            }
            
            // 格式化状态
            $statusList = PromotionPlatformModel::getStatusList();
            $item['status_text'] = $statusList[$item['status']] ?? $item['status'];
            
            // 格式化配置
            if (!empty($item['config'])) {
                $config = json_decode($item['config'], true);
                $item['config_preview'] = is_array($config) ? json_encode($config, JSON_UNESCAPED_UNICODE) : $item['config'];
            }
        }
        
        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get,
            'status_list' => PromotionPlatformModel::getStatusList()
        ]);
        
        // 渲染视图
        return $this->fetch();
    }

    /**
     * 添加推广平台
     * @auth true
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            // 验证数据
            if (empty($data['platform_name'])) {
                return json(['code' => 0, 'info' => '平台名称不能为空']);
            }
            if (empty($data['channel'])) {
                return json(['code' => 0, 'info' => '频道/群组不能为空']);
            }
            
            // 处理配置数据
            if (!empty($data['config'])) {
                try {
                    json_decode($data['config'], true);
                    $data['config'] = json_encode(json_decode($data['config'], true), JSON_UNESCAPED_UNICODE);
                } catch (\Exception $e) {
                    return json(['code' => 0, 'info' => '配置格式错误，请输入有效的JSON格式']);
                }
            }
            
            // 设置默认状态
            if (empty($data['status'])) {
                $data['status'] = 'active';
            }
            
            try {
                $id = Db::name('system_new_promotion_platforms')->insertGetId($data);
                if ($id) {
                    return json(['code' => 1, 'info' => '添加成功', 'url' => '']);
                } else {
                    return json(['code' => 0, 'info' => '添加失败']);
                }
            } catch (\Exception $e) {
                return json(['code' => 0, 'info' => '添加失败：' . $e->getMessage()]);
            }
        }
        
        $this->title = '添加推广平台';
        $this->assign([
            'status_list' => PromotionPlatformModel::getStatusList()
        ]);
        return $this->fetch('form');
    }

    /**
     * 编辑推广平台
     * @auth true
     */
    public function edit()
    {
        $id = $this->request->param('id');
        if (empty($id)) {
            return json(['code' => 0, 'info' => '参数错误']);
        }
        
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            // 验证数据
            if (empty($data['platform_name'])) {
                return json(['code' => 0, 'info' => '平台名称不能为空']);
            }
            if (empty($data['channel'])) {
                return json(['code' => 0, 'info' => '频道/群组不能为空']);
            }
            
            // 处理配置数据
            if (!empty($data['config'])) {
                try {
                    json_decode($data['config'], true);
                    $data['config'] = json_encode(json_decode($data['config'], true), JSON_UNESCAPED_UNICODE);
                } catch (\Exception $e) {
                    return json(['code' => 0, 'info' => '配置格式错误，请输入有效的JSON格式']);
                }
            }
            
            try {
                $result = Db::name('system_new_promotion_platforms')->where('id', $id)->update($data);
                if ($result !== false) {
                    return json(['code' => 1, 'info' => '更新成功', 'url' => '']);
                } else {
                    return json(['code' => 0, 'info' => '更新失败']);
                }
            } catch (\Exception $e) {
                return json(['code' => 0, 'info' => '更新失败：' . $e->getMessage()]);
            }
        }
        
        // 获取数据
        $info = Db::name('system_new_promotion_platforms')->where('id', $id)->find();
        if (!$info) {
            return json(['code' => 0, 'info' => '数据不存在']);
        }
        
        // 解析配置
        if (!empty($info['config'])) {
            $info['config'] = json_decode($info['config'], true);
        }
        
        $this->title = '编辑推广平台';
        $this->assign([
            'vo' => $info,  // 改为vo，与Business.php保持一致
            'status_list' => PromotionPlatformModel::getStatusList()
        ]);
        return $this->fetch('form');
    }

    /**
     * 删除推广平台
     * @auth true
     */
    public function delete()
    {
        $id = $this->request->param('id');
        if (empty($id)) {
            return json(['code' => 0, 'info' => '参数错误']);
        }
        
        try {
            $result = Db::name('system_new_promotion_platforms')->where('id', $id)->delete();
            if ($result) {
                return json(['code' => 1, 'info' => '删除成功']);
            } else {
                return json(['code' => 0, 'info' => '删除失败']);
            }
        } catch (\Exception $e) {
            return json(['code' => 0, 'info' => '删除失败：' . $e->getMessage()]);
        }
    }

    /**
     * 更新状态
     * @auth true
     */
    public function updateStatus()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status');
        
        if (empty($id) || empty($status)) {
            return json(['code' => 0, 'info' => '参数错误']);
        }
        
        try {
            $result = Db::name('system_new_promotion_platforms')->where('id', $id)->update(['status' => $status]);
            if ($result !== false) {
                return json(['code' => 1, 'info' => '状态更新成功']);
            } else {
                return json(['code' => 0, 'info' => '状态更新失败']);
            }
        } catch (\Exception $e) {
            return json(['code' => 0, 'info' => '状态更新失败：' . $e->getMessage()]);
        }
    }
}
