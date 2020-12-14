<?php

declare (strict_types=1);
namespace App\Model;

/**
 * @property string $address 
 * @property int $protocol 
 */
class CoinAddressBlacklist extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'coin_address_blacklist';
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
    protected $casts = ['protocol' => 'integer'];
}