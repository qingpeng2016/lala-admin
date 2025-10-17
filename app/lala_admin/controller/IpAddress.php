<?php
declare (strict_types=1);

namespace app\lala_admin\controller;

use think\admin\Controller;
use think\facade\Db;
use think\facade\Log;

/**
 * IP地址管理
 */
class IpAddress extends Controller
{
    /**
     * IP地址列表
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title = 'IP地址管理';

        // 记录日志
        $this->app->log->info('IpAddress index method called');

        // 获取请求参数
        $get = $this->request->get();
        $this->app->log->info('Request parameters: ' . json_encode($get));

        $query = Db::name('system_new_ip_address_management');
        $this->app->log->info('Query object created');

        // 添加搜索条件
        if (isset($get['ip_address']) && $get['ip_address'] !== '') {
            $query->where('ip_address', 'like', "%{$get['ip_address']}%");
            $this->app->log->info('Added ip_address condition: ' . $get['ip_address']);
        }
        if (isset($get['upstream_provider']) && $get['upstream_provider'] !== '') {
            $query->where('upstream_provider', $get['upstream_provider']);
            $this->app->log->info('Added upstream_provider condition: ' . $get['upstream_provider']);
        }
        if (isset($get['parent_machine']) && $get['parent_machine'] !== '') {
            $query->where('parent_machine', $get['parent_machine']);
            $this->app->log->info('Added parent_machine condition: ' . $get['parent_machine']);
        }
        if (isset($get['region']) && $get['region'] !== '') {
            $query->where('region', $get['region']);
            $this->app->log->info('Added region condition: ' . $get['region']);
        }
        if (isset($get['network_type']) && $get['network_type'] !== '') {
            $query->where('network_type', $get['network_type']);
            $this->app->log->info('Added network_type condition: ' . $get['network_type']);
        }
        if (isset($get['virtualization_system']) && $get['virtualization_system'] !== '') {
            $query->where('virtualization_system', $get['virtualization_system']);
            $this->app->log->info('Added virtualization_system condition: ' . $get['virtualization_system']);
        }
        if (isset($get['status']) && $get['status'] !== '') {
            $query->where('status', $get['status']);
            $this->app->log->info('Added status condition: ' . $get['status']);
        }

        // 执行分页查询
        $this->app->log->info('Executing pagination query');
        $result = $query->order('id desc')->paginate([
            'list_rows' => isset($get['pageSize']) ? intval($get['pageSize']) : 20,
            'page' => isset($get['page']) ? intval($get['page']) : 1,
            'query' => $get,
            'path' => '/admin.html#/lala_admin/ip_address/index.html',
        ], false);
        $this->app->log->info('Pagination query executed, total: ' . $result->total());

        // 格式化数据
        $list = $result->items();

        foreach ($list as &$item) {
            // 处理NULL值，避免htmlentities错误
            $item['upstream_provider'] = $item['upstream_provider'] ?? '';
            $item['parent_machine'] = $item['parent_machine'] ?? '';
            $item['region'] = $item['region'] ?? '';
            $item['network_type'] = $item['network_type'] ?? '';
            $item['virtualization_system'] = $item['virtualization_system'] ?? '';
            $item['ip_address'] = $item['ip_address'] ?? '';
            $item['status'] = $item['status'] ?? 'unused';
            
            // 处理时间
            if (!empty($item['created_at'])) {
                $item['created_at'] = date('Y-m-d H:i:s', strtotime($item['created_at']));
            }
            if (!empty($item['updated_at'])) {
                $item['updated_at'] = date('Y-m-d H:i:s', strtotime($item['updated_at']));
            }
        }

        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get,
            'upstream_provider_list' => $this->getUpstreamProviderList(),
            'region_list' => $this->getRegionList(),
            'network_type_list' => $this->getNetworkTypeList(),
            'virtualization_system_list' => $this->getVirtualizationSystemList(),
            'status_list' => $this->getStatusList()
        ]);
        $this->app->log->info('Variables assigned to view');

        // 渲染视图
        $this->app->log->info('Rendering view');
        return $this->fetch();
    }

    /**
     * 添加IP地址
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add()
    {
        // 记录日志
        Log::info('IpAddress add method called');
        
        // 如果是POST请求，处理表单提交
        if ($this->request->isPost()) {
            // 记录日志
            Log::info('IpAddress add POST request received');
            
            // 获取表单数据
            $data = $this->request->post();
            Log::info('Form data: ' . json_encode($data));
            
            // 数据验证
            $validate = \think\facade\Validate::rule([
                'upstream_provider' => 'require|in:niuzong,qianzhi',
                'parent_machine' => 'max:100',
                'region' => 'require|in:guangzhou,shenzhen,xiamen,hongkong',
                'network_type' => 'require|in:telecom,mobile,unicom,bgp,hk',
                'virtualization_system' => 'in:pve,vf',
                'ip_address_start' => 'require|max:50',
                'status' => 'in:unused,used,reported,abnormal,unknown'
            ])->message([
                'upstream_provider.require' => '所属上游不能为空',
                'upstream_provider.in' => '所属上游必须是niuzong或qianzhi',
                'parent_machine.max' => '所属母机最多100个字符',
                'region.require' => '所属地区不能为空',
                'region.in' => '所属地区必须是guangzhou、shenzhen、xiamen或hongkong',
                'network_type.require' => '网络类型不能为空',
                'network_type.in' => '网络类型必须是telecom、mobile、unicom、bgp或hk',
                'virtualization_system.in' => '虚拟化系统必须是pve或vf',
                'ip_address_start.require' => '起始IP地址不能为空',
                'ip_address_start.max' => 'IP地址最多50个字符',
                'status.in' => '状态必须是unused、used、reported、abnormal或unknown'
            ]);
            
            // 验证数据
            if (!$validate->check($data)) {
                $error = $validate->getError();
                Log::error('Validation error: ' . $error);
                return $this->error($error);
            }
            
            try {
                // 获取IP地址范围
                $ipStart = trim($data['ip_address_start']);
                $ipEnd = trim($data['ip_address_end'] ?? '');
                
                // 生成IP列表
                $ipList = $this->generateIpList($ipStart, $ipEnd);
                
                if (empty($ipList)) {
                    return $this->error('IP地址格式错误');
                }
                
                $successCount = 0;
                $skipCount = 0;
                $skippedIps = [];
                
                // 批量插入IP
                foreach ($ipList as $ip) {
                    // 检查IP地址是否已存在
                    $exists = Db::name('system_new_ip_address_management')
                        ->where('ip_address', $ip)
                        ->find();
                    
                    if ($exists) {
                        $skipCount++;
                        $skippedIps[] = $ip;
                        continue;
                    }
                    
                    // 准备插入数据
                    $insertData = [
                        'upstream_provider' => $data['upstream_provider'],
                        'parent_machine' => $data['parent_machine'] ?? '',
                        'region' => $data['region'],
                        'network_type' => $data['network_type'],
                        'virtualization_system' => $data['virtualization_system'] ?? '',
                        'virt_system_machine_id' => $data['virt_system_machine_id'] ?? 0,
                        'ip_address' => $ip,
                        'status' => $data['status'] ?? 'unused',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // 插入数据
                    $result = Db::name('system_new_ip_address_management')->insert($insertData);
                    
                    if ($result) {
                        $successCount++;
                    }
                }
                
                Log::info("Batch insert result: success={$successCount}, skip={$skipCount}");
                
                // 返回结果
                $message = "添加完成！成功: {$successCount} 条";
                if ($skipCount > 0) {
                    $message .= "，跳过: {$skipCount} 条（已存在）";
                    if (count($skippedIps) <= 5) {
                        $message .= "：" . implode(', ', $skippedIps);
                    }
                }
                
                return json(['code' => 1, 'info' => $message, 'url' => '']);
                
            } catch (\Exception $e) {
                Log::error('Exception in add method: ' . $e->getMessage());
                Log::error('Stack trace: ' . $e->getTraceAsString());
                return $this->error('添加失败: ' . $e->getMessage());
            }
        }
        
        // 分配变量到视图
        $this->assign([
            'upstream_provider_list' => $this->getUpstreamProviderList(),
            'region_list' => $this->getRegionList(),
            'network_type_list' => $this->getNetworkTypeList(),
            'virtualization_system_list' => $this->getVirtualizationSystemList(),
            'status_list' => $this->getStatusList()
        ]);
        
        // 渲染添加表单
        return $this->fetch('form');
    }

    /**
     * 编辑IP地址
     * @auth true
     */
    public function edit()
    {
        Log::info('IpAddress edit method called');
        
        if ($this->request->isPost()) {
            Log::info('IpAddress edit POST request received');
            
            // 获取表单数据
            $data = $this->request->post();
            Log::info('Form data: ' . json_encode($data));
            
            // 数据验证
            $validate = \think\facade\Validate::rule([
                'id' => 'require|integer|gt:0',
                'upstream_provider' => 'require|in:niuzong,qianzhi',
                'parent_machine' => 'max:100',
                'region' => 'require|in:guangzhou,shenzhen,xiamen,hongkong',
                'network_type' => 'require|in:telecom,mobile,unicom,bgp,hk',
                'virtualization_system' => 'in:pve,vf',
                'ip_address' => 'require|max:50',
                'status' => 'in:unused,used,reported,abnormal,unknown'
            ])->message([
                'id.require' => 'ID不能为空',
                'id.integer' => 'ID必须是整数',
                'id.gt' => 'ID必须大于0',
                'upstream_provider.require' => '所属上游不能为空',
                'upstream_provider.in' => '所属上游必须是niuzong或qianzhi',
                'parent_machine.max' => '所属母机最多100个字符',
                'region.require' => '所属地区不能为空',
                'region.in' => '所属地区必须是guangzhou、shenzhen、xiamen或hongkong',
                'network_type.require' => '网络类型不能为空',
                'network_type.in' => '网络类型必须是telecom、mobile、unicom、bgp或hk',
                'virtualization_system.in' => '虚拟化系统必须是pve或vf',
                'ip_address.require' => 'IP地址不能为空',
                'ip_address.max' => 'IP地址最多50个字符',
                'status.in' => '状态必须是unused、used、reported、abnormal或unknown'
            ]);
            
            if (!$validate->check($data)) {
                $error = $validate->getError();
                Log::error('Validation error: ' . $error);
                return $this->error($error);
            }
            
            try {
                // 检查IP地址是否已被其他记录使用
                $exists = Db::name('system_new_ip_address_management')
                    ->where('ip_address', $data['ip_address'])
                    ->where('id', '<>', $data['id'])
                    ->find();
                if ($exists) {
                    return $this->error('该IP地址已被其他记录使用');
                }
                
                // 处理数据
                $updateData = [
                    'upstream_provider' => $data['upstream_provider'],
                    'parent_machine' => $data['parent_machine'],
                    'region' => $data['region'],
                    'network_type' => $data['network_type'],
                    'virtualization_system' => $data['virtualization_system'],
                    'virt_system_machine_id' => $data['virt_system_machine_id'] ?? 0,
                    'ip_address' => $data['ip_address'],
                    'status' => $data['status'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // 更新数据
                $result = Db::name('system_new_ip_address_management')
                    ->where('id', $data['id'])
                    ->update($updateData);
                Log::info('Update result: ' . ($result !== false ? 'success' : 'failed'));
                
                if ($result !== false) {
                    return json(['code' => 1, 'info' => '更新成功', 'url' => '']);
                } else {
                    return $this->error('更新失败或IP地址不存在');
                }
            } catch (\Exception $e) {
                Log::error('Exception in edit method: ' . $e->getMessage());
                return $this->error('更新失败: ' . $e->getMessage());
            }
        }
        
        // 获取要编辑的数据
        $id = $this->request->get('id');
        if (empty($id)) {
            return $this->error('参数错误');
        }
        
        $vo = Db::name('system_new_ip_address_management')
            ->where('id', $id)
            ->find();
        if (empty($vo)) {
            return $this->error('IP地址不存在');
        }
        
        $this->assign([
            'vo' => $vo,
            'upstream_provider_list' => $this->getUpstreamProviderList(),
            'region_list' => $this->getRegionList(),
            'network_type_list' => $this->getNetworkTypeList(),
            'virtualization_system_list' => $this->getVirtualizationSystemList(),
            'status_list' => $this->getStatusList()
        ]);
        return $this->fetch('form');
    }

    /**
     * 批量导入IP地址
     * @auth true
     */
    public function import()
    {
        Log::info('IpAddress import method called');
        
        if ($this->request->isPost()) {
            Log::info('IpAddress import POST request received');
            
            // 调试：记录所有POST数据
            Log::info('POST data: ' . json_encode($this->request->post()));
            Log::info('FILES data: ' . json_encode($_FILES));
            
            // 获取上传的文件
            $file = $this->request->file('csv_file');
            
            if (empty($file)) {
                Log::info('No file uploaded - checking $_FILES directly');
                if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                    Log::info('File found in $_FILES: ' . $_FILES['csv_file']['name']);
                } else {
                    Log::info('No file in $_FILES or upload error: ' . ($_FILES['csv_file']['error'] ?? 'not set'));
                }
                return $this->error('请上传CSV文件');
            }
            
            Log::info('File uploaded: ' . $file->getOriginalName());
            
            try {
                // 检查文件类型
                $extension = strtolower($file->getOriginalExtension());
                $mimeType = $file->getMime();
                
                // 更宽松的文件类型检查
                $allowedExtensions = ['csv', 'txt'];
                $allowedMimeTypes = [
                    'text/csv',
                    'text/plain',
                    'application/csv',
                    'application/vnd.ms-excel'
                ];
                
                Log::info("File validation - Extension: {$extension}, MimeType: {$mimeType}");
                
                if (!in_array($extension, $allowedExtensions) && !in_array($mimeType, $allowedMimeTypes)) {
                    Log::info("File validation failed - Extension: {$extension}, MimeType: {$mimeType}");
                    return $this->error('只支持CSV格式文件，当前文件类型：' . $extension . ' (' . $mimeType . ')');
                }
                
                Log::info("File validation passed");
                
                // 读取文件内容
                $content = file_get_contents($file->getPathname());
                
                // 转换编码（如果是GBK编码）
                $encoding = mb_detect_encoding($content, ['UTF-8', 'GBK', 'GB2312']);
                if ($encoding !== 'UTF-8') {
                    $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                }
                
                // 解析CSV
                $lines = explode("\n", $content);
                $successCount = 0;
                $skipCount = 0;
                $errorCount = 0;
                $errors = [];
                
                // 跳过第一行（标题行）
                array_shift($lines);
                
                // 上游映射
                $upstreamMap = [
                    '牛总' => 'niuzong',
                    '千智' => 'qianzhi'
                ];
                
                // 地区映射
                $regionMap = [
                    '广州' => 'guangzhou',
                    '深圳' => 'shenzhen',
                    '厦门' => 'xiamen',
                    '香港' => 'hongkong',
                    '东莞' => 'shenzhen' // 东莞归类为深圳
                ];
                
                // 网络类型映射
                $networkMap = [
                    '电信' => 'telecom',
                    '移动' => 'mobile',
                    '联通' => 'unicom',
                    'BGP' => 'bgp',
                    'HK' => 'hk'
                ];
                
                foreach ($lines as $index => $line) {
                    $line = trim($line);
                    if (empty($line)) {
                        continue;
                    }
                    
                    // 解析CSV行
                    $fields = str_getcsv($line);
                    
                    if (count($fields) < 4) {
                        $errors[] = "第" . ($index + 2) . "行数据不完整";
                        $errorCount++;
                        continue;
                    }
                    
                    // 获取字段值
                    $upstream = trim($fields[0]);
                    $region = trim($fields[1]);
                    $networkType = trim($fields[2]);
                    $ipAddress = trim($fields[3]);
                    
                    // 检查IP地址是否已存在
                    $exists = Db::name('system_new_ip_address_management')
                        ->where('ip_address', $ipAddress)
                        ->find();
                    
                    if ($exists) {
                        $skipCount++;
                        continue;
                    }
                    
                    // 映射字段值
                    $upstreamProvider = $upstreamMap[$upstream] ?? '';
                    $regionCode = $regionMap[$region] ?? '';
                    $networkTypeCode = $networkMap[$networkType] ?? '';
                    
                    // 验证必填字段
                    if (empty($upstreamProvider) || empty($regionCode) || empty($networkTypeCode) || empty($ipAddress)) {
                        $errors[] = "第" . ($index + 2) . "行：字段映射失败（上游={$upstream}, 地区={$region}, 类型={$networkType}, IP={$ipAddress}）";
                        $errorCount++;
                        continue;
                    }
                    
                    // 插入数据
                    $data = [
                        'upstream_provider' => $upstreamProvider,
                        'parent_machine' => '', // 默认为空
                        'region' => $regionCode,
                        'network_type' => $networkTypeCode,
                        'virtualization_system' => '', // 默认为空
                        'virt_system_machine_id' => 0,
                        'ip_address' => $ipAddress,
                        'status' => 'unused', // 默认为未使用
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $result = Db::name('system_new_ip_address_management')->insert($data);
                    
                    if ($result) {
                        $successCount++;
                    } else {
                        $errors[] = "第" . ($index + 2) . "行：插入失败";
                        $errorCount++;
                    }
                }
                
                // 返回结果
                $message = "导入完成！成功: {$successCount} 条，跳过: {$skipCount} 条，失败: {$errorCount} 条";
                if (!empty($errors)) {
                    $message .= "\n错误详情：\n" . implode("\n", array_slice($errors, 0, 10));
                    if (count($errors) > 10) {
                        $message .= "\n... 还有 " . (count($errors) - 10) . " 条错误";
                    }
                }
                
                Log::info("Import result: {$message}");
                
                return json(['code' => 1, 'info' => $message, 'url' => '']);
                
            } catch (\Exception $e) {
                Log::error('Exception in import method: ' . $e->getMessage());
                Log::error('Stack trace: ' . $e->getTraceAsString());
                return $this->error('导入失败: ' . $e->getMessage());
            }
        }
        
        // 分配变量到视图
        $this->assign([]);
        
        // 渲染导入表单
        return $this->fetch('import');
    }

    /**
     * 删除IP地址
     * @auth true
     */
    public function remove()
    {
        Log::info('IpAddress remove method called');
        
        $id = $this->request->post('id');
        if (empty($id)) {
            return $this->error('参数错误');
        }
        
        try {
            $result = Db::name('system_new_ip_address_management')
                ->where('id', $id)
                ->delete();
            Log::info('Delete result: ' . ($result ? 'success' : 'failed'));
            
            if ($result) {
                return json(['code' => 1, 'info' => '删除成功']);
            } else {
                return $this->error('删除失败或IP地址不存在');
            }
        } catch (\Exception $e) {
            Log::error('Exception in remove method: ' . $e->getMessage());
            return $this->error('删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 生成IP地址列表
     * @param string $ipStart 起始IP
     * @param string $ipEnd 结束IP（可选）
     * @return array
     */
    private function generateIpList($ipStart, $ipEnd = '')
    {
        // 如果没有结束IP，只返回起始IP
        if (empty($ipEnd)) {
            return [$ipStart];
        }
        
        // 验证IP格式
        if (!filter_var($ipStart, FILTER_VALIDATE_IP) || !filter_var($ipEnd, FILTER_VALIDATE_IP)) {
            return [];
        }
        
        // 将IP地址转换为长整型
        $startLong = ip2long($ipStart);
        $endLong = ip2long($ipEnd);
        
        if ($startLong === false || $endLong === false) {
            return [];
        }
        
        // 确保起始IP小于结束IP
        if ($startLong > $endLong) {
            return [];
        }
        
        // 限制最多生成1000个IP
        if (($endLong - $startLong) > 1000) {
            return [];
        }
        
        // 生成IP列表
        $ipList = [];
        for ($i = $startLong; $i <= $endLong; $i++) {
            $ipList[] = long2ip($i);
        }
        
        return $ipList;
    }

    /**
     * 获取上游供应商列表
     * @return array
     */
    private function getUpstreamProviderList()
    {
        return [
            'niuzong' => '牛总',
            'qianzhi' => '千智'
        ];
    }

    /**
     * 获取地区列表
     * @return array
     */
    private function getRegionList()
    {
        return [
            'guangzhou' => '广州',
            'shenzhen' => '深圳',
            'xiamen' => '厦门',
            'hongkong' => '香港'
        ];
    }

    /**
     * 获取网络类型列表
     * @return array
     */
    private function getNetworkTypeList()
    {
        return [
            'telecom' => '电信',
            'mobile' => '移动',
            'unicom' => '联通',
            'bgp' => 'BGP',
            'hk' => 'HK'
        ];
    }

    /**
     * 获取虚拟化系统列表
     * @return array
     */
    private function getVirtualizationSystemList()
    {
        return [
            'pve' => 'PVE',
            'vf' => 'VF'
        ];
    }

    /**
     * 获取状态列表
     * @return array
     */
    private function getStatusList()
    {
        return [
            'unused' => '未使用',
            'used' => '已使用',
            'reported' => '通报',
            'abnormal' => '异常',
            'unknown' => '未知'
        ];
    }
}

