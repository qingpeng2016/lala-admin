<?php
declare (strict_types=1);

namespace app\lala\const;

/**
 * 枚举工具类
 */
class EnumTool
{
    /**
     * 获取路由模式列表
     * @return array
     */
    public static function getRouteModes(): array
    {
        return [
            Enum::ROUTE_MODE_AUTO => Enum::ROUTE_MODE_AUTO,
            Enum::ROUTE_MODE_FIXED => Enum::ROUTE_MODE_FIXED
        ];
    }

    /**
     * 获取订单状态列表
     * @return array
     */
    public static function getOrderStatuses(): array
    {
        return [
            Enum::ORDER_STATUS_PENDING => Enum::ORDER_STATUS_PENDING,
            Enum::ORDER_STATUS_SUCCESS => Enum::ORDER_STATUS_SUCCESS,
            Enum::ORDER_STATUS_FAILED => Enum::ORDER_STATUS_FAILED,
            Enum::ORDER_STATUS_CANCELED => Enum::ORDER_STATUS_CANCELED,
            Enum::ORDER_STATUS_REFUNDED => Enum::ORDER_STATUS_REFUNDED
        ];
    }

    /**
     * 获取业务通知状态列表
     * @return array
     */
    public static function getBusinessNotifyStatuses(): array
    {
        return [
            Enum::BUSINESS_NOTIFY_STATUS_PENDING => Enum::BUSINESS_NOTIFY_STATUS_PENDING,
            Enum::BUSINESS_NOTIFY_STATUS_SUCCESS => Enum::BUSINESS_NOTIFY_STATUS_SUCCESS,
            Enum::BUSINESS_NOTIFY_STATUS_FAILED => Enum::BUSINESS_NOTIFY_STATUS_FAILED
        ];
    }

    /**
     * 获取支付类型列表
     * @return array
     */
    public static function getPaymentTypes(): array
    {
        return [
            Enum::PAYMENT_TYPE_CASH => Enum::PAYMENT_TYPE_CASH,
            Enum::PAYMENT_TYPE_PAYPAL => Enum::PAYMENT_TYPE_PAYPAL,
            Enum::PAYMENT_TYPE_CARD => Enum::PAYMENT_TYPE_CARD,
            Enum::PAYMENT_TYPE_BITCOIN => Enum::PAYMENT_TYPE_BITCOIN
        ];
    }

    /**
     * 获取订单类型列表
     * @return array
     */
    public static function getOrderTypes(): array
    {
        return [
            Enum::ORDER_TYPE_DEPOSIT => Enum::ORDER_TYPE_DEPOSIT,
            Enum::ORDER_TYPE_WITHDRAW => Enum::ORDER_TYPE_WITHDRAW,
        ];
    }

    /**
     * 获取适配器名称列表
     * @return array
     */
    public static function getAdaptorNames(): array
    {
        return [
            Enum::ADAPTOR_NAME_PAY_LINKING => Enum::ADAPTOR_NAME_PAY_LINKING,
            Enum::ADAPTOR_NAME_PAY_PAYPAL => Enum::ADAPTOR_NAME_PAY_PAYPAL,
            Enum::ADAPTOR_NAME_PAY_WONDERS_PAY => Enum::ADAPTOR_NAME_PAY_WONDERS_PAY,
        ];
    }

    /**
     * 获取统计时间范围列表
     * @return array
     */
    public static function getStatTimeRanges(): array
    {
        return Enum::getStatTimeRanges();
    }
} 