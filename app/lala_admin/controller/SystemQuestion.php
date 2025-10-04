<?php
declare (strict_types=1);

namespace app\lala_admin\controller;

use think\admin\Controller;
use think\facade\Db;
use think\facade\Log;

/**
 * 问答管理
 */
class SystemQuestion extends Controller
{
    /**
     * 问答列表
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title = '问答管理';

        // 记录日志
        $this->app->log->info('SystemQuestion index method called');

        // 获取请求参数
        $get = $this->request->get();
        $this->app->log->info('Request parameters: ' . json_encode($get));

        $query = Db::name('system_question');
        $this->app->log->info('Query object created');

        // 添加搜索条件
        if (isset($get['id']) && $get['id'] !== '') {
            $query->where('id', $get['id']);
            $this->app->log->info('Added id condition: ' . $get['id']);
        }
        if (isset($get['type']) && $get['type'] !== '') {
            $query->where('type', $get['type']);
            $this->app->log->info('Added type condition: ' . $get['type']);
        }
        if (isset($get['category']) && $get['category'] !== '') {
            $query->where('category', $get['category']);
            $this->app->log->info('Added category condition: ' . $get['category']);
        }
        if (isset($get['keyword']) && $get['keyword'] !== '') {
            $query->where(function($q) use ($get) {
                $q->whereOr('title', 'like', "%{$get['keyword']}%")
                  ->whereOr('problem_description', 'like', "%{$get['keyword']}%")
                  ->whereOr('solution_description', 'like', "%{$get['keyword']}%");
            });
            $this->app->log->info('Added keyword condition: ' . $get['keyword']);
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
            'path' => '/admin.html#/lala_admin/system_question/index.html', // 使用正确的路径
        ], false);
        $this->app->log->info('Pagination query executed, total: ' . $result->total());

        // 格式化数据
        $list = $result->items();

        foreach ($list as &$item) {
            // 处理NULL值，避免htmlentities错误
            $item['category'] = $item['category'] ?? '';
            $item['problem_description'] = $item['problem_description'] ?? '';
            $item['solution_description'] = $item['solution_description'] ?? '';
            
            // 截取问题描述用于列表显示
            $item['problem_short'] = mb_substr(strip_tags($item['problem_description']), 0, 100);
            if (mb_strlen(strip_tags($item['problem_description'])) > 100) {
                $item['problem_short'] .= '...';
            }
            
            // 截取解决方案用于列表显示
            $item['solution_short'] = mb_substr(strip_tags($item['solution_description']), 0, 100);
            if (mb_strlen(strip_tags($item['solution_description'])) > 100) {
                $item['solution_short'] .= '...';
            }
            
            // 处理时间
            if (!empty($item['created_at'])) {
                $item['created_at'] = date('Y-m-d H:i:s', strtotime($item['created_at']));
            }
            if (!empty($item['updated_at'])) {
                $item['updated_at'] = date('Y-m-d H:i:s', strtotime($item['updated_at']));
            }
        }

        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get,
            'type_list' => $this->getTypeList(),
            'category_list' => $this->getCategoryList()
        ]);
        $this->app->log->info('Variables assigned to view');

        // 渲染视图
        $this->app->log->info('Rendering view');
        return $this->fetch();
    }

    /**
     * 添加问答
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add()
    {
        // 记录日志
        Log::info('SystemQuestion add method called');
        
        // 如果是POST请求，处理表单提交
        if ($this->request->isPost()) {
            // 记录日志
            Log::info('SystemQuestion add POST request received');
            
            // 获取表单数据
            $data = $this->request->post();
            Log::info('Form data: ' . json_encode($data));
            
            // 数据验证
            $validate = \think\facade\Validate::rule([
                'type' => 'require|in:ticket,startup,vip_issue',
                'title' => 'require|max:255',
                'problem_description' => 'require',
                'solution_description' => 'require',
                'category' => 'max:100'
            ])->message([
                'type.require' => '问题类型不能为空',
                'type.in' => '问题类型必须是ticket、startup或vip_issue',
                'title.require' => '问题标题不能为空',
                'title.max' => '问题标题最多255个字符',
                'problem_description.require' => '问题描述不能为空',
                'solution_description.require' => '解决方案不能为空',
                'category.max' => '分类最多100个字符'
            ]);
            
            // 验证数据
            if (!$validate->check($data)) {
                $error = $validate->getError();
                Log::error('Validation error: ' . $error);
                return $this->error($error);
            }
            
            try {
                // 设置默认字段
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
                
                // 插入数据
                $result = Db::name('system_question')->insert($data);
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
        
        // 分配变量到视图
        $this->assign([
            'type_list' => $this->getTypeList(),
            'category_list' => $this->getCategoryList()
        ]);
        
        // 渲染添加表单
        return $this->fetch('form');
    }

    /**
     * 编辑问答
     * @auth true
     */
    public function edit()
    {
        Log::info('SystemQuestion edit method called');
        
        if ($this->request->isPost()) {
            Log::info('SystemQuestion edit POST request received');
            
            // 获取表单数据
            $data = $this->request->post();
            Log::info('Form data: ' . json_encode($data));
            
            // 数据验证
            $validate = \think\facade\Validate::rule([
                'id' => 'require|integer|gt:0',
                'type' => 'require|in:ticket,startup,vip_issue',
                'title' => 'require|max:255',
                'problem_description' => 'require',
                'solution_description' => 'require',
                'category' => 'max:100'
            ])->message([
                'id.require' => 'ID不能为空',
                'id.integer' => 'ID必须是整数',
                'id.gt' => 'ID必须大于0',
                'type.require' => '问题类型不能为空',
                'type.in' => '问题类型必须是ticket、startup或vip_issue',
                'title.require' => '问题标题不能为空',
                'title.max' => '问题标题最多255个字符',
                'problem_description.require' => '问题描述不能为空',
                'solution_description.require' => '解决方案不能为空',
                'category.max' => '分类最多100个字符'
            ]);
            
            if (!$validate->check($data)) {
                $error = $validate->getError();
                Log::error('Validation error: ' . $error);
                return $this->error($error);
            }
            
            try {
                // 处理数据
                $updateData = [
                    'type' => $data['type'],
                    'category' => $data['category'],
                    'title' => $data['title'],
                    'problem_description' => $data['problem_description'],
                    'solution_description' => $data['solution_description'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // 更新数据
                $result = Db::name('system_question')
                    ->where('id', $data['id'])
                    ->update($updateData);
                Log::info('Update result: ' . ($result !== false ? 'success' : 'failed'));
                
                if ($result !== false) {
                    return json(['code' => 1, 'info' => '更新成功', 'url' => '']);
                } else {
                    return $this->error('更新失败或问答不存在');
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
        
        $vo = Db::name('system_question')
            ->where('id', $id)
            ->find();
        if (empty($vo)) {
            return $this->error('问答不存在');
        }
        
        $this->assign([
            'vo' => $vo,
            'type_list' => $this->getTypeList(),
            'category_list' => $this->getCategoryList()
        ]);
        return $this->fetch('form');
    }

    /**
     * 查看问答详情
     * @auth true
     */
    public function view()
    {
        Log::info('SystemQuestion view method called');
        
        // 获取要查看的数据
        $id = $this->request->get('id');
        if (empty($id)) {
            return $this->error('参数错误');
        }
        
        $vo = Db::name('system_question')
            ->where('id', $id)
            ->find();
        if (empty($vo)) {
            return $this->error('问答不存在');
        }
        
        // 处理NULL值
        $vo['category'] = $vo['category'] ?? '';
        $vo['problem_description'] = $vo['problem_description'] ?? '';
        $vo['solution_description'] = $vo['solution_description'] ?? '';
        
        // 处理时间
        if (!empty($vo['created_at'])) {
            $vo['created_at'] = date('Y-m-d H:i:s', strtotime($vo['created_at']));
        }
        if (!empty($vo['updated_at'])) {
            $vo['updated_at'] = date('Y-m-d H:i:s', strtotime($vo['updated_at']));
        }
        
        $this->assign([
            'vo' => $vo,
            'type_list' => $this->getTypeList(),
            'category_list' => $this->getCategoryList()
        ]);
        return $this->fetch('view');
    }

    /**
     * 删除问答
     * @auth true
     */
    public function remove()
    {
        Log::info('SystemQuestion remove method called');
        
        $id = $this->request->post('id');
        if (empty($id)) {
            return $this->error('参数错误');
        }
        
        try {
            $result = Db::name('system_question')
                ->where('id', $id)
                ->delete();
            Log::info('Delete result: ' . ($result ? 'success' : 'failed'));
            
            if ($result) {
                return json(['code' => 1, 'info' => '删除成功']);
            } else {
                return $this->error('删除失败或问答不存在');
            }
        } catch (\Exception $e) {
            Log::error('Exception in remove method: ' . $e->getMessage());
            return $this->error('删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取类型列表
     * @return array
     */
    private function getTypeList()
    {
        return [
            'ticket' => '工单处理',
            'startup' => '开机处理',
            'vip_issue' => '大户问题'
        ];
    }

    /**
     * 获取分类列表
     * @return array
     */
    private function getCategoryList()
    {
        return [
            'network' => '网络问题',
            'hardware' => '硬件问题',
            'software' => '软件问题',
            'account' => '账户问题',
            'billing' => '账单问题',
            'service' => '服务问题',
            'other' => '其他问题'
        ];
    }
}
