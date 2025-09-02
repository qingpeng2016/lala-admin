<?php
declare (strict_types = 1);

namespace app\lala_admin\controller;

use think\admin\Controller;
use app\lala_admin\model\PromotionAnalysis as PromotionAnalysisModel;

/**
 * 推广分析管理
 */
class PromotionAnalysis extends Controller
{
    /**
     * 推广分析首页
     * @auth true
     * @menu true
     */
    public function index()
    {
        $this->title = '推广分析';
        
        // 获取请求参数
        $get = $this->request->get();
        
        // 获取日期范围
        $start_date = $get['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
        $end_date = $get['end_date'] ?? date('Y-m-d');
        
        // 获取渠道统计
        $channel_stats = $this->getChannelStats($start_date, $end_date);
        
        // 获取每日趋势
        $daily_trend = $this->getDailyTrend('', $start_date, $end_date);
        
        // 分配变量到视图
        $this->assign([
            'channel_stats' => $channel_stats,
            'daily_trend' => $daily_trend,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'get' => $get
        ]);
        
        // 渲染视图
        return $this->fetch();
    }



    /**
     * 获取渠道访问量统计
     * @param string $start_date 开始日期
     * @param string $end_date 结束日期
     * @return array
     */
    protected function getChannelStats($start_date = '', $end_date = '')
    {
        $query = PromotionAnalysisModel::where('is_manager', 0)
                    ->where('manager_id', 0)
                    ->where('userid', 0)
                    ->where('channel', '<>', '')
                    ->where('channel', '<>', '0');

        // 添加日期范围
        if ($start_date) {
            $query->where('created_at', '>=', $start_date . ' 00:00:00');
        }
        if ($end_date) {
            $query->where('created_at', '<=', $end_date . ' 23:59:59');
        }

        // 按渠道分组统计，去重IP
        $stats = $query->field([
                'channel',
                'COUNT(DISTINCT ipaddr) as unique_visitors',
                'COUNT(*) as total_actions'
            ])
            ->group('channel')
            ->select()
            ->toArray();

        // 重新整理数据
        $result = [];
        foreach ($stats as $item) {
            $channel = \app\lala_admin\const\Enum::getChannelName($item['channel']);
            if (!isset($result[$channel])) {
                $result[$channel] = [
                    'unique_visitors' => 0,
                    'total_actions' => 0
                ];
            }
            $result[$channel]['unique_visitors'] += $item['unique_visitors'];
            $result[$channel]['total_actions'] += $item['total_actions'];
        }

        return $result;
    }

    /**
     * 获取渠道详细数据
     * @param string $channel 渠道
     * @param string $start_date 开始日期
     * @param string $end_date 结束日期
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    protected function getChannelDetail($channel = '', $start_date = '', $end_date = '', $page = 1, $limit = 20)
    {
        $query = PromotionAnalysisModel::where('is_manager', 0)
                    ->where('manager_id', 0)
                    ->where('userid', 0)
                    ->where('channel', '<>', '')
                    ->where('channel', '<>', '0');

        // 添加渠道筛选
        if ($channel) {
            if ($channel == 'TG') {
                $query->where('channel', \app\lala_admin\const\Enum::CHANNEL_TG);
            } else {
                $query->where('channel', '<>', \app\lala_admin\const\Enum::CHANNEL_TG);
            }
        }

        // 添加日期范围
        if ($start_date) {
            $query->where('created_at', '>=', $start_date . ' 00:00:00');
        }
        if ($end_date) {
            $query->where('created_at', '<=', $end_date . ' 23:59:59');
        }

        // 分页查询
        $result = $query->order('created_at desc')
                       ->paginate([
                           'list_rows' => $limit,
                           'page' => $page
                       ]);

        return $result;
    }

    /**
     * 获取每日访问量趋势
     * @param string $channel 渠道
     * @param string $start_date 开始日期
     * @param string $end_date 结束日期
     * @return array
     */
    protected function getDailyTrend($channel = '', $start_date = '', $end_date = '')
    {
        // 如果没有指定日期范围，默认分析最近1周
        if (empty($start_date)) {
            $start_date = date('Y-m-d', strtotime('-7 days'));
        }
        if (empty($end_date)) {
            $end_date = date('Y-m-d');
        }
        
        // 限制查询范围不超过30天，避免数据量过大
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        $max_days = 30;
        
        if (($end_timestamp - $start_timestamp) > ($max_days * 24 * 3600)) {
            $start_date = date('Y-m-d', $end_timestamp - ($max_days * 24 * 3600));
        }

        $query = PromotionAnalysisModel::where('is_manager', 0)
                    ->where('manager_id', 0)
                    ->where('userid', 0)
                    ->where('channel', '<>', '')
                    ->where('channel', '<>', '0')
                    ->where('created_at', '>=', $start_date . ' 00:00:00')
                    ->where('created_at', '<=', $end_date . ' 23:59:59');

        // 添加渠道筛选
        if ($channel) {
            if ($channel == 'TG') {
                $query->where('channel', \app\lala_admin\const\Enum::CHANNEL_TG);
            } else {
                $query->where('channel', '<>', \app\lala_admin\const\Enum::CHANNEL_TG);
            }
        }

        try {
            // 按日期分组统计
            $trend = $query->field([
                    'DATE(created_at) as date',
                    'COUNT(DISTINCT ipaddr) as unique_visitors',
                    'COUNT(*) as total_actions'
                ])
                ->group('DATE(created_at)')
                ->order('date asc')
                ->select()
                ->toArray();

            return $trend;
        } catch (\Exception $e) {
            // 如果查询出错，记录日志并返回空数组
            trace('getDailyTrend查询出错: ' . $e->getMessage(), 'error');
            return [];
        }
    }
}
