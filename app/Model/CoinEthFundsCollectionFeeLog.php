<?php

declare (strict_types=1);
namespace App\Model;

/**
 * @property string $tx_hash 
 * @property string $coin_name 
 * @property float $amount 
 * @property string $address 
 * @property int $status 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class CoinEthFundsCollectionFeeLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'coin_eth_funds_collection_fee_logs';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['tx_hash', 'coin_name', 'amount', 'address', 'type'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['amount' => 'float', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}