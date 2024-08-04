<?php

declare(strict_types=1);

namespace App\Listener;

use App\Event\LoginEvent;
use App\Model\User;
use App\Services\LoginLogService;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\HttpMessage\Exception\ForbiddenHttpException;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Logger\LoggerFactory;
use MessageNotify\Channel\DingTalkChannel;
use MessageNotify\Notify;
use MessageNotify\Template\Text;
use Psr\Container\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Log\LoggerInterface;
use UAParser\Exception\FileNotFoundException;
use UAParser\Parser;
use function App\Tools\getClientIP;
use function App\Tools\isProd;
use function Hyperf\Config\config;
use function Hyperf\Coroutine\co;
use function Hyperf\Support\env;

/**
 * 登录事件监听器
 * @package App\Listener
 * @class LoginListener
 * @author lovexjho 2024-08-03
 */
#[Listener]
class LoginListener implements ListenerInterface
{
    #[Inject]
    public LoginLogService $loginLogService;

    #[Inject]
    public ClientFactory $clientFactory;

    #[Inject]
    public RequestInterface $request;

    public LoggerInterface $logger;

    public function __construct(protected ContainerInterface $container, protected LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->get('ip', 'request');
    }

    public function listen(): array
    {
        return [
            LoginEvent::class
        ];
    }

    /**
     * @param object $event
     * @return void
     * @throws GuzzleException
     * @throws FileNotFoundException
     */
    public function process(object $event): void
    {
        /** @var User $user */
        $user = $event->user;
        $data = [
            'user_id' => $user->id,
            'status' => $event->status,
        ];

        $ip = getClientIP();

        $user_agent = $this->request->getHeader('user-agent');
        // 无user-agent头则抛403
        if (empty($user_agent)) {
            throw new ForbiddenHttpException();
        }

        // ua解析
        $parser = Parser::create();
        $result = $parser->parse($user_agent[0]);
        $data['browser'] = $result->ua->toString();
        $data['operating_system'] = $result->os->toString();

        $data['ip'] = $ip;

        /**
         * 如果是内网IP则不发送请求
         */
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
            $data['address'] = '局域网';
        } else {
            // 请求高德IP地址接口
            $gdKey = $this->container->get(ConfigInterface::class)->get('request.gd_key', false);
            if (!$gdKey) {
                $this->logger->info('高德Key未配置');
            } else {
                $client = $this->clientFactory->create();
                $url = 'https://restapi.amap.com/v3/ip?ip=' . $ip . '&key=' . $gdKey;
                $response = $client->get($url);
                $result = json_decode($response->getBody()->getContents(), true);
                if ($result['status'] != 1) {
                    $this->logger->error('高德api请求失败，原因：' . $result['info']);
                } else {
                    $data['address'] = $result['province'] . ' ' . (empty($result['city']) ? '' : $result['city']);
                }
            }
        }

        $res = $this->loginLogService->create($data);

        if (!$res) {
            $this->logger->error('登录日志保存失败');
        }

        // 发送钉钉通知
        if (isProd() && $event->status) {
            if (empty(env('NOTIFY_DINGTALK_TOKEN'))
                || empty(env('NOTIFY_DINGTALK_SECRET'))) {
                $this->loggerFactory->get('dingTalk', 'request')->info('未配置token或secret');
                return;
            }

            co(function () use ($data) {
                $content = sprintf(
                    '你于 %s 在 %s 使用 %s 浏览器 %s 操作系统 登录成功 IP地址：%s',
                    date('Y/m/d H:i:s'),
                    $data['address'],
                    $data['browser'],
                    $data['operating_system'],
                    $data['ip']
                );

                Notify::make()
                    ->setChannel(DingTalkChannel::class)
                    ->setTemplate(Text::class)
                    ->setTitle('登录通知')
                    ->setText($content)
                    ->send();
            });
        }
    }
}
