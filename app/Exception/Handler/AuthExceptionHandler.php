<?php

namespace App\Exception\Handler;

use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Qbhy\HyperfAuth\Exception\UnauthorizedException;
use Throwable;

/**
 * 自定义未授权异常处理器
 * @package App\Exception\Handler
 * @class AuthExceptionHandler
 * @author lovexjho 2024-08-03
 */
class AuthExceptionHandler extends ExceptionHandler
{

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $res = [
            'code' => 401,
            'data' => [],
            'message' => '登录超时，请重新登录'
        ];
        $this->stopPropagation();
        return $response->withStatus(401)->withHeader('server', 'hyperf')->withBody(new SwooleStream(json_encode($res, JSON_UNESCAPED_UNICODE)));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof UnauthorizedException;
    }
}