<?php
declare (strict_types=1);

namespace app\lala_admin\const;

/**
 * 系统常量定义
 */
class Enum
{
    /**
     * 统计时间范围
     */
    const STAT_TIME_TODAY = 'today';      // 今日
    const STAT_TIME_YESTERDAY = 'yesterday'; // 昨日
    const STAT_TIME_WEEK = 'week';        // 本周
    const STAT_TIME_MONTH = 'month';      // 本月
    const STAT_TIME_QUARTER = 'quarter';  // 本季度
    const STAT_TIME_YEAR = 'year';        // 本年

    /**
     * 渠道常量定义
     */
    const CHANNEL_TG = '211';           // TG渠道

    /**
     * 获取所有统计时间范围
     * @return array
     */
    public static function getStatTimeRanges()
    {
        return [
            self::STAT_TIME_TODAY => '今日',
            self::STAT_TIME_YESTERDAY => '昨日',
            self::STAT_TIME_WEEK => '本周',
            self::STAT_TIME_MONTH => '本月',
            self::STAT_TIME_QUARTER => '本季度',
            self::STAT_TIME_YEAR => '本年'
        ];
    }

    /**
     * 获取所有渠道配置（用于下拉选择）
     * @return array
     */
    public static function getChannelList()
    {
        return [
            self::CHANNEL_TG => 'TG'
        ];
    }

    /**
     * 根据渠道代码获取渠道名称
     * @param string $channel 渠道代码
     * @return string
     */
    public static function getChannelName($channel)
    {
        if ($channel === self::CHANNEL_TG) {
            return 'TG';
        }
        // 其他所有渠道都归为官方
        return '官方';
    }

    /**
     * 判断是否为TG渠道
     * @param string $channel 渠道代码
     * @return bool
     */
    public static function isTgChannel($channel)
    {
        return $channel === self::CHANNEL_TG;
    }
} 