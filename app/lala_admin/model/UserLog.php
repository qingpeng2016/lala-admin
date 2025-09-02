<?php
declare (strict_types = 1);

namespace app\lala_admin\model;

use think\admin\Model;
use app\lala_admin\const\EnumTool;

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

    /**
     * 获取渠道列表
     * @return array
     */
    public static function getChannelList(): array
    {
        return EnumTool::getChannelList();
    }

    /**
     * 获取渠道名称
     * @param string $channel 渠道代码
     * @return string
     */
    public static function getChannelName(string $channel): string
    {
        return EnumTool::getChannelName($channel);
    }

    /**
     * 判断是否为TG渠道
     * @param string $channel 渠道代码
     * @return bool
     */
    public static function isTgChannel(string $channel): bool
    {
        return EnumTool::isTgChannel($channel);
    }

    /**
     * 获取操作类型列表
     * @return array
     */
    public static function getActionList(): array
    {
        return [
            '页面访问' => '页面访问',
            '用户点击' => '用户点击',
            '用户加入' => '用户加入'
        ];
    }
} 