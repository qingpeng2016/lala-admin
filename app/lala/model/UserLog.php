<?php
declare (strict_types = 1);

namespace app\lala\model;

use think\admin\Model;

/**
 * 用户日志模型
 */
class UserLog extends Model
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