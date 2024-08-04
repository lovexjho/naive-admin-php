## 简介

Vue Naive PHP 使用[Hyperf](https://hyperf.wiki/3.1/#/zh-cn/)框架开发，提供Vue Naive Admin后台管理模板的后台接口

## 环境要求

- Hyperf >= 3.0
- PHP >= 8.1

## 安装

```bash
git clone https://github.com/lovexjho/naive-admin-php.git
```

## 启动

可本地启动或通过docker启动，

### 使用docker启动 (推荐)

```bash
cd naive-admin-php && docker network create  hyperf-skeleton && docker-compose up
```

设置环境变量，复制目录下`.env.example`文件，命名为`.env`文件，打开命令行执行

```bash
docker exec naive-ui-php  php bin/hyperf.php gen:auth-env
```

## 说明

除基础模版接口外，还有一些额外的接口

- 存储管理功能
    - 火山对象存储管理 ✅
    - 本地存储管理
- 登录日志 ✅
- 接入阿里云验证码2.0 ✅

## 使用的第三方sdk

- [96qbhy/hyperf-auth](https://github.com/qbhy/hyperf-auth)
- [ua-parser/uap-php](https://github.com/ua-parser/uap-php)
- [vinchan/message-notify](https://github.com/VinchanGit/message-notify) 做了一些更改适配hyperf3
- 其他依赖请参考`composer.json`文件

## 版权说明

本项目使用 `MIT协议`。

## 前端项目地址

[Vue Naive Admin](https://github.com/zclzone/vue-naive-admin.git)

## 其他

如果喜欢，请点个star🌟，万分感谢。