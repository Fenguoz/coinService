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

    public function execute()
    {
        $this->logger->info(date('Y-m-d H:i:s', time()) . " TRONFundsCollection: start");
        if (config('app_env') == 'dev') {
            $this->logger->info(date('Y-m-d H:i:s', time()) . " TRONFundsCollection: Done");
            return true;
        }

        $tronSetting = Setting::getListByModule(ConstantsSetting::TRON);

        bcscale(10);
        $txFee = $tronSetting['tron_tx_fee'];
        $txFeeSendType = $tronSetting['tron_tx_fee_send_type']; //手续费发放类型(1全额发放 2补量发放)
        $config = CoinChainConfig::where('protocol', Protocol::TRON)
            ->orderBy('contract_address', 'desc')
            ->get();
        if ($config->isEmpty()) {
            $this->logger->info(date('Y-m-d H:i:s', time()) . " TRONFundsCollection: Not exist protocol(" . Protocol::$__names[Protocol::TRON] . ")");
            return true;
        }

        $coins = [];
        $coin2minAmount = [];
        $coin2sendAddress = [];
        $coin2recvAddress = [];
        foreach ($config as $v) {
            $coins[] = $v->coin_name;
            $coin2minAmount[$v->coin_name] = $v->funds_collection_min_amount;
            $coin2sendAddress[$v->coin_name] = $v->send_address;
            $coin2recvAddress[$v->coin_name] = $v->recv_address;
        }

        echo "coin2minAmount:" . json_encode($coin2minAmount) . "\n";
        echo "coin2sendAddress:" . json_encode($coin2sendAddress) . "\n";
        echo "coin2recvAddress:" . json_encode($coin2recvAddress) . "\n";

        /* 判断上一次归集状态 */
        $address2status = [];
        $logs = CoinFundsCollectionLog::where('status', 0)->where('protocol', Protocol::TRON)->get();
        foreach ($logs as $log) {
            $address2status[$log->address] = 0; //默认0，防止服务失败
            $receiptStatusResult = (new CoinService)->receiptStatus($log->tx_hash, $log->coin_name, Protocol::TRON);
            if (!$receiptStatusResult || $receiptStatusResult['code'] != 200) {
                continue;
            }

            $ret = (new CoinService)->balance($log->address, $log->coin_name, Protocol::TRON);
            if (!$ret || $ret['code'] != 200) {
                continue;
            }

            $balance = $ret['data'];
            if ($log->type == 2 && $receiptStatusResult['data'] == 1 && $balance < $txFee) {
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

            $address2status[$log->address] = $receiptStatus;
        }

        echo "address2status:" . json_encode($address2status) . "\n";

        /* 归集 */
        $blacklist = CoinAddressBlacklist::where('protocol', Protocol::TRON)
            ->pluck('address')
            ->all();
        echo "blacklist:" . json_encode($blacklist) . "\n";

        $data = CoinAddress::where('protocol', Protocol::TRON)
            ->whereNotIn('address', $blacklist)
            ->pluck('address');
        foreach ($data as $address) {
            if (isset($address2status[$address]) && $address2status[$address] == 0) {
                continue;
            }

            foreach ($coins as $coin) {
                $ret = (new CoinService)->balance($address, $coin, Protocol::TRON);
                if (!$ret || $ret['code'] != 200) {
                    continue;
                }

                //归集数量
                $transferAmount = (int)$ret['data'];
                if ($transferAmount <= 0 || $transferAmount < $coin2minAmount[$coin]) {
                    continue;
                }
                echo 'transferAmount:' . $transferAmount . PHP_EOL;

                // 判断TRC20的手续费
                if ($coin != 'TRX') {
                    $ret = (new CoinService)->balance($address, 'TRX', Protocol::TRON);
                    if (!$ret || $ret['code'] != 200) {
                        continue;
                    }

                    $coinBalance = $ret['data'];

                    if ($txFee > $coinBalance) { // Need trx as transaction fee
                        $transferFeeAmount = ($txFeeSendType == 1) ? $txFee : bcsub((string)$txFee, (string)$coinBalance);
                        $transferResult = (new CoinService)->transfer(
                            $coin2sendAddress[$coin],
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
                }

                //归集余额
                $transferResult = (new CoinService)->transfer(
                    $address,
                    $coin2recvAddress[$coin],
                    $transferAmount,
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
                    'amount' => $transferAmount,
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
}
