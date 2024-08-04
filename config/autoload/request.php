<?php

use function Hyperf\Support\env;

/**
 * 各种请求api密钥
 */
return [
    'xf_key' => env('XF_KEY', ''), // 讯飞key
    'xf_secret' => env('XF_SECRET', ''), // 讯飞secret
    'gd_key' => env('GD_KEY', ''), // 高德key
    // 阿里云配置
    'ali_yun' => [
        'access_key_id' => env('ALI_ACCESS_KEY_ID', ''), // 阿里云access_key_id
        'access_secret' => env('ALI_ACCESS_KEY_SECRET', ''),// 阿里云access_secret
        'endpoint' => "captcha.cn-shanghai.aliyuncs.com", // 阿里云验证码endpoint
        'connect_timeout' => 5000,
        'read_timeout' => 5000,
        'scene_id' => env('ALI_SCENE_ID', '') // 阿里云验证码scene_id
    ],
];