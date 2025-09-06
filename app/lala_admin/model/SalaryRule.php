<?php
declare (strict_types = 1);

namespace app\lala_admin\model;

use think\admin\Model;

/**
 * 工资规则模型
 */
class SalaryRule extends Model
{
    /**
     * 数据表名称
     * @var string
     */
    protected $table = 'system_new_salary_rules';

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段
     * @var string
     */
    protected $updateTime = 'updated_at';

    /**
     * 规则类型列表
     * @return array
     */
    public static function getRuleTypeList()
    {
        return [
            'base_salary' => '底薪',
            'attendance_bonus' => '全勤奖', 
            'late_penalty' => '迟到扣款',
            'meal_allowance' => '餐补',
            'night_transport' => '晚班交通补助',
            'bonus' => '奖金',
            'deduction' => '扣款'
        ];
    }

    /**
     * 单位类型列表
     * @return array
     */
    public static function getUnitTypeList()
    {
        return [
            'fixed' => '固定金额',
            'per_day' => '每天',
            'per_time' => '每次',
            'per_month' => '每月'
        ];
    }

    /**
     * 状态列表
     * @return array
     */
    public static function getStatusList()
    {
        return [
            '1' => '启用',
            '0' => '禁用'
        ];
    }
}
