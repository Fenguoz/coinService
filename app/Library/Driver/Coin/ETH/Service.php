<?php

namespace Driver\Coin\ETH;

use App\Constants\ErrorCode;
use App\Constants\Protocol;
use App\Model\CoinAddress;
use App\Model\CoinChainConfig;
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

    public function blockByNumber(int $blockNumber, $isRebuild = true)
    {
        $txData = $this->eth->getBlockByNumber($blockNumber);
        if (!$txData)
            return $this->error(ErrorCode::ERR_SERVER);
        return $this->_notify($isRebuild ? $this->_rebuildTxData($txData) : $txData);
    }

    public function _rebuildTxData($txData)
    {
        $mainCoin = '';
        $mainCoinDecimals = 0;
        $contractAddress = [];
        $contractAddress2coinName = [];
        $contractAddress2decimals = [];

        $config = CoinChainConfig::where('protocol', Protocol::ETH)->get();
        foreach ($config as $v) {
            if ($v->contract_address) {
                $contractAddress[] = $v->contract_address;
                $contractAddress2coinName[$v->contract_address] = $v->coin_name;
                $contractAddress2decimals[$v->contract_address] = $v->decimals;
            } else {
                $mainCoin = $v->coin_name;
                $mainCoinDecimals = $v->decimals;
            }
        }

        $transferMethod = '0xa9059cbb';
        $data = [];
        foreach ($txData['transactions'] as $v) {
            $item = [];
            $item['hash'] = $v['hash'];
            $item['block'] = Utils::toDisplayAmount($v['blockNumber'], 0);
            $item['from'] = $v['from'];
            $item['to'] = $v['to'];

            if ($v['input'] == '0x') { //ETH
                if (!CoinAddress::where('address', $v['to'])->where('protocol', Protocol::ETH)->exists()) continue;

                $item['coin'] = $mainCoin;
                $item['amount'] = Utils::toDisplayAmount($v['value'], (int)$mainCoinDecimals);
            } else { //ERC20
                if (!in_array($v['to'], $contractAddress)) continue;

                $method = substr($v['input'], 0, 10);
                if ($transferMethod != $method) continue;

                $item['to'] = '0x' . substr($v['input'], 34, 40);
                if (!CoinAddress::where('address', $item['to'])->where('protocol', Protocol::ETH)->exists()) continue;

                $item['coin'] = $contractAddress2coinName[$v['to']];
                $amount = substr($v['input'], 74, 64);
                $item['amount'] = Utils::toDisplayAmount($amount, (int)$contractAddress2decimals[$v['to']]);
            }
            $data[] = $item;
        }
        return $data;
    }

    public function _return()
    {
    }
}
