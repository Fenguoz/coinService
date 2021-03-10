<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\Controller;
use App\Constants\ErrorCode;
use App\Constants\Protocol;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Redis\RedisFactory;
use Psr\Container\ContainerInterface;

class BaseController extends AbstractController
{

    /**
     * @var ContainerInterfase
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->redis = $container->get(RedisFactory::class)->get('default');
        $this->client = $container->get(ClientFactory::class)->create();
    }

    /**
     * success
     * 成功返回请求结果
     * @param array $data
     * @param string|null $msg
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function success($data = [], string $msg = null)
    {
        $msg = $msg ?? ErrorCode::getMessage(ErrorCode::SUCCESS);
        $data = [
            'code' => ErrorCode::SUCCESS,
            'message' => $msg,
            'data' => $data
        ];

        return $this->response->json($data);
    }

    /**
     * error
     * 业务相关错误结果返回
     * @param int $code
     * @param string|null $msg
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function error(int $code = ErrorCode::ERR_SERVER, string $msg = null)
    {
        $msg = $msg ?? ErrorCode::getMessage($code);
        $data = [
            'code' => $code,
            'message' => $msg,
        ];

        return $this->response->json($data);
    }

    public function index()
    {
        $service = new \App\Service\CoinService();
        // newAddress
        // return $service->newAddress('BTC', Protocol::BTC);
        // return $service->newAddress('TRX', Protocol::TRX);
        // return $service->newAddress('FUSDT', Protocol::TRX);

        // balance
        // $address = '0x64AECA120dfCE9Df383EA0582f25248FCA638CA3';
        // return $service->balance($address, Coin::ETH, Protocol::ETH);
        // return $service->balance($address, Coin::USDT, Protocol::ETH);
        // $address = '14GvTBTVb5cTuEiC4zVXvpkKjGgDWkyB55';
        // return $service->balance($address, 'BTC', Protocol::BTC);
        // $address = 'TBkjvdApxVoLrL5t5zgJsWZuCgjJXzjTuQ';
        $address = 'TPQz2D5PddZ1LTiYTRJ9hiy6k8s2qWpmPY';
        return $service->balance($address, 'FUSDT', Protocol::TRX);
        // return $service->balance($address, 'TRX', Protocol::TRX);

        // transfer
        // $from = '0x5eA1cAB2e6b49796EE0454A6131998B67aF596D5';
        // $to = '0x63f3f4618d1add951344b269d22c9d736451302a';
        // $number = '0.1';
        // return $service->transfer($from,$to,$number, Coin::USDT, Protocol::ETH);
        // return $service->transfer($from,$to,$number, Coin::ETH, Protocol::ETH);
        // $from = 'KxKGoMErzWS3MUpWyA2fb4uhS2CBLWEQiueqNkmEoyQU2JAykuh5';
        $from = 'TBkjvdApxVoLrL5t5zgJsWZuCgjJXzjTuQ';
        $to = 'TPQz2D5PddZ1LTiYTRJ9hiy6k8s2qWpmPY';
        $number = '1';
        return $service->transfer($from, $to, $number, 'TRX', Protocol::TRX);
        // return $service->transfer($from, $to, $number, 'FUSDT', Protocol::TRX);

        // return $service->blockNumber('FUSDT', Protocol::TRX);
        // return $service->blockNumber(Coin::USDT, Protocol::ETH);

        // $txHash = '0x9dcc648804b3f05dade94e60808faef45800d70cca6a5e41bfa8c0388cb7518f';
        // return $service->transactionReceipt($txHash, Coin::USDT, Protocol::ETH);
        // return $service->receiptStatus($txHash, Coin::USDT, Protocol::ETH);
        // $txHash = '703a6354c0077c8bd9a4de255a73c393d6bee58e0bc55a2f09be64913edec0f6';//trx
        $txHash = 'a931f589ebe1182ef74d53957971273f51e3755739358066ec883dfb21c49a9b';//trx20
        return $service->transactionReceipt($txHash, 'FUSDT', Protocol::TRX);
        // return $service->receiptStatus($txHash, 'FUSDT', Protocol::TRX);
    }

}
