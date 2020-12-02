<?php

namespace Driver\Coin\ETH;

use App\Constants\ErrorCode;
use Driver\Coin\AbstractService;
use Ethereum\Wallet;
use Ethereum\Eth;
use Ethereum\ERC20;
use Ethereum\InfuraApi;
use Ethereum\NodeApi;
use Ethereum\Utils;

class Service extends AbstractService
{
    protected $passphrase = ''; //密码
    protected $eth;

    public function __construct($config)
    {
        if (!$config)
            return $this->error(ErrorCode::DATA_NOT_EXIST);
        $this->config = $config;

        if ($this->config['coin_name'] == 'ETH') {
            $this->eth = new Eth(
                new NodeApi(
                    $this->config['rpc_url'],
                    $this->config['rpc_user'],
                    $this->config['rpc_password']
                )
            );
        } else { //ERC20
            $this->eth = new ERC20(
                $this->config['contract_address'],
                new NodeApi(
                    $this->config['rpc_url'],
                    $this->config['rpc_user'],
                    $this->config['rpc_password']
                )
            );
        }
    }

    public function newAddress()
    {
        //array {"mnemonic":"","key":"","address":""}
        $data = Wallet::newAccountByMnemonic($this->passphrase);
        return $this->_notify($data);
    }

    public function balance(string $address)
    {
        if ($this->config['coin_name'] == 'ETH') {
            $number = $this->eth->ethBalance($address, $this->config['decimals']);
        } else { //ERC20
            $number = $this->eth->balance($address, $this->config['decimals']);
        }
        return $this->_notify($number);
    }

    public function transfer(string $from, string $to, string $number)
    {
        $data = $this->eth->transfer(
            $from,
            $to,
            $number,
            $this->config['tx_speed'],
            $this->config['decimals']
        );
        if (!$data)
            return $this->error(ErrorCode::ERR_SERVER);
        return $this->_notify($data);
    }

    public function blockNumber()
    {
        $number = $this->eth->blockNumber();
        if (!$number)
            return $this->error(ErrorCode::ERR_SERVER);
        return $this->_notify($number);
    }

    public function transactionReceipt(string $txHash)
    {
        $data = $this->eth->getTransactionReceipt($txHash);
        if (!$data)
            return $this->error(ErrorCode::ERR_SERVER);
        return $this->_notify($data);
    }

    public function receiptStatus(string $txHash)
    {
        $data = $this->transactionReceipt($txHash);
        $status = hexdec($data['status']);
        $blockNumber = hexdec($data['blockNumber']);
        $blockNumberLatest = $this->blockNumber($txHash);
        $confirms = $blockNumberLatest - $blockNumber + 1;

        if ($status == 0)
            return $this->error(ErrorCode::WAIT_RECEIPT);

        if ($this->config['confirm'] > $confirms)
            return $this->error(ErrorCode::CONFIRM_NOT_ENOUGHT);

        return $this->_notify(true);
    }

    public function _return()
    {
    }
}
