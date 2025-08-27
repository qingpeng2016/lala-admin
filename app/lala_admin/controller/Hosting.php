<?php
declare (strict_types=1);

namespace app\lala_admin\controller;

use think\admin\Controller;
use think\facade\Db;

/**
 * 持有商品管理
 */
class Hosting extends Controller
{
    /**
     * 持有商品列表
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title = '持有商品管理';

        // 获取请求参数
        $get = $this->request->get();

        // 创建查询对象
        $query = Db::name('tblhosting');

        // 添加搜索条件
        if (isset($get['id']) && $get['id'] !== '') {
            $query->where('id', $get['id']);
        }
        if (isset($get['userid']) && $get['userid'] !== '') {
            $query->where('userid', $get['userid']);
        }
        if (isset($get['domainstatus']) && $get['domainstatus'] !== '') {
            $query->where('domainstatus', $get['domainstatus']);
        }

        // 始终关联tblclients表获取邮箱信息，同时关联tblproducts表获取产品名称
        $query->join('tblclients c', 'tblhosting.userid = c.id', 'LEFT')
            ->join('tblproducts p', 'tblhosting.packageid = p.id', 'LEFT')
            ->field('tblhosting.*, c.email, p.name as product_name');

        // 邮箱搜索条件
        if (isset($get['email']) && $get['email'] !== '') {
            $query->where('c.email', 'like', "%{$get['email']}%");
        }

        // 执行分页查询
        $result = $query->order('tblhosting.id desc')->paginate([
            'list_rows' => 20,
            'page' => $get['page'] ?? 1,
            'query' => $get, // 传递所有查询参数，确保分页链接包含搜索条件
            'path' => '/admin.html#/lala_admin/hosting/index.html', // 使用正确的路径
        ], false);

        // 格式化数据
        $list = $result->items();

        // 处理数据
        foreach ($list as &$item) {
            // 格式化时间
            if (!empty($item['regdate'])) {
                $item['regdate'] = date('Y-m-d', strtotime($item['regdate']));
            }
            if (!empty($item['nextduedate'])) {
                $item['nextduedate'] = date('Y-m-d', strtotime($item['nextduedate']));
            }
            if (!empty($item['nextinvoicedate'])) {
                $item['nextinvoicedate'] = date('Y-m-d', strtotime($item['nextinvoicedate']));
            }
            if (!empty($item['created_at'])) {
                $item['created_at'] = date('Y-m-d H:i:s', strtotime($item['created_at']));
            }

            // 格式化状态
            $item['domainstatus_text'] = $this->getDomainStatusText($item['domainstatus']);

            // 格式化金额
            $item['firstpaymentamount_formatted'] = number_format(floatval($item['firstpaymentamount'] ?? 0), 2);
            $item['amount_formatted'] = number_format(floatval($item['amount'] ?? 0), 2);

            // 格式化磁盘使用率
            if ($item['disklimit'] > 0) {
                $item['disk_usage_percent'] = round(($item['diskusage'] / $item['disklimit']) * 100, 2);
            } else {
                $item['disk_usage_percent'] = 0;
            }

            // 格式化带宽使用率
            if ($item['bwlimit'] > 0) {
                $item['bw_usage_percent'] = round(($item['bwusage'] / $item['bwlimit']) * 100, 2);
            } else {
                $item['bw_usage_percent'] = 0;
            }
        }

        // 分配变量到视图
        $this->assign([
            'list' => $list,
            'pagehtml' => $result->render(),
            'get' => $get,
            'domainstatus_list' => [
                'Pending' => '待处理',
                'Active' => '活跃',
                'Suspended' => '已暂停',
                'Terminated' => '已终止',
                'Cancelled' => '已取消',
                'Fraud' => '欺诈',
                'Completed' => '已完成'
            ]
        ]);

        // 渲染视图
        return $this->fetch();
    }

    /**
     * 创建付款链接
     * @auth true
     */
    public function createPaymentLink()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();

            $hostingIds = $post['hosting_ids'] ?? [];
            $paymentMethod = $post['payment_method'] ?? 'coolpaybitocin';
            $adjustAmount = floatval($post['adjust_amount'] ?? 0);
            $paymentNote = $post['payment_note'] ?? '';

            if (empty($hostingIds)) {
                return json(['code' => 0, 'msg' => '请选择要创建付款链接的商品']);
            }

            // 检查所有商品是否存在且属于同一用户
            $userIds = [];
            $existingHostingIds = [];
            foreach ($hostingIds as $hostingId) {
                $hosting = Db::name('tblhosting')->where('id', $hostingId)->find();
                if ($hosting) {
                    $userIds[] = $hosting['userid'];
                    $existingHostingIds[] = $hostingId;
                }
            }

            // 检查是否有不存在的商品
            if (count($existingHostingIds) !== count($hostingIds)) {
                $missingIds = array_diff($hostingIds, $existingHostingIds);
                return json(['code' => 0, 'msg' => '以下商品ID不存在：' . implode(', ', $missingIds)]);
            }

            $uniqueUserIds = array_unique($userIds);
            if (count($uniqueUserIds) > 1) {
                return json(['code' => 0, 'msg' => '选中的商品必须属于同一用户，当前包含多个用户：' . implode(', ', $uniqueUserIds)]);
            }

            $results = [];
            $successCount = 0;
            $skipCount = 0;
            $invoiceItems = []; // 存储所有需要创建发票项目的商品
            $totalAmount = 0; // 总金额
            $skippedItems = []; // 存储跳过的商品信息

            foreach ($hostingIds as $hostingId) {
                // 查询该服务器的未付订单
                $existingInvoice = Db::name('tblinvoiceitems')
                    ->alias('ii')
                    ->join('tblinvoices i', 'ii.invoiceid = i.id')
                    ->field('ii.*, i.id as invoice_id')
                    ->where('ii.relid', $hostingId)
                    ->where('ii.type', 'Hosting')
                    ->where('i.status', 'Unpaid')
                    ->find();

                if ($existingInvoice) {
                    // 如果存在未付订单，记录信息用于后续更新
                    $skippedItems[] = [
                        'hosting_id' => $hostingId,
                        'existing_invoice' => $existingInvoice
                    ];
                    $skipCount++;
                } else {
                    // 如果不存在未付订单，创建新的订单
                    try {
                        // 获取hosting信息
                        $hosting = Db::name('tblhosting')->where('id', $hostingId)->find();
                        if (!$hosting) {
                            $results[] = [
                                'hosting_id' => $hostingId,
                                'status' => 'error',
                                'message' => '商品信息不存在'
                            ];
                            continue;
                        }

                        // 计算最终金额（暂时不处理加减钱）
                        $finalAmount = floatval($hosting['amount']);

                        // 收集发票项目数据（暂不插入数据库）
                        $invoiceItems[] = [
                            'userid' => $hosting['userid'],
                            'relid' => $hostingId,
                            'type' => 'Hosting',
                            'description' => '商品续费 - ' . ($hosting['domain'] ?? '未知域名'),
                            'amount' => $finalAmount,
                            'taxed' => 0,
                            'duedate' => date('Y-m-d', strtotime('+7 days')),
                            'paymentmethod' => $paymentMethod,
                            'notes' => ''
                        ];

                        $successCount++;
                    } catch (\Exception $e) {
                        $results[] = [
                            'hosting_id' => $hostingId,
                            'status' => 'error',
                            'message' => '创建失败：' . $e->getMessage()
                        ];
                    }
                }
            }

            // 无论什么情况都要创建invoice表
            // 开启事务
            Db::startTrans();
            try {
                // 获取用户ID（已经确认是同一用户）
                $userId = $uniqueUserIds[0];

                // 计算总金额（包括新创建和已有的发票项目）
                $totalAmount = 0;

                // 添加新创建的发票项目金额
                foreach ($invoiceItems as $item) {
                    $totalAmount += $item['amount'];
                }

                // 添加已有发票项目的金额
                foreach ($skippedItems as $skippedItem) {
                    $totalAmount += $skippedItem['existing_invoice']['amount'];
                }

                // 创建汇总发票
                $invoiceData = [
                    'userid' => $userId,
                    'invoicenum' => '', // 发票号码，可以为空
                    'date' => date('Y-m-d'),
                    'duedate' => date('Y-m-d', strtotime('+7 days')),
                    'datepaid' => '0000-00-00 00:00:00',
                    'last_capture_attempt' => '0000-00-00 00:00:00',
                    'date_refunded' => '0000-00-00 00:00:00',
                    'date_cancelled' => '0000-00-00 00:00:00',
                    'subtotal' => $totalAmount,
                    'credit' => 0.00,
                    'tax' => 0.00,
                    'tax2' => 0.00,
                    'total' => $totalAmount,
                    'taxrate' => 0.000,
                    'taxrate2' => 0.000,
                    'status' => 'Unpaid',
                    'paymentmethod' => $paymentMethod,
                    'paymethodid' => null,
                    'notes' => $paymentNote,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $invoiceId = Db::name('tblinvoices')->insertGetId($invoiceData);

                // 创建新的发票项目
                foreach ($invoiceItems as $item) {
                    $item['invoiceid'] = $invoiceId;

                    $invoiceItemId = Db::name('tblinvoiceitems')->insertGetId($item);

                    $results[] = [
                        'hosting_id' => $item['relid'],
                        'status' => 'success',
                        'message' => '付款链接创建成功',
                        'invoice_id' => $invoiceId,
                        'invoice_item_id' => $invoiceItemId,
                        'amount' => $item['amount']
                    ];
                }

                // 更新已有发票项目的invoiceid（如果有跳过的商品）
                if ($skipCount > 0) {
                    // 收集需要删除的老发票ID
                    $oldInvoiceIds = [];

                    foreach ($skippedItems as $skippedItem) {
                        $hostingId = $skippedItem['hosting_id'];
                        $existingItem = $skippedItem['existing_invoice'];

                        // 收集老发票ID（避免重复）
                        if (!in_array($existingItem['invoice_id'], $oldInvoiceIds)) {
                            $oldInvoiceIds[] = $existingItem['invoice_id'];
                        }

                        // 更新发票项目的invoiceid
                        Db::name('tblinvoiceitems')
                            ->where('id', $existingItem['id'])
                            ->update(['invoiceid' => $invoiceId]);

                        $results[] = [
                            'hosting_id' => $hostingId,
                            'status' => 'updated',
                            'message' => '已更新到汇总发票',
                            'invoice_id' => $invoiceId,
                            'invoice_item_id' => $existingItem['id'],
                            'amount' => $existingItem['amount']
                        ];
                    }

                    // 删除老的未付发票记录
                    foreach ($oldInvoiceIds as $oldInvoiceId) {
                        // 检查该发票是否还有其他不属于本次hostingIds的发票项目
                        $otherInvoiceItems = Db::name('tblinvoiceitems')
                            ->where('invoiceid', $oldInvoiceId)
                            ->where('type', 'Hosting')
                            ->whereNotIn('relid', $hostingIds)
                            ->find();
                        
                        if ($otherInvoiceItems) {
                            // 如果存在其他发票项目，回滚事务并报错
                            Db::rollback();
                            return json(['code' => 0, 'msg' => '发票ID ' . $oldInvoiceId . ' 还包含其他商品，无法删除']);
                        }
                        
                        // 先删除对应的账单提醒记录
                        Db::name('system_new_tblhosting_notes')
                            ->where('invoice_id', $oldInvoiceId)
                            ->delete();

                        // 再删除发票记录
                        Db::name('tblinvoices')
                            ->where('id', $oldInvoiceId)
                            ->delete();
                    }
                }

                // 写入商品备注表（只记录一条，包含所有商品信息）
                if (!empty($hostingIds) && !empty($paymentNote)) {
                    // 获取第一个商品的信息来获取用户ID和邮箱
                    $firstHosting = Db::name('tblhosting')->where('id', $hostingIds[0])->find();
                    if ($firstHosting) {
                        // 获取用户邮箱
                        $client = Db::name('tblclients')->where('id', $firstHosting['userid'])->find();
                        $email = $client['email'] ?? '';

                        // 构建商品信息字符串
                        $productInfoLines = [];
                        foreach ($hostingIds as $hostingId) {
                            $hosting = Db::name('tblhosting')->where('id', $hostingId)->find();
                            if ($hosting) {
                                // 获取产品名称
                                $product = Db::name('tblproducts')->where('id', $hosting['packageid'])->find();
                                $productName = $product['name'] ?? '未知产品';

                                $productInfoLines[] ='hostingId:' . $hostingId . ', 商品名称:' . $productName;
                            }
                        }
                        $productInfo = implode("\n", $productInfoLines);

                        // 插入备注记录
                        $noteData = [
                            'employee_name' => 'admin',
                            'userid' => $firstHosting['userid'],
                            'email' => $email,
                            'product_info' => $productInfo,
                            'content' => $paymentNote,
                            'invoice_id' => $invoiceId,
                            'adjust_amount' => $adjustAmount,
                            'status' => 'Wait',
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];

                        Db::name('system_new_tblhosting_notes')->insert($noteData);
                    }
                }

                // 提交事务
                Db::commit();

            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return json(['code' => 0, 'msg' => '创建汇总发票失败：' . $e->getMessage()]);
            }

            return json([
                'code' => 1,
                'msg' => "处理完成：成功创建 {$successCount} 个，跳过 {$skipCount} 个，汇总发票ID: " . ($invoiceId ?? '无'),
                'data' => $results
            ]);
        }

        return json(['code' => 0, 'msg' => '请求方式错误']);
    }

    /**
     * 获取域名状态文本
     * @param string $status
     * @return string
     */
    private function getDomainStatusText($status)
    {
        $statusMap = [
            'Pending' => '待处理',
            'Active' => '活跃',
            'Suspended' => '已暂停',
            'Terminated' => '已终止',
            'Cancelled' => '已取消',
            'Fraud' => '欺诈',
            'Completed' => '已完成'
        ];
        return $statusMap[$status] ?? '未知';
    }
}
