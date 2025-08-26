<?php
declare (strict_types = 1);

namespace app\lala\controller;

use think\admin\Controller;

/**
 * 数据统计控制器
 */
class Statistics extends Controller
{
    /**
     * 统计仪表板首页
     * @auth true
     * @menu true
     */
    public function index()
    {
        $this->title = '数据统计仪表板';
        
        // 直接写死随机数据
        $userStats = [
            'total' => rand(1000, 2000),
            'active' => rand(800, 1500),
            'inactive' => rand(100, 300),
            'suspended' => rand(50, 150),
            'cancelled' => rand(20, 100),
            'new_today' => rand(5, 25),
            'new_week' => rand(50, 150),
            'new_month' => rand(200, 500),
            'growth_rate' => rand(5, 15)
        ];
        
        $serverStats = [
            'total' => rand(30, 80),
            'online' => rand(25, 70),
            'offline' => rand(1, 10),
            'maintenance' => rand(1, 5),
            'utilization_rate' => rand(85, 98),
            'new_servers' => rand(1, 8),
            'decommissioned' => rand(0, 3)
        ];
        
        $revenueStats = [
            'today' => number_format(rand(8000, 20000), 2),
            'yesterday' => number_format(rand(7000, 18000), 2),
            'week' => number_format(rand(50000, 150000), 2),
            'month' => number_format(rand(200000, 500000), 0),
            'quarter' => number_format(rand(800000, 1500000), 0),
            'year' => number_format(rand(3000000, 8000000), 0),
            'growth_rate' => rand(8, 25),
            'currency' => 'USD'
        ];
        
        $orderStats = [
            'total' => rand(1500, 3000),
            'pending' => rand(20, 100),
            'active' => rand(1200, 2500),
            'suspended' => rand(50, 200),
            'cancelled' => rand(20, 100),
            'fraud' => rand(1, 20),
            'new_today' => rand(10, 50),
            'new_week' => rand(100, 300),
            'new_month' => rand(500, 1200),
            'conversion_rate' => rand(70, 90)
        ];
        
        $ticketStats = [
            'total' => rand(300, 800),
            'open' => rand(10, 50),
            'answered' => rand(5, 30),
            'client_reply' => rand(3, 20),
            'closed' => rand(250, 700),
            'new_today' => rand(3, 15),
            'new_week' => rand(20, 80),
            'new_month' => rand(100, 300),
            'avg_response_time' => rand(1, 5) . '小时',
            'satisfaction_rate' => rand(85, 98)
        ];
        
        $domainStats = [
            'total' => rand(2000, 5000),
            'active' => rand(1800, 4500),
            'expired' => rand(100, 300),
            'pending' => rand(20, 100),
            'new_today' => rand(5, 30),
            'new_week' => rand(50, 200),
            'new_month' => rand(200, 600),
            'expiring_soon' => rand(20, 100)
        ];
        
        $hostingStats = [
            'total' => rand(600, 1200),
            'active' => rand(550, 1100),
            'suspended' => rand(10, 50),
            'cancelled' => rand(5, 30),
            'new_today' => rand(3, 15),
            'new_week' => rand(20, 80),
            'new_month' => rand(100, 300),
            'utilization_rate' => rand(90, 99)
        ];
        
        $vpsStats = [
            'total' => rand(200, 500),
            'active' => rand(180, 450),
            'suspended' => rand(5, 25),
            'cancelled' => rand(2, 15),
            'new_today' => rand(1, 8),
            'new_week' => rand(10, 40),
            'new_month' => rand(50, 150),
            'utilization_rate' => rand(90, 98)
        ];
        
        $dedicatedStats = [
            'total' => rand(50, 150),
            'active' => rand(45, 140),
            'suspended' => rand(1, 10),
            'cancelled' => rand(1, 5),
            'new_today' => rand(0, 3),
            'new_week' => rand(2, 10),
            'new_month' => rand(10, 30),
            'utilization_rate' => rand(90, 99)
        ];
        
        // 分配变量到视图
        $this->assign([
            'timeRange' => 'month',
            'timeRanges' => [
                'today' => '今日',
                'yesterday' => '昨日',
                'week' => '本周',
                'month' => '本月',
                'quarter' => '本季度',
                'year' => '本年'
            ],
            'userStats' => $userStats,
            'serverStats' => $serverStats,
            'revenueStats' => $revenueStats,
            'orderStats' => $orderStats,
            'ticketStats' => $ticketStats,
            'domainStats' => $domainStats,
            'hostingStats' => $hostingStats,
            'vpsStats' => $vpsStats,
            'dedicatedStats' => $dedicatedStats
        ]);
        
        return $this->fetch();
    }
} 