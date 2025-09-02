<?php
declare (strict_types=1);

namespace app\lala_admin\controller;

use think\admin\Controller;
use app\lala_admin\model\PromotionAnalysis as PromotionAnalysisModel;
use app\lala_admin\constant\EnumTool;
use app\lala_admin\constant\Enum;

/**
 * 推广分析管理
 */
class PromotionAnalysis extends Controller
{
    /**
     * 默认查询天数
     */
    const DEFAULT_DAYS = 7;

    /**
     * 获取基础查询条件
     * @return \think\db\Query
     */
    private function getBaseQuery()
    {
        return PromotionAnalysisModel::where('is_manager', 0)
            ->where('manager_id', 0)
            ->where('userid', 0);
    }

    /**
     * 获取标准化的日期范围
     * @param string $start_date 开始日期
     * @param string $end_date 结束日期
     * @return array [start_date, end_date]
     */
    private function getDateRange($start_date = '', $end_date = '')
    {
        // 如果没有指定日期范围，使用默认天数
        if (empty($start_date)) {
            $start_date = date('Y-m-d', strtotime('-' . self::DEFAULT_DAYS . ' days'));
        }
        if (empty($end_date)) {
            $end_date = date('Y-m-d');
        }
        
        return [$start_date, $end_date];
    }

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
        list($start_date, $end_date) = $this->getDateRange($get['start_date'] ?? '', $get['end_date'] ?? '');

        // 获取渠道统计
        $channel_stats = $this->getChannelStats($start_date, $end_date);

        // 获取漏斗数据
        $funnel_data = $this->getFunnelData($start_date, $end_date);
        
        // 获取全部渠道的漏斗数据
        $all_funnel_data = $this->getAllFunnelData($start_date, $end_date);
        
        // 获取投资回报分析数据
        $roi_data = $this->getRoiData($start_date, $end_date);

        // 分配变量到视图
        $this->assign([
            'channel_stats' => $channel_stats,
            'funnel_data' => $funnel_data,
            'all_funnel_data' => $all_funnel_data,
            'roi_data' => $roi_data,
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
        // 1. 先获取TG渠道数据
        $tg_query = $this->getBaseQuery()
            ->where('channel', Enum::CHANNEL_TG);

        if ($start_date) {
            $tg_query->where('created_at', '>=', $start_date . ' 00:00:00');
        }
        if ($end_date) {
            $tg_query->where('created_at', '<=', $end_date . ' 23:59:59');
        }

        $tg_stats = $tg_query->field([
            'COUNT(DISTINCT ipaddr) as unique_visitors',
            'COUNT(*) as total_actions'
        ])->find();

        // 2. 获取全部渠道数据（不限制channel条件）
        $all_query = $this->getBaseQuery();

        if ($start_date) {
            $all_query->where('created_at', '>=', $start_date . ' 00:00:00');
        }
        if ($end_date) {
            $all_query->where('created_at', '<=', $end_date . ' 23:59:59');
        }

        $all_stats = $all_query->field([
            'COUNT(DISTINCT ipaddr) as unique_visitors',
            'COUNT(*) as total_actions'
        ])->find();

        // 3. 组装结果数据
        $result = [];
        
        // TG渠道数据
        if ($tg_stats) {
            $result['TG'] = [
                'unique_visitors' => $tg_stats['unique_visitors'] ?? 0,
                'total_actions' => $tg_stats['total_actions'] ?? 0
            ];
        }
        
        // 全部渠道数据
        if ($all_stats) {
            $result['全部'] = [
                'unique_visitors' => $all_stats['unique_visitors'] ?? 0,
                'total_actions' => $all_stats['total_actions'] ?? 0
            ];
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
        $query = $this->getBaseQuery()
            ->where('channel', '<>', '')
            ->where('channel', '<>', '0');

        // 添加渠道筛选
        if ($channel) {
            if ($channel == 'TG') {
                $query->where('channel', Enum::CHANNEL_TG);
            } else {
                $query->where('channel', '<>', Enum::CHANNEL_TG);
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
     * 获取TG渠道的漏斗数据
     * @param string $start_date 开始日期
     * @param string $end_date 结束日期
     * @return array
     */
    protected function getFunnelData($start_date = '', $end_date = '')
    {
        // 获取标准化的日期范围
        list($start_date, $end_date) = $this->getDateRange($start_date, $end_date);

        try {
            // 1. 先获取TG渠道的总独立访客数
            $total_tg_visitors = $this->getBaseQuery()
                ->where('channel', Enum::CHANNEL_TG)
                ->where('created_at', '>=', $start_date . ' 00:00:00')
                ->where('created_at', '<=', $end_date . ' 23:59:59')
                ->count('DISTINCT ipaddr');

            if ($total_tg_visitors == 0) {
                return [];
            }

            // 2. 统一按 description 和 action 分组
            $all_stats = $this->getBaseQuery()
                ->where('channel', Enum::CHANNEL_TG)
                ->where('description', '<>', '')
                ->where('description', 'not null')
                ->where('created_at', '>=', $start_date . ' 00:00:00')
                ->where('created_at', '<=', $end_date . ' 23:59:59')
                ->field([
                    'description',
                    'action',
                    'COUNT(DISTINCT ipaddr) as visitors'
                ])
                ->group('description, action')
                ->order('visitors desc')
                ->limit(15)
                ->select()
                ->toArray();

            // 3. 计算转化率并格式化显示
            $funnel_data = [];
            foreach ($all_stats as $item) {
                $funnel_rate = round(($item['visitors'] / $total_tg_visitors) * 100, 2);
                $funnel_data[] = [
                    'page' => '(' . $item['action'] . ') ' . $item['description'],
                    'visitors' => $item['visitors'],
                    'rate' => $funnel_rate
                ];
            }

            return $funnel_data;

        } catch (\Exception $e) {
            // 如果查询出错，记录日志并返回空数组
            trace('getFunnelData查询出错: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * 获取全部渠道漏斗数据
     * @param string $start_date 开始日期
     * @param string $end_date 结束日期
     * @return array
     */
    protected function getAllFunnelData($start_date = '', $end_date = '')
    {
        // 获取标准化的日期范围
        list($start_date, $end_date) = $this->getDateRange($start_date, $end_date);

        try {
            // 1. 先获取所有渠道的总独立访客数
            $total_visitors = $this->getBaseQuery()
                ->where('created_at', '>=', $start_date . ' 00:00:00')
                ->where('created_at', '<=', $end_date . ' 23:59:59')
                ->count('DISTINCT ipaddr');

            if ($total_visitors == 0) {
                return [];
            }

            // 2. 统一按 description 和 action 分组
            $all_stats = $this->getBaseQuery()
                ->where('description', '<>', '')
                ->where('description', 'not null')
                ->where('created_at', '>=', $start_date . ' 00:00:00')
                ->where('created_at', '<=', $end_date . ' 23:59:59')
                ->field([
                    'description',
                    'action',
                    'COUNT(DISTINCT ipaddr) as visitors'
                ])
                ->group('description, action')
                ->order('visitors desc')
                ->limit(15)
                ->select()
                ->toArray();

            // 3. 计算转化率并格式化显示
            $funnel_data = [];
            foreach ($all_stats as $item) {
                $funnel_rate = round(($item['visitors'] / $total_visitors) * 100, 2);
                $funnel_data[] = [
                    'page' => '(' . $item['action'] . ') ' . $item['description'],
                    'visitors' => $item['visitors'],
                    'rate' => $funnel_rate
                ];
            }

            return $funnel_data;

        } catch (\Exception $e) {
            // 如果查询出错，记录日志并返回空数组
            trace('getAllFunnelData查询出错: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * 获取投资回报分析数据
     * @param string $start_date 开始日期
     * @param string $end_date 结束日期
     * @return array
     */
    protected function getRoiData($start_date = '', $end_date = '')
    {
        // 获取标准化的日期范围
        list($start_date, $end_date) = $this->getDateRange($start_date, $end_date);

        try {
            $roi_data = [];
            
            // 按日期循环统计
            $current_date = $start_date;
            while ($current_date <= $end_date) {
                // 1. 获取当日访问量（独立访客数）
                $visits = $this->getBaseQuery()
                    ->where('channel', Enum::CHANNEL_TG)
                    ->where('created_at', '>=', $current_date . ' 00:00:00')
                    ->where('created_at', '<=', $current_date . ' 23:59:59')
                    ->count('DISTINCT ipaddr');

                // 2. 获取当日注册数（TG渠道用户）
                $registers = \think\facade\Db::name('tblclients')
                    ->where('affiliateid', '211')
                    ->where('datecreated', $current_date)
                    ->count();

                // 3. 获取当日下单数和下单金额（注册用户的订单）
                $order_stats = \think\facade\Db::name('tblclients')
                    ->alias('c')
                    ->join('tblinvoices i', 'c.id = i.userid')
                    ->where('c.affiliateid', '211')
                    ->where('i.date', $current_date)
                    ->where('i.status', '<>', 'Cancelled')
                    ->field([
                        'COUNT(i.id) as order_count',
                        'SUM(i.total) as order_amount'
                    ])
                    ->find();

                $orders = $order_stats['order_count'] ?? 0;
                $order_amount = $order_stats['order_amount'] ?? 0;

                // 4. 计算转化率
                $register_rate = $visits > 0 ? round(($registers / $visits) * 100, 2) : 0;
                $order_rate = $visits > 0 ? round(($orders / $visits) * 100, 2) : 0;

                $roi_data[] = [
                    'date' => $current_date,
                    'visits' => $visits,
                    'registers' => $registers,
                    'register_rate' => $register_rate,
                    'orders' => $orders,
                    'order_rate' => $order_rate,
                    'order_amount' => number_format($order_amount, 2)
                ];

                // 日期加一天
                $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
            }

            return array_reverse($roi_data); // 倒序显示，最新日期在前

        } catch (\Exception $e) {
            // 如果查询出错，记录日志并返回空数组
            trace('getRoiData查询出错: ' . $e->getMessage(), 'error');
            return [];
        }
    }
}
