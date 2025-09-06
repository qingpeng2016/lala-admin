<?php

namespace app\lala_admin\constant;

use think\facade\Db;

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
        try {
            // 从数据库获取推广平台信息
            $platforms = Db::name('system_new_promotion_platforms')
                ->where('status', 'active')
                ->field('channel, platform_name')
                ->select()
                ->toArray();
            
            $channelList = [];
            foreach ($platforms as $platform) {
                $channelList[$platform['channel']] = $platform['platform_name'];
            }
            
            // 添加特殊选项
            $channelList['official'] = '其他渠道';
            
            return $channelList;
        } catch (\Exception $e) {
            // 如果数据库查询失败，返回默认配置
            return [
                '211' => 'TG',
                'official' => '其他渠道'
            ];
        }
    }

    /**
     * 根据渠道代码获取渠道名称
     * @param string $channel 渠道代码
     * @return string
     */
    public static function getChannelName($channel)
    {
        try {
            // 从数据库查询渠道名称
            $platform = Db::name('system_new_promotion_platforms')
                ->where('channel', $channel)
                ->where('status', 'active')
                ->field('platform_name')
                ->find();
            
            if ($platform) {
                return $platform['platform_name'];
            }
            
            // 如果没找到，返回其他渠道
            return '其他';
        } catch (\Exception $e) {
            // 如果数据库查询失败，使用原有逻辑
            if ($channel === '211') {
                return 'TG';
            }
            return '其他';
        }
    }

    /**
     * 判断是否为TG渠道
     * @param string $channel 渠道代码
     * @return bool
     */
    public static function isTgChannel($channel)
    {
        try {
            // 从数据库查询是否为TG平台
            $platform = Db::name('system_new_promotion_platforms')
                ->where('channel', $channel)
                ->where('platform_name', 'TG')
                ->where('status', 'active')
                ->find();
            
            return !empty($platform);
        } catch (\Exception $e) {
            // 如果数据库查询失败，使用原有逻辑
            return $channel === '211';
        }
    }
} 