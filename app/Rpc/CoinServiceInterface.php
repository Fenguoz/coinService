<?php

namespace App\Rpc;

interface CoinServiceInterface
{
    public function newAddress(array $config, int $protocol);

    public function balance(string $address, array $config, int $protocol);

    public function transfer(string $from, string $to, string $amount, array $config, int $protocol);

    public function blockNumber(array $config, int $protocol);

    public function transactionReceipt(string $txHash, array $config, int $protocol);
    
    public function receiptStatus(string $txHash, array $config, int $protocol);

    public function blockByNumber(int $blockNumber, array $config, int $protocol);

    public function privateKeyToAddress(string $privateKey, array $config, int $protocol);
}
