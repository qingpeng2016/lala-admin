<?php
declare (strict_types=1);

namespace app\lala_admin\model;

use think\Model;

/**
 * 账单提醒模型
 */
class BillReminder extends Model
{
    // 设置表名
    protected $name = 'system_new_tblhosting_notes';
    
    // 设置字段信息
    protected $schema = [
        'id' => 'int',
        'invoice_id' => 'int',
        'employee_name' => 'string',
        'userid' => 'int',
        'email' => 'string',
        'product_info' => 'text',
        'adjust_amount' => 'string',
        'content' => 'text',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    /**
     * 获取发票状态
     * @return string
     */
    public function getInvoiceStatusAttr()
    {
        if (empty($this->invoice_id)) {
            return '无发票';
        }
        
        $invoice = \think\facade\Db::name('tblinvoices')->where('id', $this->invoice_id)->find();
        return $invoice['status'] ?? '未知';
    }
    
    /**
     * 获取发票金额
     * @return string
     */
    public function getInvoiceTotalAttr()
    {
        if (empty($this->invoice_id)) {
            return '0.00';
        }
        
        $invoice = \think\facade\Db::name('tblinvoices')->where('id', $this->invoice_id)->find();
        return $invoice['total'] ?? '0.00';
    }
    
    /**
     * 格式化商品信息
     * @return string
     */
    public function getProductInfoFormattedAttr()
    {
        if (empty($this->product_info)) {
            return '-';
        }
        return nl2br(htmlspecialchars($this->product_info));
    }
    
    /**
     * 格式化备注内容
     * @return string
     */
    public function getContentFormattedAttr()
    {
        if (empty($this->content)) {
            return '-';
        }
        return nl2br(htmlspecialchars($this->content));
    }
    
    /**
     * 格式化创建时间
     * @return string
     */
    public function getCreatedAtFormattedAttr()
    {
        if (empty($this->created_at)) {
            return '-';
        }
        return date('Y-m-d H:i:s', strtotime($this->created_at));
    }
    
    /**
     * 格式化更新时间
     * @return string
     */
    public function getUpdatedAtFormattedAttr()
    {
        if (empty($this->updated_at)) {
            return '-';
        }
        return date('Y-m-d H:i:s', strtotime($this->updated_at));
    }
    
    /**
     * 关联发票信息
     */
    public function invoice()
    {
        return $this->belongsTo('Invoice', 'invoice_id', 'id');
    }
    
    /**
     * 关联用户信息
     */
    public function client()
    {
        return $this->belongsTo('Client', 'userid', 'id');
    }
}
