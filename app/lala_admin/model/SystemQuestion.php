<?php
declare (strict_types = 1);

namespace app\lala_admin\model;

use think\admin\Model;

/**
 * 问题答案模型
 * Model层：负责数据表定义和基础数据操作
 */
class SystemQuestion extends Model
{
    /**
     * 数据表名称
     * @var string
     */
    protected $table = 'system_question';

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段
     * @var string
     */
    protected $updateTime = 'updated_at';

    /**
     * 自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;

    /**
     * 类型映射
     * @var array
     */
    public static $typeMap = [
        'ticket' => '工单处理',
        'startup' => '开机处理', 
        'vip_issue' => '大户问题'
    ];

    /**
     * 分类映射
     * @var array
     */
    public static $categoryMap = [
        'network' => '网络问题',
        'hardware' => '硬件问题',
        'software' => '软件问题',
        'account' => '账户问题',
        'billing' => '账单问题',
        'service' => '服务问题',
        'other' => '其他问题'
    ];

    /**
     * 获取类型文本
     * @param string $type
     * @return string
     */
    public static function getTypeText($type)
    {
        return self::$typeMap[$type] ?? '未知';
    }

    /**
     * 获取分类文本
     * @param string $category
     * @return string
     */
    public static function getCategoryText($category)
    {
        return self::$categoryMap[$category] ?? $category;
    }

    /**
     * 获取类型列表
     * @return array
     */
    public static function getTypeList()
    {
        return self::$typeMap;
    }

    /**
     * 获取分类列表
     * @return array
     */
    public static function getCategoryList()
    {
        return self::$categoryMap;
    }

    /**
     * 搜索问题
     * @param array $params 搜索参数
     * @return \think\db\Query
     */
    public function searchQuestions($params = [])
    {
        $query = $this->newQuery();
        
        // 基础条件
        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }
        
        if (!empty($params['category'])) {
            $query->where('category', $params['category']);
        }
        
        // 全文搜索
        if (!empty($params['keyword'])) {
            $query->where(function($q) use ($params) {
                $q->whereOr('title', 'like', "%{$params['keyword']}%")
                  ->whereOr('problem_description', 'like', "%{$params['keyword']}%")
                  ->whereOr('solution_description', 'like', "%{$params['keyword']}%");
            });
        }
        
        return $query->order('created_at desc');
    }

    /**
     * 获取相关问题
     * @param string $type 类型
     * @param string $category 分类
     * @param int $limit 限制数量
     * @return array
     */
    public function getRelatedQuestions($type = '', $category = '', $limit = 5)
    {
        $query = $this->newQuery();
        
        if (!empty($type)) {
            $query->where('type', $type);
        }
        
        if (!empty($category)) {
            $query->where('category', $category);
        }
        
        return $query->field('id,title,type,category,created_at')
                    ->order('created_at desc')
                    ->limit($limit)
                    ->select()
                    ->toArray();
    }
}
