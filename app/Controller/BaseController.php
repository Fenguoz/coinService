<?php

declare(strict_types=1);

namespace App\Controller;

use App\Constants\ErrorCode;
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
}
