<?php

namespace App\Tools;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Hyperf\Context\ApplicationContext;
use Hyperf\Guzzle\HandlerStackFactory;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Http\Message\ResponseInterface;
use function Hyperf\Support\make;

/**
 * 请求类
 * @package App\Tools
 * @class Curl
 * @author lovexjho 2024-08-03
 */
class Curl
{
    /**
     * 日志保存位置
     */
    protected const LOG_SAVE_PATH = BASE_PATH . '/' . 'logs';
    protected ?Client $client;

    public function __construct(protected HandlerStackFactory $handlerStackFactory, protected LoggerFactory $loggerFactory)
    {
    }


    /**
     * 代理Client对象调用
     * @param $name
     * @param $arguments
     * @return mixed
     * @user lovexjho 2024-08-03
     */
    public function __call($name, $arguments)
    {
        $handler = $this->handlerStackFactory->create();

        $this->client =  make(Client::class, [
            'config' => [
                'handler' => $handler,
            ]
        ]);

        return $this->client->{$name}(...$arguments);
    }

    /**
     * 请求日志保存
     * @param ResponseInterface $response
     * @param $flag
     * @return true
     * @throws \Exception
     * @user lovexjho 2024-08-03
     */
    public function saveJsonLog(ResponseInterface $response, $flag = 'default')
    {

        $log = [
            'headers' => $response->getHeaders(),
            'response' => json_decode($response->getBody()->getContents(), true),
        ];

        if (!file_exists(self::LOG_SAVE_PATH) && !mkdir(self::LOG_SAVE_PATH, 0755)) {
            throw new \Exception('目录创建失败');
        }

        $filename = self::LOG_SAVE_PATH . '/' . $flag . '_' . date('Ymdhi') . '.json';

        $num = 2;
        while (file_exists($filename)) {
            $filename = self::LOG_SAVE_PATH . '/' . $flag . '_' . date('Ymdhi') . '-' . $num . '.json';
            $num++;
        }

        $success = file_put_contents($filename, json_encode($log, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if (!$success) {
            throw new \Exception('日志写入失败');
        }

        return true;
    }
}