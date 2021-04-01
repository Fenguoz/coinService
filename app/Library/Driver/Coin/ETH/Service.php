<?php

namespace Driver\Coin\ETH;

use App\Constants\ErrorCode;
use Driver\Coin\AbstractService;
use Ethereum\Wallet;
use Ethereum\Eth;
use Ethereum\ERC20;
use Ethereum\NodeApi;

class Service extends AbstractService
{
    protected $passphrase = ''; //密码
    protected $eth;

    public function __construct($config)
    {
        if (!$config) {
            return $this->error(ErrorCode::DATA_NOT_EXIST);
        }
        if (!isset($config['rpc_url'])) {
            return $this->error(ErrorCode::RPC_URL_REQUIRED);
        }

        $this->config = $config;
        if (!isset($this->config['tx_speed'])) {
            $this->config['tx_speed'] = 'standard';
        }
        if (!in_array($this->config['tx_speed'], ['safeLow', 'standard', 'fast', 'fastest'])) {
            return $this->error(ErrorCode::TX_SPEED_ERROR, 'tx_speed must be selected in this array[safeLow,standard,fast,fastest]');
        }

        $rpcUrl = $this->config['rpc_url'];
        $rpcUser = isset($this->config['rpc_user']) ? $this->config['rpc_user'] : null;
        $rpcPassword = isset($this->config['rpc_password']) ? $this->config['rpc_password'] : null;
        $api = new NodeApi($rpcUrl, $rpcUser, $rpcPassword);

        if (isset($this->config['coin_name']) && $this->config['coin_name'] != 'ETH') { //ERC20
            if (!isset($this->config['decimals'])) {
                return $this->error(ErrorCode::DECIMALS_REQUIRED);
            }
            if (!isset($this->config['contract_address'])) {
                return $this->error(ErrorCode::CONTRACT_ADDRESS_REQUIRED);
            }

            $this->eth = new ERC20($this->config['contract_address'], $api);
        } else {
            $this->config['decimals'] = 18;
            $this->eth = new Eth($api);
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
        if (isset($this->config['coin_name']) && $this->config['coin_name'] != 'ETH') { //ERC20
            $number = $this->eth->balance($address, $this->config['decimals']);
        } else {
            $number = $this->eth->ethBalance($address, $this->config['decimals']);
        }
        return $this->_notify($number);
    }

    public function transfer(string $fromPrivateKey, string $to, string $amount)
    {
        $data = $this->eth->transfer(
            $fromPrivateKey,
            $to,
            $amount,
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
        // if ($status == 0)
        //     return $this->error(ErrorCode::WAIT_RECEIPT);

        return $this->_notify($status);
    }

    public function blockByNumber(int $blockNumber)
    {
        $txData = $this->eth->getBlockByNumber($blockNumber);
        if (!$txData) {
            return $this->error(ErrorCode::ERR_SERVER);
        }
        return $this->_notify($txData);
    }

    public function _return()
    {
    }
}
