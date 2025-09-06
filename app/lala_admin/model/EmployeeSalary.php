<?php
declare (strict_types = 1);

namespace app\lala_admin\model;

use think\admin\Model;

/**
 * 员工工资模型
 */
class EmployeeSalary extends Model
{
    /**
     * 数据表名称
     * @var string
     */
    protected $table = 'system_new_employee_salary';

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
     * 员工类型列表
     * @return array
     */
    public static function getEmployeeTypeList()
    {
        return [
            'full_time' => '全职员工',
            'part_time_base' => '底薪兼职',
            'part_time' => '普通兼职'
        ];
    }

    /**
     * 状态列表
     * @return array
     */
    public static function getStatusList()
    {
        return [
            '0' => '待审核',
            '1' => '已审核',
            '2' => '已发放'
        ];
    }

    /**
     * 获取员工类型文本
     * @param string $type
     * @return string
     */
    public static function getEmployeeTypeText($type)
    {
        $list = self::getEmployeeTypeList();
        return $list[$type] ?? $type;
    }

    /**
     * 获取状态文本
     * @param string $status
     * @return string
     */
    public static function getStatusText($status)
    {
        $list = self::getStatusList();
        return $list[$status] ?? $status;
    }
}
