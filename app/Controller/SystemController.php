<?php

namespace App\Controller;

use App\Middleware\PermissionMiddleware;
use App\Services\LoginLogService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middlewares;
use Qbhy\HyperfAuth\AuthMiddleware;

/**
 * 系统控制器
 * @package App\Controller
 * @class SystemController
 * @author lovexjho 2024-08-03
 */
#[Controller]
#[Middlewares([AuthMiddleware::class,PermissionMiddleware::class])]
class SystemController extends AbstractController
{

    #[Inject]
    public LoginLogService $loginLogService;

    /**
     * 获取系统信息
     * @return \Psr\Http\Message\ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[GetMapping('info')]
    public function info()
    {
        $systemInfo = array(
            "hostname" => PHP_OS,
            "os_release" => php_uname('s'),
            "os_type" => php_uname('m'),
            "kernel_version" => php_uname('r'), // 获取内核版本
            "php_version" => phpversion()
        );
        return $this->success($systemInfo);
    }

    /**
     * 获取系统负载信息
     * @return \Psr\Http\Message\ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[GetMapping('loadavg')]
    public function loadavg()
    {
        return $this->success([
            'load' => sys_getloadavg(), // 系统负载
            'disk' => [
                'total_space' => ceil(disk_total_space('/') / pow(1024, 3)) ,
                'free_space' => ceil(disk_free_space('/') / pow(1024, 3))
            ]
        ]);
    }

    /**
     * 获取硬盘信息
     * @param int $total
     * @return string
     * @user lovexjho 2024-08-03
     */
    function getDisk(int $total) : string
    {
        $config = [
            '3' => 'GB',
            '2' => 'MB',
            '1' => 'KB'
        ];
        foreach($config as $key => $value){
            if($total > pow(1024, $key)){
                return round($total / pow(1024,$key)).$value;
            }
            return $total . 'B';
        }
    }

    /**
     * 登录日志列表
     * @return \Psr\Http\Message\ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[GetMapping('loginlog/list')]
    public function loginlog()
    {
        $page = $this->request->query('pageNo');
        $page = max($page, 1);
        $pageSize = $this->request->query('pageSize', 10);
        $status = $this->request->query('status');

        $conditions = [];
        if ($status) {
            $conditions[] = ['status', $status];
        }

        $paginate = $this->loginLogService->paginate($conditions, $page, $pageSize, ['created_at' => 'desc']);

        return $this->success($paginate);
    }
}