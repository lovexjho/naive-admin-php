<?php

namespace App\Exception\Handler;

use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Validation\ValidationException;
use Swow\Psr7\Message\ResponsePlusInterface;
use Throwable;

/**
 * 自定义验证器异常处理器
 * @package App\Exception\Handler
 * @class ValidationExceptionHandler
 * @author lovexjho 2024-08-03
 */
class ValidationExceptionHandler extends \Hyperf\Validation\ValidationExceptionHandler
{
    public function handle(Throwable $throwable, ResponsePlusInterface $response)
    {
        $this->stopPropagation();
        /** @var ValidationException $throwable */
        $error = $throwable->validator->errors()->first();
        $body = [
            'data' => [],
            'code' => 422,
            'message' => $error
        ];

        return $response->setStatus($throwable->status)->setBody(new SwooleStream(json_encode($body)));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidationException;
    }
}