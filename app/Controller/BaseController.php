<?php

declare(strict_types=1);

namespace App\Controller;

use App\Constants\ErrorCode;
use App\Constants\Protocol;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerInterface;

class BaseController extends AbstractController
{

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ClientFactory
     */
    protected $client;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->client = $container->get(ClientFactory::class)->create();
        $this->request = $container->get(RequestInterface::class);
        $this->response = $container->get(ResponseInterface::class);
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
        $config = [
            'coin_name' => 'USDT',
            'rpc_url' => 'https://api.shasta.trongrid.io',
            'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
            'decimals' => 6,
        ];
        $config = [
            'coin_name' => 'TRX',
            'rpc_url' => 'https://api.shasta.trongrid.io',
        ];
        $service = new \App\Service\CoinService();

        // newAddress
        return $service->newAddress($config, Protocol::TRON);

        // balance
        $address = 'TBkjvdApxVoLrL5t5zgJsWZuCgjJXzjTuQ';// 7754.80656
        return $service->balance($address, $config, Protocol::TRON);

        // transfer
        $from = 'TPQz2D5PddZ1LTiYTRJ9hiy6k8s2qWpmPY';
        $to = 'TBkjvdApxVoLrL5t5zgJsWZuCgjJXzjTuQ';
        $number = '1';
        return $service->transfer($from, $to, $number, $config, Protocol::TRON);

        // blockByNumber
        return $service->blockByNumber(13030300, $config, Protocol::TRON);

        // blockNumber
        return $service->blockNumber($config, Protocol::TRON);

        $txHash = '703a6354c0077c8bd9a4de255a73c393d6bee58e0bc55a2f09be64913edec0f6'; //trx
        $txHash = '3f038e528c6da876b51ac4fd4f713f07efaa36add29c0ec05b093a9d98baf952'; //trx20
        // transactionReceipt
        return $service->transactionReceipt($txHash, $config, Protocol::TRON);

        // receiptStatus
        return $service->receiptStatus($txHash, $config, Protocol::TRON);

        
    }
}
