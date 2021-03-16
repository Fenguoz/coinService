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
use Ethereum\Utils;
use Exception;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class ETHFundsCollection
{
    /**
     * @Inject()
     * @var StdoutLoggerInterface
     */
    private $logger;

    public function execute()
    {
        $this->logger->info(date('Y-m-d H:i:s', time()) . " ETHFundsCollection: start");
        if (config('app_env') == 'dev') {
            $this->logger->info(date('Y-m-d H:i:s', time()) . " ETHFundsCollection: Done");
            return true;
        }

        $eth = Setting::getListByModule(ConstantsSetting::ETH);

        $fundsCollectionGasPrice = $eth['funds_collection_gas_price'] ?? 50; //归集燃料价格（该值小于归集交易速度值时，执行归集）
        $fundsCollectionTxSpeed = $eth['funds_collection_tx_speed'] ?? 'standard'; //归集交易速度
        $gasPrice = self::gasPriceOracle($fundsCollectionTxSpeed);
        if (!$gasPrice || $fundsCollectionGasPrice < $gasPrice) {
            $this->logger->info(date('Y-m-d H:i:s', time()) . " ETHFundsCollection: gas price high($gasPrice)");
            return true;
        }

        bcscale(10);
        $ethFee = $eth['eth_fee'];
        $ethFeeSendType = $eth['eth_fee_send_type']; //以太坊手续费发放类型(1全额发放 2补量发放)
        $config = CoinChainConfig::where('protocol', Protocol::ETH)
            ->orderBy('contract_address', 'desc')
            ->get();
        if($config->isEmpty()){
            $this->logger->info(date('Y-m-d H:i:s', time()) . " ETHFundsCollection: Not exist protocol(".Protocol::$__names[Protocol::ETH].")");
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
        $logs = CoinFundsCollectionLog::where('status', 0)->where('protocol', Protocol::ETH)->get();
        foreach ($logs as $log) {
            $address2status[$log->address] = 0; //默认0，防止服务失败
            $receiptStatusResult = (new CoinService)->receiptStatus($log->tx_hash, $log->coin_name, Protocol::ETH);
            if (!$receiptStatusResult || $receiptStatusResult['code'] != 200) {
                continue;
            }

            $ret = (new CoinService)->balance($log->address, $log->coin_name, Protocol::ETH);
            if (!$ret || $ret['code'] != 200) {
                continue;
            }

            $balance = $ret['data'];
            if ($log->type == 2 && $receiptStatusResult['data'] == 1 && $balance < $ethFee) {
                CoinAddressBlacklist::insert([
                    'protocol' => Protocol::ETH,
                    'address' => $log->address
                ]);

                continue;
            }
            $result = CoinFundsCollectionLog::where('tx_hash', $log->tx_hash)->update([
                'status' => $receiptStatusResult['data']
            ]);
            if (!$result) {
                continue;
            }

            $address2status[$log->address] = $receiptStatusResult['data'];
        }

        echo "address2status:" . json_encode($address2status) . "\n";

        /* 归集 */
        $blacklist = CoinAddressBlacklist::where('protocol', Protocol::ETH)
            ->pluck('address')
            ->all();
        echo "blacklist:" . json_encode($blacklist) . "\n";

        $data = CoinAddress::where('protocol', Protocol::ETH)
            ->whereNotIn('address', $blacklist)
            ->pluck('address');
        foreach ($data as $address) {
            if (isset($address2status[$address]) && $address2status[$address] == 0)
                continue;

            foreach ($coins as $coin) {
                $ret = (new CoinService)->balance($address, $coin, Protocol::ETH);
                if (!$ret || $ret['code'] != 200) continue;

                $balance = $ret['data'];
                if ($balance == 0)
                    continue;

                //归集数量
                var_dump($balance, $ethFee);
                $transferAmount = ($coin == 'ETH') ? bcsub((string)$balance, (string)$ethFee) : $balance;
                var_dump($transferAmount);

                if ($transferAmount <= 0 || $transferAmount < $coin2minAmount[$coin])
                    continue;

                if ($coin != 'ETH') { // 判断ERC20的手续费
                    $ret = (new CoinService)->balance($address, 'ETH', Protocol::ETH);
                    if (!$ret || $ret['code'] != 200) continue;

                    $ethBalance = $ret['data'];

                    if ($ethFee > $ethBalance) { // Need eth as transaction fee
                        $ethTransferAmount = ($ethFeeSendType == 1) ? $ethFee : bcsub((string)$ethFee, (string)$ethBalance);
                        $transferResult = (new CoinService)->transfer(
                            $coin2sendAddress[$coin],
                            $address,
                            $ethTransferAmount,
                            'ETH',
                            Protocol::ETH
                        );
                        var_dump($transferResult);
                        if (!$transferResult || $transferResult['code'] != 200) {
                            $this->logger->info(date('Y-m-d H:i:s', time()) . "Error: 转账手续费 失败!");
                        }

                        $result = CoinFundsCollectionLog::create([
                            'tx_hash' => $transferResult['data'],
                            'coin_name' => 'ETH',
                            'amount' => $ethTransferAmount,
                            'address' => $address,
                            'type' => 2,
                            'protocol' => Protocol::ETH
                        ]);
                        if (!$result) {
                            $this->logger->info(date('Y-m-d H:i:s', time()) . "Error: CoinFundsCollectionLog 添加失败!");
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
                    Protocol::ETH
                );
                if (!$transferResult || $transferResult['code'] != 200) {
                    $this->logger->info(date('Y-m-d H:i:s', time()) . "Error: 转账手续费 失败!");
                }

                $result = CoinFundsCollectionLog::create([
                    'tx_hash' => $transferResult['data'],
                    'coin_name' => $coin,
                    'amount' => $transferAmount,
                    'address' => $address,
                    'type' => 1,
                    'protocol' => Protocol::ETH
                ]);
                if (!$result) {
                    $this->logger->info(date('Y-m-d H:i:s', time()) . "Error: CoinFundsCollectionLog 添加失败!");
                }
            }
        }

        $this->logger->info(date('Y-m-d H:i:s', time()) . " FundsCollection: Done!");
        return true;
    }

    public static function gasPriceOracle($type = 'standard')
    {
        $url = 'https://www.etherchain.org/api/gasPriceOracle';
        $res = Utils::httpRequest('GET', $url);
        if ($type && isset($res[$type])) {
            $price = $res[$type];
            return $price;
        } else {
            return $res;
        }
    }
}
