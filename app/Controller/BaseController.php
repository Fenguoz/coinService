<?php

declare(strict_types=1);

namespace App\Controller;

use App\Constants\Coin;
use Hyperf\HttpServer\Annotation\Controller;
use App\Constants\ErrorCode;
use App\Constants\Protocol;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Redis\RedisFactory;
use Psr\Container\ContainerInterface;
use OpenApi\Annotations\Info;
use OpenApi\Annotations\Server;

/**
 * @Info(
 *     version="1.0",
 *     title="钱包服务",
 * ),
 * @Server(
 *     url="http://127.0.0.1:9501",
 *     description="本地环境"
 * )
 */
/**
 * @Controller()
 */
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
        // return $service->newAddress(Coin::ETH, Protocol::ETH);

        // balance
        $address = '0x64AECA120dfCE9Df383EA0582f25248FCA638CA3';
        // return $service->balance($address, Coin::ETH, Protocol::ETH);
        return $service->balance($address, Coin::USDT, Protocol::ETH);

        // transfer
        // $from = '0x5eA1cAB2e6b49796EE0454A6131998B67aF596D5';
        // $to = '0x63f3f4618d1add951344b269d22c9d736451302a';
        // $number = '0.1';
        // return $service->transfer($from,$to,$number, Coin::USDT, Protocol::ETH);
        // return $service->transfer($from,$to,$number, Coin::ETH, Protocol::ETH);
    }

}
