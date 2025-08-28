<?php
declare (strict_types = 1);

namespace app\lala_admin\model;

use think\admin\Model;

/**
 * 推广平台模型
 */
class PromotionPlatform extends Model
{
    /**
     * 数据表名称
     * @var string
     */
    protected $table = 'system_new_promotion_platforms';

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
     * 状态列表
     * @return array
     */
    public static function getStatusList()
    {
        return [
            'active' => '活跃',
            'inactive' => '非活跃',
            'testing' => '测试中'
        ];
    }
}
