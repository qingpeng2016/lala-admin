<?php
declare (strict_types = 1);

namespace app\lala\model;

use think\admin\Model;

/**
 * 协议内容模型
 */
class AgreementContent extends Model
{
    /**
     * 数据表名称
     * @var string
     */
    protected $table = 'realname_agreements_content';

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
     * 协议类型列表
     * @var array
     */
    public static $agreementTypes = [
        'user_agreement' => '用户协议',
        'privacy_policy' => '隐私政策'
    ];

    /**
     * 状态列表
     * @var array
     */
    public static $statusList = [
        1 => '激活',
        0 => '禁用'
    ];
} 