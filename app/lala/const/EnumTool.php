<?php
declare (strict_types=1);

namespace app\lala\const;

/**
 * 枚举工具类
 */
class EnumTool
{

    /**
     * 获取统计时间范围列表
     * @return array
     */
    public static function getStatTimeRanges(): array
    {
        return Enum::getStatTimeRanges();
    }
} 