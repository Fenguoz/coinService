<?php

namespace App\Service;

use App\Constants\ErrorCode;
use Driver\Coin\CoinFactory;
use Hyperf\Amqp\Producer;
use Hyperf\Utils\ApplicationContext;

abstract class BaseService
{
    /**
     * @var ContainerInterfase
     */
    protected $container;

    public function __construct()
    {
        $this->container = ApplicationContext::getContainer();
        $this->producer = $this->container->get(Producer::class);
        $this->coin = $this->container->get(CoinFactory::class);
    }

    /**
     * success
     */
    public function success($data = [], string $msg = null)
    {
        return [
            'code' => ErrorCode::SUCCESS,
            'message' => $msg ?? ErrorCode::getMessage(ErrorCode::SUCCESS),
            'data' => $data
        ];
    }

    /**
     * error
     */
    public function error(int $code = ErrorCode::ERR_SERVER, string $msg = null)
    {
        return [
            'code' => $code,
            'message' => $msg ?? ErrorCode::getMessage($code),
        ];
    }
}