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
    public static function getValueByKey(string $key)
    {
        return self::where('k', $key)->value('v');
    }
    public static function getListByModule(int $module, array $order = [], int $offset = 0, int $limit = 0)
    {
        return self::getList(['module' => ConstantsSetting::$__names[$module]], $order, $offset, $limit);
    }
}