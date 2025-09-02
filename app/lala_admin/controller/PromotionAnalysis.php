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

        // 分配变量到视图
        $this->assign([
            'channel_stats' => $channel_stats,
            'funnel_data' => $funnel_data,
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
        $query = $this->getBaseQuery()
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

        // 重新整理数据，TG渠道优先
        $result = [];
        $tg_data = null;
        
        foreach ($stats as $item) {
            $channel = EnumTool::getChannelName($item['channel']);
            if (!isset($result[$channel])) {
                $result[$channel] = [
                    'unique_visitors' => 0,
                    'total_actions' => 0
                ];
            }
            $result[$channel]['unique_visitors'] += $item['unique_visitors'];
            $result[$channel]['total_actions'] += $item['total_actions'];
            
            // 单独保存TG数据
            if ($channel === 'TG') {
                $tg_data = $result[$channel];
                unset($result[$channel]);
            }
        }
        
        // TG渠道放在最前面
        if ($tg_data) {
            $result = ['TG' => $tg_data] + $result;
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
}
