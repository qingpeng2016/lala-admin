<?php
declare (strict_types = 1);

namespace app\lala_admin\model;

use think\admin\Model;

/**
 * 员工模型
 */
class Employee extends Model
{
    /**
     * 数据表名称
     * @var string
     */
    protected $table = 'system_user';

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'create_at';

}
