<?php
declare (strict_types=1);

namespace app\lala_admin\controller;

use think\admin\Controller;
use app\lala_admin\model\SalaryRule as SalaryRuleModel;
use think\facade\Db;

/**
 * 工资规则管理
 */
class SalaryRule extends Controller
{
    /**
     * 工资规则列表
     * @auth true
     * @menu true
     */
    public function index()
    {
        $this->title = '工资规则管理';
        
        // 直接获取所有工资规则数据
        $list = SalaryRuleModel::order('sort_order asc, id asc')->select()->toArray();
        
        // 处理数据
        foreach ($list as &$item) {
            $item['rule_type_text'] = SalaryRuleModel::getRuleTypeList()[$item['rule_type']] ?? $item['rule_type'];
            $item['unit_text'] = SalaryRuleModel::getUnitTypeList()[$item['unit']] ?? $item['unit'];
            $item['status_text'] = SalaryRuleModel::getStatusList()[$item['status']] ?? $item['status'];
        }
        
        // 获取提成规则数据
        $commission_new = $this->getCommissionRules('new');
        $commission_old = $this->getCommissionRules('old');
        
        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'commission_new' => $commission_new,
            'commission_old' => $commission_old
        ]);
        
        return $this->fetch();
    }
    
    /**
     * 添加工资规则
     * @auth true
     */
    public function add()
    {
        $this->title = '添加工资规则';
        
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            try {
                SalaryRuleModel::create($data);
                $this->success('添加成功', url('index'));
            } catch (\Exception $e) {
                $this->error('添加失败：' . $e->getMessage());
            }
        }
        
        $this->assign([
            'rule_type_list' => SalaryRuleModel::getRuleTypeList(),
            'unit_type_list' => SalaryRuleModel::getUnitTypeList(),
            'status_list' => SalaryRuleModel::getStatusList()
        ]);
        
        return $this->fetch('form');
    }
    
    /**
     * 编辑工资规则
     * @auth true
     */
    public function edit()
    {
        $this->title = '编辑工资规则';
        
        $id = $this->request->param('id');
        $vo = SalaryRuleModel::find($id);
        
        if (!$vo) {
            $this->error('记录不存在');
        }
        
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            try {
                $vo->save($data);
                $this->success('编辑成功', url('index'));
            } catch (\Exception $e) {
                $this->error('编辑失败：' . $e->getMessage());
            }
        }
        
        $this->assign([
            'vo' => $vo,
            'rule_type_list' => SalaryRuleModel::getRuleTypeList(),
            'unit_type_list' => SalaryRuleModel::getUnitTypeList(),
            'status_list' => SalaryRuleModel::getStatusList()
        ]);
        
        return $this->fetch('form');
    }
    
    /**
     * 删除工资规则
     * @auth true
     */
    public function delete()
    {
        $id = $this->request->param('id');
        
        try {
            SalaryRuleModel::destroy($id);
            $this->success('删除成功');
        } catch (\Exception $e) {
            $this->error('删除失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取提成规则数据
     * @param string $type new|old
     * @return array
     */
    private function getCommissionRules($type)
    {
        $table = $type === 'new' ? 'system_new_commission_rules_new_customer' : 'system_new_commission_rules_old_customer';
        
        try {
            return Db::name($table)
                ->where('status', 1)
                ->order('sort_order asc')
                ->select()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
}
