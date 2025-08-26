<?php
declare (strict_types = 1);

namespace app\lala_admin\model;

use think\admin\Model;

/**
 * 持有商品模型
 */
class Hosting extends Model
{
    /**
     * 数据表名称
     * @var string
     */
    protected $table = 'tblhosting';

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'created_at';

}
