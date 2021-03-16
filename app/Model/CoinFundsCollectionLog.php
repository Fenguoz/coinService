<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property string $tx_hash 
 * @property string $coin_name 
 * @property float $amount 
 * @property string $address 
 * @property int $type 
 * @property int $protocol 
 * @property int $status 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class CoinFundsCollectionLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'coin_funds_collection_logs';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['tx_hash', 'coin_name', 'amount', 'address', 'type', 'protocol', 'status'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'amount' => 'float', 'type' => 'integer', 'protocol' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}