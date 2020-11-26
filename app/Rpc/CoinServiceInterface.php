<?php

namespace App\Rpc;

interface CoinServiceInterface
{
    public function address(int $coin, int $protocol);
}
