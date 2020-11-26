<?php

declare (strict_types=1);
namespace App\Model;

/**
 * @property string $address 
 * @property string $key 
 * @property int $protocol 
 */
class CoinAddress extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'coin_address';
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