<?php

declare(strict_types=1);

namespace App\Controller;

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
        return $service->newAddress('TRX', Protocol::TRX);

        // balance
        $address = 'TBkjvdApxVoLrL5t5zgJsWZuCgjJXzjTuQ';
        $address = 'TPQz2D5PddZ1LTiYTRJ9hiy6k8s2qWpmPY';
        return $service->balance($address, 'TRX', Protocol::TRX);

        // transfer
        $from = 'TBkjvdApxVoLrL5t5zgJsWZuCgjJXzjTuQ';
        $to = 'TPQz2D5PddZ1LTiYTRJ9hiy6k8s2qWpmPY';
        $number = '1';
        return $service->transfer($from, $to, $number, 'TRX', Protocol::TRX);

        // blockByNumber
        return $service->blockByNumber(13030300, 'FUSDT', Protocol::TRX);

        // blockNumber
        return $service->blockNumber('TRX', Protocol::TRX);

        $txHash = '703a6354c0077c8bd9a4de255a73c393d6bee58e0bc55a2f09be64913edec0f6'; //trx
        $txHash = '3f038e528c6da876b51ac4fd4f713f07efaa36add29c0ec05b093a9d98baf952'; //trx20
        // transactionReceipt
        return $service->transactionReceipt($txHash, 'FUSDT', Protocol::TRX);

        // receiptStatus
        return $service->receiptStatus($txHash, 'FUSDT', Protocol::TRX);

        $container = ApplicationContext::getContainer();
        $exec = $container->get(TaskExecutor::class);
        $result = $exec->execute(new Task([\App\Task\ETHFundsCollection::class, 'execute']));
        echo $result;
        return $this->success($result);
    }
}
