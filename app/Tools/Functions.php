<?php

declare(strict_types=1);

namespace App\Tools;

use Hyperf\Context\ApplicationContext;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Redis\Redis;
use function Hyperf\Config\config;

/**
 * 获取真实客户端ip地址
 * @return mixed|string
 * @throws \Psr\Container\ContainerExceptionInterface
 * @throws \Psr\Container\NotFoundExceptionInterface
 * @user lovexjho 2024-08-03
 */
function getClientIP()
{
    $request = ApplicationContext::getContainer()->get(RequestInterface::class);
    // 尝试从 X-Real-IP 请求头中获取客户端真实 IP 地址
    $realIp = $request->getHeader('X-Real-IP');

    if (!empty($realIp)) {
        return $realIp[0];
    }

    // 如果 X-Real-IP 请求头不存在，则尝试从 X-Forwarded-For 请求头中获取客户端真实 IP 地址
    $forwardedFor = $request->getHeader('X-Forwarded-For');

    if (!empty($forwardedFor)) {
        // X-Forwarded-For 可能是一个逗号分隔的 IP 地址列表，取第一个作为真实 IP 地址
        $ips = explode(',', $forwardedFor[0]);
        return trim($ips[0]);
    }

    // 如果以上两个请求头都不存在，则返回默认的客户端 IP 地址
    return $request->getServerParams()['remote_addr'] ?? '';
}

/**
 * 随机字符串生成
 * @param $length
 * @return string
 * @user lovexjho 2024-08-03
 */
function generateRandomString($length)
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}


/**
 * 获取运行环境
 * @return mixed
 * @user lovexjho 2024-08-03
 */
function environment()
{
    return config('app_env');
}

/**
 * 是否是生产环境
 * @return bool
 * @user lovexjho 2024-08-03
 */
function isProd(): bool
{
    return environment() == 'prod';
}

/**
 * 是否是开发环境
 * @return bool
 * @user lovexjho 2024-08-03
 */
function isDev()
{
    return environment() == 'dev';
}