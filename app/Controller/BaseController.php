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
use Hyperf\Utils\ApplicationContext;
use Hyperf\Task\TaskExecutor;
use Hyperf\Task\Task;

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
        // $address = '0x63f3f4618d1add951344b269d22c9d736451302a';
        // return $service->balance($address, Coin::ETH, Protocol::ETH);
        // return $service->balance($address, 'USDT', Protocol::ETH);

        // transfer
        // $from = '0x5eA1cAB2e6b49796EE0454A6131998B67aF596D5';
        // $to = '0x63f3f4618d1add951344b269d22c9d736451302a';
        // $number = '0.1';
        // return $service->transfer($from,$to,$number, Coin::USDT, Protocol::ETH);
        // return $service->transfer($from,$to,$number, Coin::ETH, Protocol::ETH);

        // return $service->blockNumber('USDT', Protocol::ETH);

        // $txHash = '0xfe0ed5db1061e691eb85e836a88570e5810a492d807180296eef99a015706938';
        // return $service->transactionReceipt($txHash, 'USDT', Protocol::ETH);
        // return $service->receiptStatus($txHash, 'USDT', Protocol::ETH);


        // $blockNumer = 11384670;
        // $blockNumer = 11339155;
        // $data = $service->blockByNumber($blockNumer, 'USDT', Protocol::ETH);

        // var_dump($data);
        // return $data;

        $container = ApplicationContext::getContainer();
        $exec = $container->get(TaskExecutor::class);
        $result = $exec->execute(new Task([\App\Task\ETHFundsCollection::class, 'execute']));
        echo $result;
        // return $this->success($result);
    }
}
