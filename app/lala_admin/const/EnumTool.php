<?php

namespace app\lala_admin\const;

/**
 * 枚举工具类
 */
class EnumTool
{
    /**
     * 获取统计时间范围列表
     * @return array
     */
    public static function getStatTimeRanges()
    {
        return Enum::getStatTimeRanges();
    }

    /**
     * 获取渠道列表
     * @return array
     */
    public static function getChannelList()
    {
        return Enum::getChannelList();
    }

    /**
     * 根据渠道代码获取渠道名称
     * @param string $channel 渠道代码
     * @return string
     */
    public static function getChannelName($channel)
    {
        return Enum::getChannelName($channel);
    }

    /**
     * 判断是否为TG渠道
     * @param string $channel 渠道代码
     * @return bool
     */
    public static function isTgChannel($channel)
    {
        return Enum::isTgChannel($channel);
    }
} 