<?php

namespace App\Service;

use App\Constants\ErrorCode;
use App\Constants\Protocol;
use App\Rpc\CoinServiceInterface;
use Driver\Coin\CoinException;
use Hyperf\Di\Annotation\Inject;
use Hyperf\RpcServer\Annotation\RpcService;
use League\Flysystem\Filesystem;

/**
 * @RpcService(name="CoinService", protocol="jsonrpc-http", server="jsonrpc-http", publishTo="consul")
 */
class CoinService extends BaseService implements CoinServiceInterface
{
    /**
     * @Inject
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Generate address-related information according to the agreement
     *
     * @param array     $config     Coin config
     * @param int       $protocol   Chian protocol
     * @return string   address
     */
    public function newAddress(array $config, int $protocol)
    {
        $protocolName = $this->getProtocolName($protocol);
        try {
            $data = $this->coin->setAdapter($protocolName, $config)->newAddress();
        } catch (CoinException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        }

        // 文件储存
        $file = "coin/address/{$protocolName}/{$data['address']}.txt";
        $this->filesystem->write($file, json_encode($data));

        return $this->success($data['address']);
    }

    /**
     * Get balance based on address
     *
     * @param string $address   Address to check balance
     * @param array $config     Coin config
     * @param int   $protocol   Chian protocol
     * @return string
     */
    public function balance(string $address, array $config, int $protocol)
    {
        $protocolName = $this->getProtocolName($protocol);
        try {
            $number = $this->coin->setAdapter($protocolName, $config)->balance($address);
        } catch (CoinException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        }
        return $this->success($number);
    }

    /**
     * Create a new message call transaction
     *
     * @param string $from      The source address of the transaction sent
     * @param string $to        Target address of the transaction
     * @param string $amount    Amount of transaction sent
     * @param array $config     Coin config
     * @param int   $protocol   Chian protocol
     * @return array
     */
    public function transfer(string $from, string $to, string $amount, array $config, int $protocol)
    {
        $protocolName = $this->getProtocolName($protocol);

        // Get Key of Address
        $key = $from;
        $file = "coin/address/{$protocolName}/{$from}.txt";
        if ($this->filesystem->has($file)) {
            $contents = $this->filesystem->read($file);
            $key = json_decode($contents, true)['key'];
        }

        try {
            $txHash = $this->coin->setAdapter($protocolName, $config)->transfer($key, $to, $amount);
        } catch (CoinException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        }

        return $this->success($txHash);
    }

    /**
     * Returns the number of the latest block
     *
     * @param array  $config     Coin config
     * @param int    $protocol   Chian protocol
     * @return string
     */
    public function blockNumber(array $config, int $protocol)
    {
        $protocolName = $this->getProtocolName($protocol);
        try {
            $number = $this->coin->setAdapter($protocolName, $config)->blockNumber();
        } catch (CoinException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        }
        return $this->success($number);
    }

    /**
     * Returns the receipt of the specified transaction, using the hash to specify 
     * the transaction.
     *
     * @param string $txHash    Transaction hash
     * @param array $config     Coin config
     * @param int   $protocol   Chian protocol
     * @return array
     */
    public function transactionReceipt(string $txHash, array $config, int $protocol)
    {
        $protocolName = $this->getProtocolName($protocol);
        try {
            $number = $this->coin->setAdapter($protocolName, $config)->transactionReceipt($txHash);
        } catch (CoinException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        }
        return $this->success($number);
    }

    /**
     * Returns the status of the receipt of the specified transaction, using the hash 
     * to specify the transaction.
     *
     * @param string $txHash    Transaction hash
     * @param array $config     Coin config
     * @param int   $protocol   Chian protocol
     * @return bool
     */
    public function receiptStatus(string $txHash, array $config, int $protocol)
    {
        $protocolName = $this->getProtocolName($protocol);
        try {
            $ret = $this->coin->setAdapter($protocolName, $config)->receiptStatus($txHash);
        } catch (CoinException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        }
        return $this->success($ret);
    }

    /**
     * Returns a block of the specified number.
     *
     * @param int       $blockNumber    Block number
     * @param array     $config     Coin config
     * @param int       $protocol       Chian protocol
     * @return bool
     */
    public function blockByNumber(int $blockNumber, array $config, int $protocol)
    {
        $protocolName = $this->getProtocolName($protocol);
        try {
            $ret = $this->coin->setAdapter($protocolName, $config)->blockByNumber($blockNumber);
        } catch (CoinException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        }
        return $this->success($ret);
    }

    /**
     * Obtain the address based on the private key
     *
     * @param string    $privateKey     Block number
     * @param array     $config         Coin config
     * @param int       $protocol       Chian protocol
     * @return bool
     */
    public function privateKeyToAddress(string $privateKey, array $config, int $protocol)
    {
        $protocolName = $this->getProtocolName($protocol);
        try {
            $ret = $this->coin->setAdapter($protocolName, $config)->privateKeyToAddress($privateKey);
        } catch (CoinException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        }
        return $this->success($ret);
    }

    /**
     * Get protocol name
     *
     * @param int       $protocol       Chian protocol
     * @return string   $protocolName
     */
    private function getProtocolName(int $protocol): string
    {
        $protocolName = isset(Protocol::$__names[$protocol])
            ? Protocol::$__names[$protocol]
            : null;
        if (!$protocolName) {
            return $this->error(ErrorCode::DATA_NOT_EXIST);
        }
        return $protocolName;
    }
}
