<?php
declare (strict_types = 1);

namespace app\lala\controller;

use think\admin\Controller;
use think\facade\Db;

/**
 * 用户协议管理
 */
class UserAgreements extends Controller
{
    /**
     * 用户协议列表
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title = '用户协议管理';
        
        // 获取请求参数
        $get = $this->request->get();
        
        // 创建查询对象
        $query = Db::name('realname_agreements');
        
        // 添加搜索条件
        if (isset($get['user_id']) && $get['user_id'] !== '') {
            $query->where('user_id', 'like', "%{$get['user_id']}%");
        }
        if (isset($get['agreement_type']) && $get['agreement_type'] !== '') {
            $query->where('agreement_type', $get['agreement_type']);
        }
        if (isset($get['agreement_version']) && $get['agreement_version'] !== '') {
            $query->where('agreement_version', 'like', "%{$get['agreement_version']}%");
        }
        if (isset($get['ip_address']) && $get['ip_address'] !== '') {
            $query->where('ip_address', 'like', "%{$get['ip_address']}%");
        }
        if (isset($get['status']) && $get['status'] !== '') {
            $query->where('status', $get['status']);
        }
        
        // 执行分页查询
        $result = $query->order('id desc')->paginate([
            'list_rows' => 20,
            'page' => $get['page'] ?? 1,
            'query' => $get, // 传递所有查询参数，确保分页链接包含搜索条件
            'path' => '/admin.html#/lala/user_agreements/index.html', // 使用正确的路径
        ], false);
        
        // 格式化数据
        $list = $result->items();
        
        // 处理时间
        foreach ($list as &$item) {
            if (!empty($item['sign_time'])) {
                $item['sign_time'] = date('Y-m-d H:i:s', strtotime($item['sign_time']));
            }
            // 生成浏览器指纹
            if (!empty($item['user_agent'])) {
                $item['browser_fingerprint'] = $this->generateBrowserFingerprint($item['user_agent']);
            } else {
                $item['browser_fingerprint'] = '';
            }
        }
        
        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get,
            'agreement_type_list' => [
                'user_agreement' => '用户协议',
                'privacy_policy' => '隐私政策',
                'service_terms' => '服务条款',
                'data_consent' => '数据授权'
            ],
            'status_list' => [
                0 => '未签署',
                1 => '已签署',
                2 => '已撤销'
            ]
        ]);
        
        // 渲染视图
        return $this->fetch();
    }
    
    /**
     * 生成浏览器指纹
     * @param string $userAgent
     * @return string
     */
    private function generateBrowserFingerprint($userAgent)
    {
        // 多特征指纹算法
        $components = [
            $userAgent,
            $this->request->header('Accept-Language', ''),
            $this->request->header('Accept-Encoding', ''),
            $this->request->header('Accept', ''),
            $this->request->header('User-Agent', ''),
            $this->request->ip(),
            date('Y-m-d'), // 添加日期作为组件
        ];
        
        // 生成哈希
        $fingerprint = $this->hashString(implode('|', $components));
        
        // 返回32位指纹字符串
        return substr($fingerprint, 0, 32);
    }
    
    /**
     * 字符串哈希算法
     * @param string $str
     * @return string
     */
    private function hashString($str)
    {
        // 使用多种哈希算法组合
        $hash1 = md5($str);
        $hash2 = sha1($str);
        $hash3 = hash('sha256', $str);
        
        // 组合哈希值
        $combined = $hash1 . $hash2 . $hash3;
        
        // 再次哈希
        return md5($combined);
    }
    
    /**
     * 获取协议内容
     * @auth true
     */
    public function getAgreementContent()
    {
        $id = $this->request->param('id');
        if (!$id) {
            $this->error('参数错误');
        }
        
        $info = Db::name('realname_agreements')->where('id', $id)->find();
        if (!$info) {
            $this->error('数据不存在');
        }
        
        $this->success('获取成功', '', ['content' => $info['agreement_content']]);
    }
} 