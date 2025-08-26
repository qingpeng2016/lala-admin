<?php
declare (strict_types = 1);

namespace app\lala_admin\model;

use think\admin\Model;

/**
 * 客户模型
 */
class Client extends Model
{
    /**
     * 数据表名称
     * @var string
     */
    protected $table = 'tblclients';

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'created_at';

}
