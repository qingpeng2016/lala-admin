<?php
require_once 'telegram_bot.php';

// 配置
$bot_config = [
    'bot_token' => 'YOUR_BOT_TOKEN_HERE',
    'database' => [
        'host' => 'localhost',
        'database' => 'telegram_bot',
        'username' => 'root',
        'password' => 'password'
    ]
];

$bot = new TelegramBot($bot_config['bot_token'], $bot_config['database']);

// 获取统计数据
$stats = $bot->getStats();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Bot 管理后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css" rel="stylesheet">
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center my-4">🤖 Telegram Bot 管理后台</h1>
            </div>
        </div>
        
        <!-- 统计卡片 -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo array_sum(array_column($stats, 'count')); ?></div>
                    <div class="stat-label">总点击次数</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="stat-number"><?php echo count(array_unique(array_column($stats, 'user_id'))); ?></div>
                    <div class="stat-label">活跃用户数</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="stat-number"><?php echo count($stats); ?></div>
                    <div class="stat-label">今日操作数</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="stat-number"><?php echo date('Y-m-d'); ?></div>
                    <div class="stat-label">当前日期</div>
                </div>
            </div>
        </div>
        
        <!-- 详细统计表格 -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>📊 详细统计数据</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>操作类型</th>
                                        <th>点击次数</th>
                                        <th>日期</th>
                                        <th>占比</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_clicks = array_sum(array_column($stats, 'count'));
                                    foreach ($stats as $stat): 
                                        $percentage = $total_clicks > 0 ? round(($stat['count'] / $total_clicks) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $action_labels = [
                                                'action_kefu' => '👨‍💼 联系客服',
                                                'action_usergroup' => '👥 进入用户群',
                                                'action_website' => '🌐 访问官网',
                                                'action_app' => '📱 下载APP'
                                            ];
                                            echo $action_labels[$stat['action']] ?? $stat['action'];
                                            ?>
                                        </td>
                                        <td><span class="badge bg-primary"><?php echo $stat['count']; ?></span></td>
                                        <td><?php echo $stat['date']; ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" style="width: <?php echo $percentage; ?>%">
                                                    <?php echo $percentage; ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 图表 -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>📈 操作类型分布</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="actionChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>📅 每日趋势</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script>
        // 准备图表数据
        const statsData = <?php echo json_encode($stats); ?>;
        
        // 操作类型分布图
        const actionLabels = {
            'action_kefu': '联系客服',
            'action_usergroup': '进入用户群', 
            'action_website': '访问官网',
            'action_app': '下载APP'
        };
        
        const actionCounts = {};
        statsData.forEach(stat => {
            if (!actionCounts[stat.action]) {
                actionCounts[stat.action] = 0;
            }
            actionCounts[stat.action] += parseInt(stat.count);
        });
        
        new Chart(document.getElementById('actionChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(actionCounts).map(key => actionLabels[key] || key),
                datasets: [{
                    data: Object.values(actionCounts),
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB', 
                        '#FFCE56',
                        '#4BC0C0'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // 每日趋势图
        const dateCounts = {};
        statsData.forEach(stat => {
            if (!dateCounts[stat.date]) {
                dateCounts[stat.date] = 0;
            }
            dateCounts[stat.date] += parseInt(stat.count);
        });
        
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: Object.keys(dateCounts),
                datasets: [{
                    label: '每日点击次数',
                    data: Object.values(dateCounts),
                    borderColor: '#36A2EB',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
