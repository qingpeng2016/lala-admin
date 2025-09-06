<?php
declare (strict_types = 1);

namespace app\lala_admin\controller;

use think\admin\Controller;
use think\facade\Db;
use app\lala_admin\model\EmployeeSalary as EmployeeSalaryModel;

/**
 * 员工工资管理
 */
class EmployeeSalary extends Controller
{
    /**
     * 工资列表
     * @auth true
     * @menu true
     */
    public function index()
    {
        $this->title = '工资列表管理';
        
        // 获取请求参数
        $get = $this->request->get();
        
        // 创建查询对象
        $query = Db::name('system_new_employee_salary');
        
        // 添加搜索条件
        if (isset($get['employee_name']) && $get['employee_name'] !== '') {
            $query->where('employee_name', 'like', "%{$get['employee_name']}%");
        }
        if (isset($get['employee_type']) && $get['employee_type'] !== '') {
            $query->where('employee_type', $get['employee_type']);
        }
        if (isset($get['salary_month']) && $get['salary_month'] !== '') {
            $query->where('salary_month', $get['salary_month']);
        }
        if (isset($get['status']) && $get['status'] !== '') {
            $query->where('status', $get['status']);
        }
        
        // 执行分页查询
        $result = $query->order('id desc')->paginate([
            'list_rows' => 20,
            'page' => $get['page'] ?? 1,
            'query' => $get,
            'path' => '/admin.html#/lala_admin/employee_salary/index.html',
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
            
            // 格式化员工类型和状态
            $item['employee_type_text'] = EmployeeSalaryModel::getEmployeeTypeText($item['employee_type']);
            $item['status_text'] = EmployeeSalaryModel::getStatusText($item['status']);
            
            // 计算基础福利合计（仅全职员工）
            if ($item['employee_type'] === 'full_time') {
                $item['welfare_total'] = $item['attendance_bonus'] + $item['meal_allowance'] + $item['night_transport'] - $item['late_penalty'];
            } else {
                $item['welfare_total'] = 0;
            }
            
            // 计算提成合计
            $item['commission_total'] = $item['new_customer_commission'] + $item['old_customer_commission'] + $item['monthly_bonus'] + $item['price_bonus'];
        }
        
        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get,
            'employee_type_list' => EmployeeSalaryModel::getEmployeeTypeList(),
            'status_list' => EmployeeSalaryModel::getStatusList()
        ]);
        
        // 渲染视图
        return $this->fetch();
    }

    /**
     * 添加工资记录
     * @auth true
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            // 验证数据
            if (empty($data['employee_id'])) {
                $this->error('员工ID不能为空');
            }
            if (empty($data['employee_name'])) {
                $this->error('员工姓名不能为空');
            }
            if (empty($data['employee_type'])) {
                $this->error('员工类型不能为空');
            }
            if (empty($data['salary_month'])) {
                $this->error('工资月份不能为空');
            }
            
            // 检查是否已存在相同员工相同月份的记录
            $exists = Db::name('system_new_employee_salary')
                ->where('employee_id', $data['employee_id'])
                ->where('salary_month', $data['salary_month'])
                ->find();
            if ($exists) {
                $this->error('该员工本月工资记录已存在');
            }
            
            // 计算工资总额
            $data = $this->calculateSalary($data);
            
            // 设置默认状态
            if (!isset($data['status'])) {
                $data['status'] = '0';
            }
            
            $id = Db::name('system_new_employee_salary')->insertGetId($data);
            if ($id) {
                return json(['code' => 1, 'info' => '添加成功', 'url' => '']);
            } else {
                return $this->error('添加失败');
            }
        }
        
        $this->title = '添加工资记录';
        $this->assign([
            'employee_type_list' => EmployeeSalaryModel::getEmployeeTypeList(),
            'status_list' => EmployeeSalaryModel::getStatusList()
        ]);
        return $this->fetch('form');
    }

    /**
     * 编辑工资记录
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
            if (empty($data['employee_name'])) {
                $this->error('员工姓名不能为空');
            }
            if (empty($data['employee_type'])) {
                $this->error('员工类型不能为空');
            }
            if (empty($data['salary_month'])) {
                $this->error('工资月份不能为空');
            }
            
            // 计算工资总额
            $data = $this->calculateSalary($data);
            
            $result = Db::name('system_new_employee_salary')->where('id', $id)->update($data);
            if ($result !== false) {
                return json(['code' => 1, 'info' => '更新成功', 'url' => '']);
            } else {
                return $this->error('更新失败');
            }
        }
        
        // 获取数据
        $info = Db::name('system_new_employee_salary')->where('id', $id)->find();
        if (!$info) {
            $this->error('数据不存在');
        }
        
        $this->title = '编辑工资记录';
        $this->assign([
            'vo' => $info,
            'employee_type_list' => EmployeeSalaryModel::getEmployeeTypeList(),
            'status_list' => EmployeeSalaryModel::getStatusList()
        ]);
        return $this->fetch('form');
    }

    /**
     * 删除工资记录
     * @auth true
     */
    public function delete()
    {
        $id = $this->request->param('id');
        if (empty($id)) {
            $this->error('参数错误');
        }
        
        $result = Db::name('system_new_employee_salary')->where('id', $id)->delete();
        if ($result) {
            return json(['code' => 1, 'info' => '删除成功']);
        } else {
            return json(['code' => 0, 'info' => '删除失败']);
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
        
        if (empty($id) || !isset($status)) {
            return json(['code' => 0, 'info' => '参数错误']);
        }
        
        $result = Db::name('system_new_employee_salary')->where('id', $id)->update(['status' => $status]);
        if ($result !== false) {
            return json(['code' => 1, 'info' => '状态更新成功']);
        } else {
            return json(['code' => 0, 'info' => '状态更新失败']);
        }
    }

    /**
     * 计算工资总额
     * @param array $data
     * @return array
     */
    private function calculateSalary($data)
    {
        // 基础工资
        $base_salary = floatval($data['base_salary'] ?? 0);
        
        // 基础福利（仅全职员工）
        $welfare_total = 0;
        if ($data['employee_type'] === 'full_time') {
            $welfare_total = floatval($data['attendance_bonus'] ?? 0) 
                           + floatval($data['meal_allowance'] ?? 0) 
                           + floatval($data['night_transport'] ?? 0) 
                           - floatval($data['late_penalty'] ?? 0);
        }
        
        // 提成合计
        $commission_total = floatval($data['new_customer_commission'] ?? 0)
                          + floatval($data['old_customer_commission'] ?? 0)
                          + floatval($data['monthly_bonus'] ?? 0)
                          + floatval($data['price_bonus'] ?? 0);
        
        // 计算应发工资总额
        $total_salary = $base_salary + $welfare_total + $commission_total;
        
        // 计算实发工资
        $deductions = floatval($data['deductions'] ?? 0);
        $actual_salary = $total_salary - $deductions;
        
        $data['total_salary'] = $total_salary;
        $data['actual_salary'] = $actual_salary;
        
        return $data;
    }
}
