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
    const DEFAULT_DAYS = 15;

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

        // 获取所有渠道的漏斗数据和投资回报数据
        $funnel_data_all = [];
        $roi_data_all = [];
        
        foreach ($channel_stats as $channel_name => $stats) {
            if ($channel_name === '全部') {
                // 全部渠道：不限制channel条件
                $funnel_data_all[$channel_name] = $this->getFunnelDataByChannel('', $start_date, $end_date);
                $roi_data_all[$channel_name] = $this->getRoiDataByChannel('', $start_date, $end_date);
            } else {
                // 具体渠道：根据渠道名称获取对应的channel值
                $channel_codes = $this->getChannelCodes($channel_name);
                $funnel_data_all[$channel_name] = $this->getFunnelDataByChannel($channel_codes, $start_date, $end_date);
                $roi_data_all[$channel_name] = $this->getRoiDataByChannel($channel_codes, $start_date, $end_date);
            }
        }

        // 分配变量到视图
        $this->assign([
            'channel_stats' => $channel_stats,
            'funnel_data_all' => $funnel_data_all,
            'roi_data_all' => $roi_data_all,
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
        // 1. 获取所有渠道的统计数据（包括空channel，因为可能是其他渠道）
        $query = $this->getBaseQuery();

        if ($start_date) {
            $query->where('created_at', '>=', $start_date . ' 00:00:00');
        }
        if ($end_date) {
            $query->where('created_at', '<=', $end_date . ' 23:59:59');
        }

        $stats = $query->field([
            'channel',
            'COUNT(DISTINCT ipaddr) as unique_visitors',
            'COUNT(*) as total_actions'
        ])
            ->group('channel')
            ->select()
            ->toArray();

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

        // 3. 获取推广平台信息用于排序
        $platform_info = [];
        try {
            $platforms = \think\facade\Db::name('system_new_promotion_platforms')
                ->where('status', 'active')
                ->field('id, channel, platform_name')
                ->order('id asc')
                ->select()
                ->toArray();
            
            foreach ($platforms as $platform) {
                $platform_info[$platform['channel']] = [
                    'name' => $platform['platform_name'],
                    'order' => $platform['id']
                ];
            }
        } catch (\Exception $e) {
            // 查询失败时使用默认配置
        }

        // 4. 动态组装结果数据
        $result = [];
        $channel_order = []; // 用于排序

        // 处理各个具体渠道
        foreach ($stats as $item) {
            // 直接从推广平台信息获取渠道名称，如果没有则是其他渠道
            if (isset($platform_info[$item['channel']]) && !empty($item['channel'])) {
                $channel_name = $platform_info[$item['channel']]['name'];
                $order = $platform_info[$item['channel']]['order'];
            } else {
                // 空channel、'0'或不在推广平台表中的都归为其他渠道
                $channel_name = '其他渠道';
                $order = 9999;
            }
            
            if (!isset($result[$channel_name])) {
                $result[$channel_name] = [
                    'unique_visitors' => 0,
                    'total_actions' => 0
                ];
                $channel_order[$channel_name] = $order;
            }
            $result[$channel_name]['unique_visitors'] += $item['unique_visitors'];
            $result[$channel_name]['total_actions'] += $item['total_actions'];
        }

        // 添加全部渠道数据
        if ($all_stats) {
            $result['全部'] = [
                'unique_visitors' => $all_stats['unique_visitors'] ?? 0,
                'total_actions' => $all_stats['total_actions'] ?? 0
            ];
            $channel_order['全部'] = 3; // 全部渠道最低优先级
        }

        // 按优先级排序
        uksort($result, function($a, $b) use ($channel_order) {
            return ($channel_order[$a] ?? 999) - ($channel_order[$b] ?? 999);
        });

        return $result;
    }

    /**
     * 根据渠道名称获取对应的channel代码数组
     * @param string $channel_name 渠道名称
     * @return array
     */
    private function getChannelCodes($channel_name)
    {
        if ($channel_name === '其他渠道') {
            // 其他渠道：获取所有非推广平台的channel值（包括空值）
            return $this->getOtherChannelCodes();
        }
        
        // 推广平台渠道：从推广平台表中获取对应的channel值
        try {
            $platform = \think\facade\Db::name('system_new_promotion_platforms')
                ->where('platform_name', $channel_name)
                ->where('status', 'active')
                ->field('channel')
                ->find();
            
            if ($platform) {
                return [$platform['channel']];
            }
        } catch (\Exception $e) {
            // 查询失败
        }
        
        return [];
    }
    
    /**
     * 获取其他渠道的所有channel代码
     * @return array
     */
    private function getOtherChannelCodes()
    {
        try {
            // 获取所有推广平台的channel
            $platformChannels = \think\facade\Db::name('system_new_promotion_platforms')
                ->where('status', 'active')
                ->column('channel');
            
            // 获取所有存在的channel值
            $allChannels = $this->getBaseQuery()
                ->field('DISTINCT channel')
                ->select()
                ->column('channel');
            
            // 过滤出非推广平台的channel（包括空值）
            $otherChannels = [];
            foreach ($allChannels as $channel) {
                if (!in_array($channel, $platformChannels)) {
                    $otherChannels[] = $channel;
                }
            }
            
            return $otherChannels;
        } catch (\Exception $e) {
            return ['', '0']; // 默认返回空值
        }
    }

    /**
     * 根据渠道代码获取漏斗数据
     * @param array|string $channel_codes 渠道代码数组，空表示所有渠道
     * @param string $start_date 开始日期
     * @param string $end_date 结束日期
     * @return array
     */
    protected function getFunnelDataByChannel($channel_codes = '', $start_date = '', $end_date = '')
    {
        // 获取标准化的日期范围
        list($start_date, $end_date) = $this->getDateRange($start_date, $end_date);

        try {
            // 1. 构建查询条件
            $query = $this->getBaseQuery()
                ->where('created_at', '>=', $start_date . ' 00:00:00')
                ->where('created_at', '<=', $end_date . ' 23:59:59');

            // 添加渠道条件
            if (!empty($channel_codes)) {
                if (is_array($channel_codes)) {
                    $query->whereIn('channel', $channel_codes);
                } else {
                    $query->where('channel', $channel_codes);
                }
            }

            // 2. 获取总独立访客数
            $total_visitors = $query->count('DISTINCT ipaddr');

            if ($total_visitors == 0) {
                return [];
            }

            // 3. 按 description 和 action 分组统计
            $stats = $query->where('description', '<>', '')
                ->where('description', 'not null')
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

            // 4. 计算转化率并格式化显示
            $funnel_data = [];
            foreach ($stats as $item) {
                $funnel_rate = round(($item['visitors'] / $total_visitors) * 100, 2);
                $funnel_data[] = [
                    'page' => '(' . $item['action'] . ') ' . $item['description'],
                    'visitors' => $item['visitors'],
                    'rate' => $funnel_rate
                ];
            }

            return $funnel_data;

        } catch (\Exception $e) {
            trace('getFunnelDataByChannel查询出错: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * 根据渠道代码获取投资回报数据
     * @param array|string $channel_codes 渠道代码数组，空表示所有渠道
     * @param string $start_date 开始日期
     * @param string $end_date 结束日期
     * @return array
     */
    protected function getRoiDataByChannel($channel_codes = '', $start_date = '', $end_date = '')
    {
        // 获取标准化的日期范围
        list($start_date, $end_date) = $this->getDateRange($start_date, $end_date);

        try {
            $roi_data = [];
            
            // 按日期循环统计
            $current_date = $start_date;
            while ($current_date <= $end_date) {
                // 1. 构建访问量查询
                $visit_query = $this->getBaseQuery()
                    ->where('created_at', '>=', $current_date . ' 00:00:00')
                    ->where('created_at', '<=', $current_date . ' 23:59:59');

                if (!empty($channel_codes)) {
                    if (is_array($channel_codes)) {
                        $visit_query->whereIn('channel', $channel_codes);
                    } else {
                        $visit_query->where('channel', $channel_codes);
                    }
                }

                $visits = $visit_query->count('DISTINCT ipaddr');

                // 2. 构建注册数查询
                $register_query = \think\facade\Db::name('tblclients')
                    ->where('datecreated', $current_date);

                if (!empty($channel_codes)) {
                    if (is_array($channel_codes)) {
                        $register_query->whereIn('affiliateid', $channel_codes);
                    } else {
                        $register_query->where('affiliateid', $channel_codes);
                    }
                }

                $registers = $register_query->count();

                // 3. 构建订单查询
                if (!empty($channel_codes)) {
                    $order_query = \think\facade\Db::name('tblclients')
                        ->alias('c')
                        ->join('tblinvoices i', 'c.id = i.userid')
                        ->where('i.date', $current_date)
                        ->where('i.status', 'Paid');

                    if (is_array($channel_codes)) {
                        $order_query->whereIn('c.affiliateid', $channel_codes);
                    } else {
                        $order_query->where('c.affiliateid', $channel_codes);
                    }
                } else {
                    // 全部渠道：直接查询所有订单
                    $order_query = \think\facade\Db::name('tblinvoices')
                        ->where('date', $current_date)
                        ->where('status', 'Paid');
                }

                $order_stats = $order_query->field([
                    'COUNT(' . (empty($channel_codes) ? 'id' : 'i.id') . ') as order_count',
                    'SUM(' . (empty($channel_codes) ? 'total' : 'i.total') . ') as order_amount'
                ])->find();

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
                    'order_amount' => number_format((float)$order_amount, 0)
                ];

                // 日期加一天
                $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
            }

            return array_reverse($roi_data); // 倒序显示，最新日期在前

        } catch (\Exception $e) {
            trace('getRoiDataByChannel查询出错: ' . $e->getMessage(), 'error');
            return [];
        }
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
                // 动态获取TG渠道的channel值
                try {
                    $tgPlatform = \think\facade\Db::name('system_new_promotion_platforms')
                        ->where('platform_name', 'TG')
                        ->where('status', 'active')
                        ->field('channel')
                        ->find();
                    
                    if ($tgPlatform) {
                        $query->where('channel', $tgPlatform['channel']);
                    } else {
                        // 如果没找到TG平台，使用原有逻辑
                        $query->where('channel', '211');
                    }
                } catch (\Exception $e) {
                    // 如果查询失败，使用原有逻辑
                    $query->where('channel', '211');
                }
            } else {
                // 非TG渠道：排除所有推广平台的渠道
                try {
                    $platformChannels = \think\facade\Db::name('system_new_promotion_platforms')
                        ->where('status', 'active')
                        ->column('channel');
                    
                    if (!empty($platformChannels)) {
                        $query->where('channel', 'not in', $platformChannels);
                    } else {
                        // 如果没找到推广平台，使用原有逻辑
                        $query->where('channel', '<>', '211');
                    }
                } catch (\Exception $e) {
                    // 如果查询失败，使用原有逻辑
                    $query->where('channel', '<>', '211');
                }
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


}
