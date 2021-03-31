<?php

namespace Driver\Coin\TRON;

use App\Constants\ErrorCode;
use Driver\Coin\AbstractService;
use GuzzleHttp\Client;
use Tron\Api;
use Tron\Address;
use Tron\Exceptions\TransactionException;
use Tron\Exceptions\TronErrorException;
use Tron\TRX;
use Tron\TRC20;

class Service extends AbstractService
{
    protected $tron;

    public function __construct($config)
    {
        if (!$config) {
            return $this->error(ErrorCode::DATA_NOT_EXIST);
        }
        if (!isset($this->config['rpc_url'])) {
            return $this->error(ErrorCode::RPC_URL_REQUIRED);
        }

        $this->config = $config;

        $api = new Api(new Client([
            'base_uri' => $this->config['rpc_url'],
        ]));
        if (isset($this->config['coin_name']) && $this->config['coin_name'] != 'TRX') {
            $this->wallet = new TRC20($api, $this->config);
        } else {
            $this->wallet = new TRX($api, $this->config);
        }
    }

    public function newAddress()
    {
        try {
            $address = $this->wallet->generateAddress();
        } catch (TronErrorException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        } catch (TransactionException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        }
        return $this->_notify([
            'mnemonic' => '',
            'key' => $address->privateKey,
            'address' => $address->address,
        ]);
    }

    public function balance(string $address)
    {
        try {
            $balance = $this->wallet->balance(new Address(
                $address,
                '',
                $this->wallet->tron->address2HexString($address)
            ));
        } catch (TronErrorException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        } catch (TransactionException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        }
        return $this->_notify($balance);
    }

    public function transfer(string $fromPrivateKey, string $to, string $amount)
    {
        $fromAddress = $this->privateKeyToAddress($fromPrivateKey);
        $balance = $this->balance($fromAddress->address);
        if ($balance < $amount) {
            return $this->error(ErrorCode::INSUFFICIENT_BALANCE);
        }

        $toAddress = new Address($to, '', $this->wallet->tron->address2HexString($to));
        if (!$toAddress->isValid()) {
            return $this->error(ErrorCode::INVALID_ADDRESS);
        }

        try {
            $tx = $this->wallet->transfer($fromAddress, $toAddress, (float)$amount);
        } catch (TronErrorException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        } catch (TransactionException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        }

        return $this->_notify($tx->txID);
    }

    public function blockNumber()
    {
        try {
            $block = $this->wallet->blockNumber();
        } catch (TronErrorException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        } catch (TransactionException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        }
        return $this->_notify($block->block_header['raw_data']['number']);
    }

    public function blockByNumber(int $blockNumber)
    {
        try {
            $block = $this->wallet->blockByNumber($blockNumber);
        } catch (TronErrorException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        } catch (TransactionException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        }
        return $this->_notify($block->transactions);
    }

    public function transactionReceipt(string $txHash)
    {
        try {
            $detail = $this->wallet->transactionReceipt($txHash);
        } catch (TronErrorException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        } catch (TransactionException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        }
        return $this->_notify($detail);
    }

    public function receiptStatus(string $txHash)
    {
        $data = $this->transactionReceipt($txHash);
        if (!isset($data->contractRet) || $data->contractRet != 'SUCCESS')
            return $this->error(0, $data->contractRet);

        return $this->_notify(true);
    }

    public function privateKeyToAddress(string $privateKey)
    {
        try {
            $address = $this->wallet->privateKeyToAddress($privateKey);
        } catch (TronErrorException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        } catch (TransactionException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        }
        return $this->_notify($address);
    }

    public function _return()
    {
    }
}
