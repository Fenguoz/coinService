<?php

namespace App\Rpc;

interface CoinServiceInterface
{
    public function newAddress(string $coin, int $protocol);

    public function balance(string $address, string $coin, int $protocol);

    public function transfer(string $from, string $to, string $amount, string $coin, int $protocol);

    public function blockNumber(string $coin, int $protocol);

    public function transactionReceipt(string $txHash, string $coin, int $protocol);
    
    public function receiptStatus(string $txHash, string $coin, int $protocol);

    public function blockByNumber(int $blockNumber, string $coin, int $protocol);

    public function privateKeyToAddress(string $privateKey, string $coin, int $protocol);
}
