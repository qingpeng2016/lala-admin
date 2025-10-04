<?php
declare (strict_types = 1);

namespace app\lala_admin\controller;

use think\admin\Controller;
use think\facade\Db;
use app\lala_admin\model\SystemQuestion as SystemQuestionModel;

/**
 * 问题答案管理
 */
class SystemQuestion extends Controller
{
    /**
     * 问题列表
     * @auth true
     * @menu true
     */
    public function index()
    {
        $this->title = '问题答案管理';
        
        // 获取请求参数
        $get = $this->request->get();
        
        // 创建查询对象
        $query = Db::name('system_question');
        
        // 添加搜索条件
        if (isset($get['type']) && $get['type'] !== '') {
            $query->where('type', $get['type']);
        }
        if (isset($get['category']) && $get['category'] !== '') {
            $query->where('category', $get['category']);
        }
        if (isset($get['keyword']) && $get['keyword'] !== '') {
            $query->where(function($q) use ($get) {
                $q->whereOr('title', 'like', "%{$get['keyword']}%")
                  ->whereOr('problem_description', 'like', "%{$get['keyword']}%")
                  ->whereOr('solution_description', 'like', "%{$get['keyword']}%");
            });
        }
        
        // 执行分页查询
        $result = $query->order('created_at desc')->paginate([
            'list_rows' => 20,
            'page' => $get['page'] ?? 1,
            'query' => $get,
            'path' => '/admin.html#/lala_admin/system_question/index.html',
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
            
            // 格式化类型和分类
            $item['type_text'] = SystemQuestionModel::getTypeText($item['type']);
            $item['category_text'] = SystemQuestionModel::getCategoryText($item['category']);
            
            // 截取问题描述用于列表显示
            $item['problem_short'] = mb_substr(strip_tags($item['problem_description']), 0, 100) . '...';
            $item['solution_short'] = mb_substr(strip_tags($item['solution_description']), 0, 100) . '...';
        }
        
        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get,
            'type_list' => SystemQuestionModel::getTypeList(),
            'category_list' => SystemQuestionModel::getCategoryList()
        ]);
        
        // 渲染视图
        return $this->fetch();
    }

    /**
     * 添加问题
     * @auth true
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            // 验证数据
            if (empty($data['type'])) {
                $this->error('问题类型不能为空');
            }
            if (empty($data['title'])) {
                $this->error('问题标题不能为空');
            }
            if (empty($data['problem_description'])) {
                $this->error('问题描述不能为空');
            }
            if (empty($data['solution_description'])) {
                $this->error('解决方案不能为空');
            }
            
            try {
                $id = Db::name('system_question')->insertGetId($data);
                if ($id) {
                    return json(['code' => 1, 'info' => '添加成功', 'url' => '']);
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('添加失败：' . $e->getMessage());
            }
        }
        
        $this->title = '添加问题';
        
        $this->assign([
            'type_list' => SystemQuestionModel::getTypeList(),
            'category_list' => SystemQuestionModel::getCategoryList()
        ]);
        
        return $this->fetch('form');
    }

    /**
     * 编辑问题
     * @auth true
     */
    public function edit()
    {
        $id = $this->request->param('id');
        if (empty($id)) {
            $this->error('参数错误');
        }
        
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            // 验证数据
            if (empty($data['type'])) {
                $this->error('问题类型不能为空');
            }
            if (empty($data['title'])) {
                $this->error('问题标题不能为空');
            }
            if (empty($data['problem_description'])) {
                $this->error('问题描述不能为空');
            }
            if (empty($data['solution_description'])) {
                $this->error('解决方案不能为空');
            }
            
            try {
                $result = Db::name('system_question')->where('id', $id)->update($data);
                if ($result !== false) {
                    return json(['code' => 1, 'info' => '更新成功', 'url' => '']);
                } else {
                    return $this->error('更新失败');
                }
            } catch (\Exception $e) {
                return $this->error('更新失败：' . $e->getMessage());
            }
        }
        
        $this->title = '编辑问题';
        
        // 获取问题详情
        $vo = Db::name('system_question')->where('id', $id)->find();
        if (!$vo) {
            $this->error('问题不存在');
        }
        
        $this->assign([
            'vo' => $vo,
            'type_list' => SystemQuestionModel::getTypeList(),
            'category_list' => SystemQuestionModel::getCategoryList()
        ]);
        
        return $this->fetch('form');
    }

    /**
     * 删除问题
     * @auth true
     */
    public function delete()
    {
        if ($this->request->isPost()) {
            $id = $this->request->post('id');
            if (empty($id)) {
                return $this->error('参数错误');
            }
            
            try {
                $result = Db::name('system_question')->where('id', $id)->delete();
                if ($result) {
                    return json(['code' => 1, 'info' => '删除成功']);
                } else {
                    return $this->error('删除失败');
                }
            } catch (\Exception $e) {
                return $this->error('删除失败：' . $e->getMessage());
            }
        }
        
        return $this->error('请求方式错误');
    }

    /**
     * 查看问题详情
     * @auth true
     */
    public function view()
    {
        $id = $this->request->param('id');
        if (empty($id)) {
            $this->error('参数错误');
        }
        
        $this->title = '问题详情';
        
        // 获取问题详情
        $vo = Db::name('system_question')->where('id', $id)->find();
        if (!$vo) {
            $this->error('问题不存在');
        }
        
        // 格式化数据
        $vo['type_text'] = SystemQuestionModel::getTypeText($vo['type']);
        $vo['category_text'] = SystemQuestionModel::getCategoryText($vo['category']);
        if (!empty($vo['created_at'])) {
            $vo['created_at'] = date('Y-m-d H:i:s', strtotime($vo['created_at']));
        }
        if (!empty($vo['updated_at'])) {
            $vo['updated_at'] = date('Y-m-d H:i:s', strtotime($vo['updated_at']));
        }
        
        // 获取相关问题
        $related_questions = SystemQuestionModel::getRelatedQuestions($vo['type'], $vo['category'], 5);
        
        $this->assign([
            'vo' => $vo,
            'related_questions' => $related_questions
        ]);
        
        return $this->fetch();
    }

    /**
     * 批量删除
     * @auth true
     */
    public function batchDelete()
    {
        if ($this->request->isPost()) {
            $ids = $this->request->post('ids');
            if (empty($ids) || !is_array($ids)) {
                return $this->error('请选择要删除的记录');
            }
            
            try {
                $result = Db::name('system_question')->whereIn('id', $ids)->delete();
                if ($result) {
                    return json(['code' => 1, 'info' => '批量删除成功']);
                } else {
                    return $this->error('批量删除失败');
                }
            } catch (\Exception $e) {
                return $this->error('批量删除失败：' . $e->getMessage());
            }
        }
        
        return $this->error('请求方式错误');
    }
}
