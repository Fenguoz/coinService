<?php

declare (strict_types=1);
namespace App\Model;

use App\Constants\Setting as ConstantsSetting;
/**
 * @property string $k 
 * @property string $v 
 * @property string $module 
 * @property string $remark 
 */
class Setting extends Model
{
    /**
     * Add nullable creation and update timestamps to the table.
     *
     * @param int $precision
     */
    public $timestamps = false;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'setting';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];
    /**
     * getList
     * 获取列表
     * @param array $where 查询条件
     * @param array $order 排序条件
     * @param int $offset 偏移
     * @param int $limit 条数
     * @return array
     */
    public static function getList(array $where = [], array $order = [], int $offset = 0, int $limit = 0)
    {
        $query = self::select('k', 'v', 'module', 'remark');
        // 循环增加查询条件
        foreach ($where as $k => $v) {
            if ($v || $v != null) {
                $query = $query->where($k, $v);
            }
        }
        // 追加排序
        if ($order && is_array($order)) {
            foreach ($order as $k => $v) {
                $query = $query->orderBy($k, $v);
            }
        }
        // 是否分页
        if ($limit) {
            $query = $query->offset($offset)->limit($limit);
        }
        $query = $query->pluck('v', 'k');
        return $query ? $query->all() : [];
    }
    public static function getValueByKey(string $key)
    {
        return self::where('k', $key)->value('v');
    }
    public static function getListByModule(int $module, array $order = [], int $offset = 0, int $limit = 0)
    {
        return self::getList(['module' => ConstantsSetting::$__names[$module]], $order, $offset, $limit);
    }
}