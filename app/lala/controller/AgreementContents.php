<?php
declare (strict_types = 1);

namespace app\lala\controller;

use think\admin\Controller;
use think\facade\Db;

/**
 * 协议内容管理
 */
class AgreementContents extends Controller
{
    /**
     * 协议内容列表
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title = '协议内容管理';
        
        // 获取请求参数
        $get = $this->request->get();
        
        // 创建查询对象
        $query = Db::name('realname_agreements_content');
        
        // 执行分页查询
        $result = $query->order('id desc')->paginate([
            'list_rows' => 20,
            'page' => $get['page'] ?? 1,
            'query' => $get, // 传递所有查询参数，确保分页链接包含搜索条件
            'path' => '/admin.html#/lala/agreement_contents/index.html', // 使用正确的路径
        ], false);
        
        // 格式化数据
        $list = $result->items();
        
        // 处理时间
        foreach ($list as &$item) {
            if (!empty($item['effective_date'])) {
                $item['effective_date'] = date('Y-m-d H:i:s', strtotime($item['effective_date']));
            }
            if (!empty($item['created_at'])) {
                $item['created_at'] = date('Y-m-d H:i:s', strtotime($item['created_at']));
            }
            if (!empty($item['updated_at'])) {
                $item['updated_at'] = date('Y-m-d H:i:s', strtotime($item['updated_at']));
            }
            // 截取协议内容预览
            if (!empty($item['content'])) {
                $item['content_preview'] = mb_substr(strip_tags($item['content']), 0, 100) . '...';
            } else {
                $item['content_preview'] = '';
            }
        }
        
        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get,
            'agreement_type_list' => [
                'user_agreement' => '用户协议',
                'privacy_policy' => '隐私政策'
            ],
            'status_list' => [
                1 => '激活',
                0 => '禁用'
            ]
        ]);
        
        // 渲染视图
        return $this->fetch();
    }



    /**
     * 编辑协议内容
     * @auth true
     * @menu false
     */
    public function edit()
    {
        $this->title = '编辑协议内容';
        
        $id = $this->request->param('id');
        if (!$id) {
            $this->error('参数错误');
        }
        
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            // 验证数据
            if (empty($data['content'])) {
                $this->error('请输入协议内容');
            }
            
            // 只更新协议内容字段
            $updateData = [
                'content' => $data['content'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // 更新数据
            $result = Db::name('realname_agreements_content')->where('id', $id)->update($updateData);
            
            if ($result !== false) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
        
        // 获取数据
        $info = Db::name('realname_agreements_content')->where('id', $id)->find();
        if (!$info) {
            $this->error('数据不存在');
        }
        
        // 分配变量到视图
        $this->assign([
            'info' => $info,
            'agreement_type_list' => [
                'user_agreement' => '用户协议',
                'privacy_policy' => '隐私政策'
            ]
        ]);
        
        return $this->fetch();
    }

    /**
     * 删除协议内容
     * @auth true
     * @menu false
     */
    public function delete()
    {
        $id = $this->request->param('id');
        if (!$id) {
            $this->error('参数错误');
        }
        
        $result = Db::name('realname_agreements_content')->where('id', $id)->delete();
        
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 查看协议内容详情
     * @auth true
     * @menu false
     */
    public function view()
    {
        $this->title = '协议内容详情';
        
        $id = $this->request->param('id');
        if (!$id) {
            $this->error('参数错误');
        }
        
        // 获取数据
        $info = Db::name('realname_agreements_content')->where('id', $id)->find();
        if (!$info) {
            $this->error('数据不存在');
        }
        
        // 格式化时间
        if (!empty($info['effective_date'])) {
            $info['effective_date'] = date('Y-m-d H:i:s', strtotime($info['effective_date']));
        }
        if (!empty($info['created_at'])) {
            $info['created_at'] = date('Y-m-d H:i:s', strtotime($info['created_at']));
        }
        if (!empty($info['updated_at'])) {
            $info['updated_at'] = date('Y-m-d H:i:s', strtotime($info['updated_at']));
        }
        
        // 分配变量到视图
        $this->assign([
            'info' => $info,
            'agreement_type_list' => [
                'user_agreement' => '用户协议',
                'privacy_policy' => '隐私政策'
            ],
            'status_list' => [
                1 => '激活',
                0 => '禁用'
            ]
        ]);
        
        return $this->fetch();
    }

    /**
     * 获取协议内容
     * @auth true
     * @menu false
     */
    public function getContent()
    {
        $id = $this->request->param('id');
        if (!$id) {
            $this->error('参数错误');
        }
        
        // 获取数据
        $info = Db::name('realname_agreements_content')->where('id', $id)->find();
        if (!$info) {
            $this->error('数据不存在');
        }
        
        $this->success('获取成功', '', ['content' => $info['content']]);
    }
} 