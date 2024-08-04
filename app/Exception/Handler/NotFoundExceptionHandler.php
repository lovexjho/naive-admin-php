<?php

namespace App\Exception\Handler;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Exception\NotFoundHttpException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Swow\Psr7\Message\ResponsePlusInterface;
use Throwable;

/**
 * 自定义404异常处理器
 * @package App\Exception\Handler
 * @class NotFoundExceptionHandler
 * @author lovexjho 2024-08-03
 */
class NotFoundExceptionHandler extends ExceptionHandler
{
    public function __construct(protected RequestInterface $request, protected StdoutLoggerInterface $logger)
    {
    }

    public function handle(Throwable $throwable, ResponsePlusInterface $response)
    {
        $res = [
            'code' => 404,
            'data' => [],
            'message' => $throwable->getMessage()
        ];
        $this->logger->warning(sprintf('[%s] 404 not found', $this->request->fullUrl()));
        $this->stopPropagation();
        return $response->withHeader('server', 'hyperf')->withStatus(404)->withBody(new SwooleStream(json_encode($res, JSON_UNESCAPED_UNICODE)));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof NotFoundHttpException;
    }
}