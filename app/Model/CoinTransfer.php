<?php

declare (strict_types=1);
namespace App\Model;

/**
 * @property string $tx_hash 
 * @property string $from 
 * @property string $to 
 * @property int $protocol 
 * @property string $coin 
 * @property float $amount 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class CoinTransfer extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'coin_transfer';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['tx_hash', 'from', 'to', 'protocol', 'coin', 'amount'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['protocol' => 'integer', 'amount' => 'float', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}