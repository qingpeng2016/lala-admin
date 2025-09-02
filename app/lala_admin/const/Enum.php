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
    const CHANNEL_OFFICIAL = 'official'; // 官方渠道

    /**
     * 获取所有统计时间范围
     * @return array
     */
    public static function getStatTimeRanges(): array
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
     * 获取所有渠道配置
     * @return array
     */
    public static function getChannelList(): array
    {
        return [
            self::CHANNEL_TG => 'TG',
            self::CHANNEL_OFFICIAL => '官方'
        ];
    }

    /**
     * 根据渠道代码获取渠道名称
     * @param string $channel 渠道代码
     * @return string
     */
    public static function getChannelName(string $channel): string
    {
        $channels = self::getChannelList();
        return $channels[$channel] ?? $channel;
    }

    /**
     * 判断是否为TG渠道
     * @param string $channel 渠道代码
     * @return bool
     */
    public static function isTgChannel(string $channel): bool
    {
        return $channel === self::CHANNEL_TG;
    }
} 