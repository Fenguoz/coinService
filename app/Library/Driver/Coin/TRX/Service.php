<?php

namespace Driver\Coin\TRX;

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
        if (!$config)
            return $this->error(ErrorCode::DATA_NOT_EXIST);
        $this->config = $config;

        $api = new Api(new Client([
            'base_uri' => $this->config['rpc_url'],
        ]));
        if ($this->config['coin_name'] == 'TRX') {
            $this->wallet = new TRX($api, $this->config);
        } else {
            $this->wallet = new TRC20($api, $this->config);
        }
    }

    public function newAddress()
    {
        $address = $this->wallet->generateAddress();
        return $this->_notify([
            'mnemonic' => '',
            'key' => $address->privateKey,
            'address' => $address->address,
        ]);
    }

    public function balance(string $address)
    {
        $balance = $this->wallet->balance(new Address($address, '', $this->wallet->tron->address2HexString($address)));
        return $this->_notify($balance);
    }

    public function transfer(string $from, string $to, string $amount, string $fromPrivateKey)
    {
        try {
            $fromAddress = new Address($from, $fromPrivateKey, $this->wallet->tron->address2HexString($from));
            $toAddress = new Address($to, '', $this->wallet->tron->address2HexString($to));
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
        $block = $this->wallet->blockNumber();
        return $this->_notify($block->block_header['raw_data']['number']);
    }

    public function blockByNumber(int $blockNumber)
    {
        $block = $this->wallet->blockByNumber($blockNumber);
        return $this->_notify($block->transactions);
    }

    public function transactionReceipt(string $txHash)
    {
        $detail = $this->wallet->transactionReceipt($txHash);
        return $this->_notify($detail);
    }

    public function receiptStatus(string $txHash)
    {
        $data = $this->transactionReceipt($txHash);
        if (!isset($data->contractRet) || $data->contractRet != 'SUCCESS')
            return $this->error(0, $data->contractRet);

        return $this->_notify(true);
    }

    public function _return()
    {
    }
}
