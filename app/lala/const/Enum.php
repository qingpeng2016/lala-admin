<?php
declare (strict_types=1);

namespace app\lala\const;

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
} 