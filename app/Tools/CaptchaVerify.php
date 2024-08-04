<?php

namespace App\Tools;

use AlibabaCloud\SDK\Captcha\V20230305\Captcha;
use AlibabaCloud\SDK\Captcha\V20230305\Models\VerifyIntelligentCaptchaRequest;
use AlibabaCloud\Tea\Exception\TeaError;
use Darabonba\OpenApi\Models\Config;
use Exception;
use Hyperf\Config\Annotation\Value;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 *
 * @package App\Tools
 * @class CaptchaVerify
 * @description 阿里云Captcha服务端验证
 * @link https://help.aliyun.com/zh/captcha/captcha2-0/user-guide/server-integration
 * @author lovexjho 2024-08-03
 */
class CaptchaVerify
{
    private $client;

    private string $sceneId;

    private LoggerInterface $logger;

    #[Value('request.ali_yun')]
    private array $env_config;

    public function __construct(protected LoggerFactory $configFactory)
    {
        $this->logger = $this->configFactory->get('captcha', 'request');

        $config = new Config([]);
        $config->accessKeyId = $this->env_config['access_key_id'];
        $config->accessKeySecret = $this->env_config['access_secret'];
        $config->endpoint = $this->env_config['endpoint'] ?? 'captcha.cn-shanghai.aliyuncs.com';
        $config->connectTimeout = $this->env_config['connect_timeout'] ?? 5000;
        $config->readTimeout = $this->env_config['read_timeout'] ?? 5000;
        $this->sceneId = $this->env_config['scene_id'];
        $this->client = new Captcha($config);
    }

    public function getClient()
    {
        return $this->client;
    }

    /**
     * 验证码验证
     * @param $captchaVerifyParam
     * @return bool
     */
    public function validateCaptcha($captchaVerifyParam): bool
    {
        // 创建APi请求
        $request = new VerifyIntelligentCaptchaRequest([]);
        $request->sceneId = $this->sceneId;
        // 前端传来的验证参数 CaptchaVerifyParam
        $request->captchaVerifyParam = $captchaVerifyParam;

        try {
            $resp = $this->client->verifyIntelligentCaptcha($request);
            $result = $resp->body->result;
            $captchaVerifyResult = $result->verifyResult;
            $captchaVerifyCode = $result->verifyCode;

            if (!$captchaVerifyResult) {
                $this->logger->info(
                    $this->getErrorMessage($captchaVerifyCode),
                    ['requestId' => $resp->body->requestId]
                );
            }

        } catch (Exception $error) {
            if (!($error instanceof TeaError)) {
                $error = new TeaError([], $error->getMessage(), $error->getCode(), $error);
            }
            // 保存日志
            $this->logger->error(var_export($error->getErrorInfo(), true));
            // 出现异常建议认为验证通过，优先保证业务可用，然后尽快排查异常原因。
            $captchaVerifyResult = true;
        }

        return $captchaVerifyResult;
    }

    /**
     * @param string $verifyCode
     * @return string
     */
    private function getErrorMessage(string $verifyCode)
    {
        return match ($verifyCode) {
            'F001' => '验证不通过',
            'F002' => '传入的CaptchaVerifyParam参数为空',
            'F003' => '传入的CaptchaVerifyParam格式不合法，请参考集成文档检查您的集成代码。',
            'F004' => '控制台开启测试模式下的验证不通过',
            'F005' => '场景ID不存在',
            'F006' => '场景ID不归属该账户',
            'F007' => '验证超出时间限制',
            'F008' => '验证数据重复提交',
            'F009' => '检测到虚拟设备环境，请使用真实设备',
            'F010' => '同IP访问频率超出限制',
            'F011' => '同设备访问频率超出限制',
            'F012' => '您传入的SceneID与CaptchaVerifyParam内的场景ID不一致',
            'F013' => '您传入的CaptchaVerifyParam缺少参数',
            default => '未知错误',
        };
    }
}