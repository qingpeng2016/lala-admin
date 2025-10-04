<?php
declare (strict_types = 1);

namespace app\lala_admin\model;

use think\admin\Model;

/**
 * 问答模型
 */
class SystemQuestion extends Model
{
    /**
     * 数据表名称
     * @var string
     */
    protected $table = 'system_question';

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
     * 自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;
}