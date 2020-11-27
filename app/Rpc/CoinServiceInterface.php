<?php

namespace App\Rpc;

interface CoinServiceInterface
{
    public function newAddress(int $coin, int $protocol);
    public function balance(string $address, int $coin, int $protocol);
    public function transfer(string $from, string $to, string $number, int $coin, int $protocol);
}
