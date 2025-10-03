<?php
declare (strict_types = 1);

namespace app\lala\model;

use think\admin\Model;

/**
 * 实名认证模型
 */
class Realname extends Model
{
    /**
     * 数据表名称
     * @var string
     */
    protected $table = 'realname_personal_list';

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'create_time';

    /**
     * 更新时间字段
     * @var string
     */
    protected $updateTime = 'update_time';

} 