<?php

namespace App\Service;

use App\Constants\Coin;
use App\Constants\ErrorCode;
use App\Constants\Protocol;
use App\Exception\BusinessException;
use App\Model\CoinChainConfig;
use App\Rpc\CoinServiceInterface;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * @RpcService(name="CoinService", protocol="jsonrpc-http", server="jsonrpc-http", publishTo="consul")
 */
class CoinService extends BaseService implements CoinServiceInterface
{
    public function address(int $coin, int $protocol)
    {
        if (!isset(Protocol::$__names[$protocol])) return $this->error(ErrorCode::DATA_NOT_EXIST);
        try {
            $this->coin->setAdapter(Protocol::$__names[$protocol], CoinChainConfig::getConfigByCode(Coin::$__names[$coin], $protocol))->address();
        } catch (BusinessException $e) {
            return $this->error($e->getCode());
        }
        return $this->success();
    }

    public function balance()
    {
        return $this->success();
    }

    public function transfer()
    {
        return $this->success();
    }
}
