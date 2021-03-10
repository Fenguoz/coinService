<?php

declare (strict_types=1);
namespace App\Model;

/**
 * @property string $coin_name 
 * @property int $protocol 
 * @property string $rpc_url 
 * @property string $rpc_user 
 * @property string $rpc_password 
 * @property string $contract_address 
 * @property int $decimals 
 * @property string $tx_speed 
 * @property int $confirm 
 * @property float $funds_collection_min_amount 
 * @property string $recv_address 
 * @property string $send_address 
 */
class CoinChainConfig extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'coin_chain_config';
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
    protected $casts = ['protocol' => 'integer', 'decimals' => 'integer', 'confirm' => 'integer', 'funds_collection_min_amount' => 'float'];
    public static function getConfigByCode(string $coin, int $protocol) : array
    {
        $config = self::query()->where('coin_name', $coin)->where('protocol', $protocol)->first();
        return $config ? $config->toArray() : [];
    }
}