<?php

namespace App\Task;

use App\Constants\Protocol;
use App\Constants\Setting as ConstantsSetting;
use App\Model\CoinAddress;
use App\Model\CoinAddressBlacklist;
use App\Model\CoinChainConfig;
use App\Model\CoinFundsCollectionLog;
use App\Model\Setting;
use App\Service\CoinService;
use Exception;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class TRONFundsCollection
{
    /**
     * @Inject()
     * @var StdoutLoggerInterface
     */
    private $logger;

    /**
     * @Inject()
     * @var CoinService
     */
    private $coinService;

    protected $coins = [];
    protected $coin2minAmount = [];
    protected $coin2sendAddress = [];
    protected $coin2recvAddress = [];

    protected $txFee = [];
    protected $txFeeSendType = [];

    public function init()
    {
        $config = CoinChainConfig::where('protocol', Protocol::TRON)
            ->orderBy('contract_address', 'desc')
            ->get();
        if ($config->isEmpty()) {
            $this->logger->info(date('Y-m-d H:i:s', time()) . " TRONFundsCollection: Not exist protocol(" . Protocol::$__names[Protocol::TRON] . ")");
            return false;
        }

        foreach ($config as $v) {
            $this->coins[] = $v->coin_name;
            $this->coin2minAmount[$v->coin_name] = $v->funds_collection_min_amount;
            $this->coin2sendAddress[$v->coin_name] = $v->send_address;
            $this->coin2recvAddress[$v->coin_name] = $v->recv_address;
        }

        echo "coin2minAmount:" . json_encode($this->coin2minAmount) . "\n";
        echo "coin2sendAddress:" . json_encode($this->coin2sendAddress) . "\n";
        echo "coin2recvAddress:" . json_encode($this->coin2recvAddress) . "\n";

        $tronSetting = Setting::getListByModule(ConstantsSetting::TRON);

        $this->txFee = $tronSetting['tron_tx_fee'];
        $this->txFeeSendType = $tronSetting['tron_tx_fee_send_type']; //手续费发放类型(1全额发放 2补量发放)

        return true;
    }

    public function execute()
    {
        $this->logger->info(date('Y-m-d H:i:s', time()) . " TRONFundsCollection: start");
        if (config('app_env') == 'dev') {
            $this->logger->info(date('Y-m-d H:i:s', time()) . " TRONFundsCollection: Done");
            return true;
        }

        bcscale(10);

        if (!$this->init()) {
            return false;
        }

        /* 判断上一次归集状态 */
        $this->lastTime();

        /* 归集 */
        $blacklist = CoinAddressBlacklist::where('protocol', Protocol::TRON)
            ->pluck('address')
            ->all();
        echo "blacklist:" . json_encode($blacklist) . "\n";

        $data = CoinAddress::where('protocol', Protocol::TRON)
            ->whereNotIn('address', $blacklist)
            ->pluck('address');
        foreach ($data as $address) {
            if (isset($this->address2status[$address]) && $this->address2status[$address] == 0) {
                continue;
            }

            foreach ($this->coins as $coin) {
                $transferAmount =  $this->getBalance($address, $coin);

                //归集数量
                if ($transferAmount <= 0 || $transferAmount < $this->coin2minAmount[$coin]) {
                    continue;
                }
                echo 'address:' . $address . PHP_EOL;
                echo 'coin:' . $coin . PHP_EOL;
                echo 'transferAmount:' . $transferAmount . PHP_EOL;

                // 判断TRC20的手续费
                if ($coin != 'TRX') {
                    $coinBalance = $this->getBalance($address, 'TRX');
                    if ($this->txFee > $coinBalance) { // Need trx as transaction fee
                        $transferFeeAmount = ($this->txFeeSendType == 1) ? $this->txFee : bcsub((string)$this->txFee, (string)$coinBalance, 6);

                        // 判断发送地址余额是否充足
                        $ret = $this->coinService->privateKeyToAddress($this->coin2sendAddress[$coin], 'TRX', Protocol::TRON);
                        if (!$ret || $ret['code'] != 200) {
                            continue;
                        }
                        $sendAddress = $ret['data']->address;
                        $sendAddressBalance = $this->getBalance($sendAddress, 'TRX');
                        if ($transferFeeAmount > $sendAddressBalance) {
                            $this->logger->info(date('Y-m-d H:i:s', time()) . "TRONFundsCollection Error: 主地址转账手续费不足!");
                            break;
                        }

                        $transferResult = $this->coinService->transfer(
                            $this->coin2sendAddress[$coin],
                            $address,
                            $transferFeeAmount,
                            'TRX',
                            Protocol::TRON
                        );
                        if (!$transferResult || $transferResult['code'] != 200) {
                            $this->logger->info(date('Y-m-d H:i:s', time()) . "TRONFundsCollection Error: 转账手续费 失败!");
                            break; //the user done, next one
                        }

                        $result = CoinFundsCollectionLog::create([
                            'tx_hash' => $transferResult['data'],
                            'coin_name' => 'TRX',
                            'amount' => $transferFeeAmount,
                            'address' => $address,
                            'type' => 2,
                            'protocol' => Protocol::TRON
                        ]);
                        if (!$result) {
                            $this->logger->info(date('Y-m-d H:i:s', time()) . "TRONFundsCollection Error: CoinFundsCollectionLog 添加失败!");
                        }
                        break; //the user done, next one
                    }

                    $realTransferAmount = $transferAmount;
                }else{
                    $realTransferAmount = bcsub((string)$transferAmount, '0.5', 6);
                }

                //归集余额
                $transferResult = $this->coinService->transfer(
                    $address,
                    $this->coin2recvAddress[$coin],
                    $realTransferAmount,
                    $coin,
                    Protocol::TRON
                );
                if (!$transferResult || $transferResult['code'] != 200) {
                    $this->logger->info(date('Y-m-d H:i:s', time()) . "Error: " . $transferResult['message']);
                    continue;
                }

                $result = CoinFundsCollectionLog::create([
                    'tx_hash' => $transferResult['data'],
                    'coin_name' => $coin,
                    'amount' => $realTransferAmount,
                    'address' => $address,
                    'type' => 1,
                    'protocol' => Protocol::TRON,
                ]);
                if (!$result) {
                    $this->logger->info(date('Y-m-d H:i:s', time()) . "TRONFundsCollection Error: CoinFundsCollectionLog 添加失败!");
                }
                break; //the user done, next one
            }
        }

        $this->logger->info(date('Y-m-d H:i:s', time()) . " TRONFundsCollection: Done!");
        return true;
    }


    protected $address2status = [];
    /* 判断上一次归集状态 */
    public function lastTime()
    {
        $this->address2status = [];
        $logs = CoinFundsCollectionLog::where('status', 0)->where('protocol', Protocol::TRON)->get();
        foreach ($logs as $log) {
            $address2status[$log->address] = 0; //默认0，防止服务失败
            $receiptStatusResult = $this->coinService->receiptStatus($log->tx_hash, $log->coin_name, Protocol::TRON);
            if (!$receiptStatusResult || $receiptStatusResult['code'] != 200) {
                continue;
            }

            $balance = $this->getBalance($log->address, $log->coin_name);
            if ($log->type == 2 && $receiptStatusResult['data'] == 1 && $balance < $this->txFee) {
                CoinAddressBlacklist::insert([
                    'protocol' => Protocol::TRON,
                    'address' => $log->address
                ]);

                continue;
            }

            $receiptStatus = $receiptStatusResult['data'] ? 1 : -1;
            $result = CoinFundsCollectionLog::where('tx_hash', $log->tx_hash)->update([
                'status' => $receiptStatus
            ]);
            if (!$result) {
                continue;
            }

            $this->address2status[$log->address] = $receiptStatus;
        }

        echo "address2status:" . json_encode($this->address2status) . "\n";
        return true;
    }

    public function getBalance($address, $coin)
    {
        $ret = $this->coinService->balance($address, $coin, Protocol::TRON);
        return $ret['data'];
    }

}
