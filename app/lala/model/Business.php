<?php
declare (strict_types = 1);

namespace app\lala\model;

use think\admin\Model;

/**
 * 业务配置模型
 */
class Business extends Model
{
    /**
     * 数据表名称
     * @var string
     */
    protected $table = 'business';

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

} 