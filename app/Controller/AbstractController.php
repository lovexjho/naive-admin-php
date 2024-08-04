<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Controller;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerInterface;

abstract class AbstractController
{
    #[Inject]
    protected ContainerInterface $container;

    #[Inject]
    protected RequestInterface $request;

    #[Inject]
    protected ResponseInterface $response;

    public function response(int $code = null, $data = [], string $message = '')
    {
        return $this->response->json([
            'code' => $code,
            'data' => $data,
            'message' => $message,
            'originUrl' => $this->request->getRequestTarget()
        ]);
    }

    public function success($data = [], int $code = 200, string $message = '成功')
    {
        return $this->response($code, $data, $message);
    }

    public function error($data = [], int $code = 600,  string $message = '失败')
    {
        return $this->response($code, $data, $message);
    }
}
