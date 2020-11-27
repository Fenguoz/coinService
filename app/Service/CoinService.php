<?php

namespace App\Service;

use App\Constants\Coin;
use App\Constants\ErrorCode;
use App\Constants\Protocol;
use App\Exception\BusinessException;
use App\Model\CoinAddress;
use App\Model\CoinChainConfig;
use App\Model\CoinTransfer;
use App\Rpc\CoinServiceInterface;
use Driver\Coin\CoinException;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * @RpcService(name="CoinService", protocol="jsonrpc-http", server="jsonrpc-http", publishTo="consul")
 */
class CoinService extends BaseService implements CoinServiceInterface
{
    /**
     * Generate address-related information according to the agreement
     *
     * @param int $coin     Coin code
     * @param int $protocol Chian protocol
     * @return array        [address,mnemonic]
     */
    public function newAddress(int $coin, int $protocol)
    {
        if (!isset(Protocol::$__names[$protocol])) return $this->error(ErrorCode::DATA_NOT_EXIST);
        try {
            $data = $this->coin->setAdapter(Protocol::$__names[$protocol], CoinChainConfig::getConfigByCode(Coin::$__names[$coin], $protocol))->newAddress();
        } catch (CoinException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        }
        $result = CoinAddress::insert(array_merge($data, ['protocol' => $protocol]));
        if (!$result)
            throw new BusinessException(ErrorCode::ADD_ERROR);
        return $this->success([
            'address' => $data['address'],
            'mnemonic' => $data['mnemonic']
        ]);
    }

    /**
     * Get balance based on address
     *
     * @param string $address   Address to check balance
     * @param int   $coin       Coin code
     * @param int   $protocol   Chian protocol
     * @return string
     */
    public function balance(string $address, int $coin, int $protocol)
    {
        if (!isset(Protocol::$__names[$protocol])) return $this->error(ErrorCode::DATA_NOT_EXIST);
        try {
            $number = $this->coin->setAdapter(Protocol::$__names[$protocol], CoinChainConfig::getConfigByCode(Coin::$__names[$coin], $protocol))->balance($address);
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
     * @param string $number    Amount of transaction sent
     * @param int   $coin       Coin code
     * @param int   $protocol   Chian protocol
     * @return array
     */
    public function transfer(string $from, string $to, string $number, int $coin, int $protocol)
    {
        if (!isset(Protocol::$__names[$protocol])) return $this->error(ErrorCode::DATA_NOT_EXIST);
        $key = CoinAddress::where('address', $from)->value('key');
        if (!$key) return $this->error(ErrorCode::DATA_NOT_EXIST);

        try {
            $txHash = $this->coin->setAdapter(Protocol::$__names[$protocol], CoinChainConfig::getConfigByCode(Coin::$__names[$coin], $protocol))->transfer($key, $to, $number);
        } catch (CoinException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        }

        $result = CoinTransfer::create([
            'tx_hash' => $txHash,
            'from' => $from,
            'to' => $to,
            'protocol' => $protocol,
            'coin' => Coin::$__names[$coin],
            'number' => $number,
        ]);
        if (!$result)
            throw new BusinessException(ErrorCode::ADD_ERROR);

        return $this->success($txHash);
    }
}
