<?php
declare (strict_types=1);

namespace app\lala\const;

/**
 * 系统常量定义
 */
class Enum
{
    /**
     * 语言类型
     */
    const LANG_EN = 1; // 英文
    const LANG_CH = 2; // 中文
    const LANG_HK = 3; // 繁体中文

    /**
     * 邮件发送类型
     */
    const EMAIL_SEND_TYPE_LOGIN = 1;     // 登录
    const EMAIL_SEND_TYPE_REGISTER = 2;  // 注册
    const EMAIL_SEND_TYPE_BIND_EMAIL = 3; // 绑定邮箱

    /**
     * 业务状态
     */
    const BUSINESS_STATUS_DISABLED = 0; // 禁用
    const BUSINESS_STATUS_ENABLED = 1;  // 启用

    /**
     * 渠道状态
     */
    const CHANNEL_STATUS_DISABLED = 0; // 禁用
    const CHANNEL_STATUS_ENABLED = 1;  // 启用

    /**
     * 支付类型
     */
    const PAYMENT_TYPE_CASH = 'Cash';    // 现金
    const PAYMENT_TYPE_PAYPAL = 'PayPal';  // PayPal
    const PAYMENT_TYPE_CARD = 'Card';    // 信用卡
    const PAYMENT_TYPE_BITCOIN = 'Bitcoin'; // 比特币

    /**
     * 订单类型
     */
    const ORDER_TYPE_DEPOSIT = 'Deposit';
    const ORDER_TYPE_WITHDRAW = 'Withdraw';

    /**
     * 货币类型
     */
    const CURRENCY_TYPE_USD = 'USD'; // USD

    /**
     * 订单状态
     */
    const ORDER_STATUS_PENDING = 'Wait';     // 待支付
    const ORDER_STATUS_SUCCESS = 'Success';  // 支付成功
    const ORDER_STATUS_FAILED = 'Failed';   // 支付失败
    const ORDER_STATUS_CANCELED = 'Canceled'; // 已撤销
    const ORDER_STATUS_REFUNDED = 'Refunded'; // 已退款

    /**
     * 业务通知状态
     */
    const BUSINESS_NOTIFY_STATUS_PENDING = 'Wait';    // 未通知
    const BUSINESS_NOTIFY_STATUS_SUCCESS = 'Success'; // 通知成功
    const BUSINESS_NOTIFY_STATUS_FAILED = 'False';   // 通知失败

    /**
     * 适配器名称
     */
    const ADAPTOR_NAME_PAY_LINKING = 'PayLinking';
    const ADAPTOR_NAME_PAY_PAYPAL = 'PayPal';
    const ADAPTOR_NAME_PAY_WONDERS_PAY = 'WondersPay';

    /**
     * 路由类型
     */
    const ROUTE_MODE_AUTO = 'Auto';  // 动态模式
    const ROUTE_MODE_FIXED = 'Fixed'; // 固定模式

    /**
     * 转账App
     */
    const PAYMENT_APP_ECASHAPP = 'ECashApp'; // ecashapp
    const PAYMENT_APP_PAYPAL = 'Paypal';   // paypal
    const PAYMENT_APP_VENMO = 'Venmo';    // venmo

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
     * WHMCS 数据表
     */
    const TABLE_CLIENTS = 'tblclients';           // 用户表
    const TABLE_ORDERS = 'tblorders';             // 订单表
    const TABLE_INVOICES = 'tblinvoices';         // 发票表
    const TABLE_INVOICE_ITEMS = 'tblinvoiceitems'; // 发票项目表
    const TABLE_SERVERS = 'tblservers';           // 服务器表
    const TABLE_HOSTING = 'tblhosting';           // 虚拟主机表
    const TABLE_DOMAINS = 'tbldomains';           // 域名表
    const TABLE_TICKETS = 'tbltickets';           // 工单表
    const TABLE_PRODUCTS = 'tblproducts';         // 产品表
    const TABLE_SERVICES = 'tblservices';         // 服务表

    /**
     * 获取所有订单类型
     * @return array
     */
    public static function getAllOrderTypes(): array
    {
        return [
            self::ORDER_TYPE_DEPOSIT,
            self::ORDER_TYPE_WITHDRAW,
        ];
    }

    /**
     * 获取所有支付类型
     * @return array
     */
    public static function getAllPaymentTypes(): array
    {
        return [
            self::PAYMENT_TYPE_CASH,
            self::PAYMENT_TYPE_PAYPAL,
            self::PAYMENT_TYPE_CARD,
            self::PAYMENT_TYPE_BITCOIN,
        ];
    }

    /**
     * 获取所有支付App
     * @return array
     */
    public static function getAllPaymentApps(): array
    {
        return [
            self::PAYMENT_APP_PAYPAL,
            self::PAYMENT_APP_ECASHAPP,
            self::PAYMENT_APP_VENMO,
        ];
    }

    /**
     * 检查订单类型是否有效
     * @param string $orderType
     * @return bool
     */
    public static function checkOrderType(string $orderType): bool
    {
        return in_array($orderType, self::getAllOrderTypes());
    }

    /**
     * 检查支付类型是否有效
     * @param string $paymentType
     * @return bool
     */
    public static function checkPaymentType(string $paymentType): bool
    {
        return in_array($paymentType, self::getAllPaymentTypes());
    }

    /**
     * 检查支付App是否有效
     * @param string $paymentApp
     * @return bool
     */
    public static function checkPaymentApp(string $paymentApp): bool
    {
        return in_array($paymentApp, self::getAllPaymentApps());
    }

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