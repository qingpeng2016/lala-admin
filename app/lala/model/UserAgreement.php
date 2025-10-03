<?php
declare (strict_types = 1);

namespace app\lala\model;

use think\admin\Model;

/**
 * 用户协议模型
 */
class UserAgreement extends Model
{
    /**
     * 数据表名称
     * @var string
     */
    protected $table = 'realname_agreements';

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'create_time';

} 