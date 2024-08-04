<?php

namespace App\Exception\Handler;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Exception\MethodNotAllowedHttpException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * 自定义方法不允许异常处理器
 * @package App\Exception\Handler
 * @class MethodNotAllowedHttpExceptionHandler
 * @author lovexjho 2024-08-03
 */
class MethodNotAllowedHttpExceptionHandler extends ExceptionHandler
{

    public function __construct(protected StdoutLoggerInterface $logger,protected RequestInterface $request)
    {
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $res = [
            'code' => 400,
            'data' => [],
            'message' => $throwable->getMessage()
        ];
        $this->logger->warning(sprintf('[%s] %s', $this->request->fullUrl(), $throwable->getMessage()));
        $this->stopPropagation();
        return $response->withHeader('server', 'hyperf')->withBody(new SwooleStream(json_encode($res, JSON_UNESCAPED_UNICODE)));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof MethodNotAllowedHttpException;
    }
}