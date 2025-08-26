<?php
declare (strict_types = 1);

namespace app\lala\model;

use think\admin\Model;

/**
 * 管理员日志模型
 */
class ManagerLog extends Model
{
    /**
     * 数据表名称
     * @var string
     */
    protected $table = 'custom_user_behavior_log';

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'created_at';

} 