<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Model\Permission;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function App\Tools\isProd;

/**
 * 权限认证
 * @package App\Middleware
 * @class PermissionMiddleware
 * @author lovexjho 2024-08-03
 */
class PermissionMiddleware implements MiddlewareInterface
{
    public array $except = [
        'role/switch',  // 角色切换
    ];
    #[Inject]
    public ResponseInterface $response;

    public function __construct(protected ContainerInterface $container)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): \Psr\Http\Message\ResponseInterface
    {
        // 访问路由
        $actionName = rtrim($request->getUri()->getPath(), '/');
        $actionName = preg_replace('/\/\d+/', '', $actionName);
        $actionMethod = $request->getMethod();

        // 验证排除路由
        if (in_array($actionName, $this->except)) {
            return $handler->handle($request);
        }

        /**@var \App\Model\Role|null $currRole */
        $user = auth()->user();
        $roles = $user->roles;
        $rolesArr = $roles->toArray();

        // 如果是超级管理员，则不校验
        if (in_array('SUPER_ADMIN', array_column($rolesArr, 'code'))) {
            return $handler->handle($request);
        }

        // 当用户无角色权限时
        $res = [
            'code' => 403,
            'data' => [],
            'message' => '当前用户无角色权限，请联系管理员添加'
        ];
        if (empty($rolesArr)) {
            return $this->response->withHeader('Server', 'Hyperf')->withStatus(403)->withBody(new SwooleStream(json_encode($res, JSON_UNESCAPED_UNICODE)));
        }

        foreach ($roles as $role) {
            // 角色未启用，跳过
            if ($role->isNotEnable()) continue;

            $permissions = $role->permission()
                ->whereNotNull('path')
                ->get(['permission.path', 'permission.name', 'permission.method', 'permission.enable']);

            foreach ($permissions as $permission) {
                /** @var Permission $permission */
                if ($permission->path == $actionName
                    && $permission->method == $actionMethod) {
                    // 权限未启用，匹配成功后返回403
                    if ($permission->isNotEnable()) {
                        $res['message'] = '该权限已禁用';
                        return $this->response->withHeader('Server', 'Hyperf')
                            ->withStatus(403)
                            ->withBody(new SwooleStream(json_encode($res, JSON_UNESCAPED_UNICODE)));
                    }

                    return $handler->handle($request);
                }
            }
        }

        if (isProd()) {
            $res['message'] = '无权限';
        } else {
            $res['message'] = sprintf('无权限，路径:%s 方法:%s', $actionName, $actionMethod);
        }

        return $this->response->withHeader('Server', 'Hyperf')
            ->withStatus(403)
            ->withBody(new SwooleStream(json_encode($res, JSON_UNESCAPED_UNICODE)));
    }
}
