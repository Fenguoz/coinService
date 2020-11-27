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
 * @property int $confirm 
 * @property int $decimals 
 * @property string $tx_speed 
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
    protected $casts = ['protocol' => 'integer', 'confirm' => 'integer', 'decimals' => 'integer'];
    public static function getConfigByCode(string $coin, int $protocol) : array
    {
        $config = self::query()->where('coin_name', $coin)->where('protocol', $protocol)->first();
        return $config ? $config->toArray() : [];
    }
}