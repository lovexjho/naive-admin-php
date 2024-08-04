<?php

namespace App\Controller;

use App\Middleware\PermissionMiddleware;
use App\Services\PermissionService;
use App\Services\UserService;
use Hyperf\Database\Query\Builder;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PatchMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Qbhy\HyperfAuth\AuthMiddleware;

/**
 * 权限控制器
 * @package App\Controller
 * @class PermissionController
 * @author lovexjho 2024-08-03
 */
#[Controller]
#[Middleware(AuthMiddleware::class)]
class PermissionController extends AbstractController
{
    #[Inject]
    public PermissionService $permissionService;

    #[Inject]
    public ValidatorFactoryInterface $validatorFactory;

    #[Inject]
    public UserService $userService;

    /**
     * 权限树all
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[GetMapping('tree')]
    public function tree()
    {
        $allRes = $this->permissionService->getPermissionTree();

        if (!$allRes) {
            return $this->error([], 600, '权限获取失败');
        }

        $tree = [];
        $data = [];
        foreach ($allRes as $menu) {
            $data[$menu['id']] = $menu;
            if ($menu['parentId'] == null) {
                $tree[$menu['id']] = &$data[$menu['id']];
            } else {
                $data[$menu['parentId']]['children'][] = &$data[$menu['id']];
            }
        }

        return $this->success(array_values($tree));
    }


    /**
     * 权限树-菜单
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[GetMapping('menu/tree')]
    #[Middleware(PermissionMiddleware::class)]
    public function menuTree()
    {
        $menus = $this->permissionService->getAll(['type' => 'MENU']);

        if (!$menus) {
            return $this->error([], 600, '菜单获取失败');
        }

        $tree = [];
        $data = [];
        foreach ($menus as $menu) {
            $data[$menu['id']] = $menu;
            if ($menu['parentId'] == null) {
                $tree[$menu['id']] = &$data[$menu['id']];
            } else {
                $data[$menu['parentId']]['children'][] = &$data[$menu['id']];
            }
        }

        return $this->success(array_values($tree));
    }

    /**
     * 菜单按钮获取
     * @param $id
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[GetMapping('button/{id:\d+}')]
    #[Middleware(PermissionMiddleware::class)]
    public function button($id)
    {
        $buttons = $this->permissionService->getAll(
            function ($query) use ($id) {
                $query->where('parentId', $id)->where(function ($query) {
                    $query->where('type', 'BUTTON')
                        ->orWhere('type', 'INTERFACE');
                });
            }
        );

        return $this->success($buttons);
    }

    /**
     * 接口获取
     * @param $id
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[GetMapping('interface/{id:\d+}')]
    #[Middleware(PermissionMiddleware::class)]
    public function interface($id)
    {
        $interface = $this->permissionService->getAll(['type' => 'interface', 'parentId' => $id]);

        return $this->success($interface);
    }

    /**
     * 添加菜单
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[PostMapping('')]
    #[Middleware(PermissionMiddleware::class)]
    public function add()
    {
        $data = $this->validateField();
        $addRes = $this->permissionService->add($data);

        if (!$addRes) {
            return $this->error();
        }

        return $this->success();
    }

    /**
     * 编辑菜单
     * @param $id
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[PatchMapping('{id:\d+}')]
    #[Middleware(PermissionMiddleware::class)]
    public function edit($id)
    {
        $data = $this->validateField($id);

        $permission = $this->permissionService->getOneById($id);

        if (!$permission) {
            return $this->error();
        }

        $updateRes = $this->permissionService->updateById($id, $data);

        if (!$updateRes) {
            return $this->error();
        }

        return $this->success();
    }

    /**
     * 菜单参数验证
     * @param $id
     * @return array
     * @user lovexjho 2024-08-03
     */
    private function validateField($id = null)
    {
        $validator = $this->validatorFactory->make($this->request->all(), [
            'code' => 'bail|required|string|max:50|unique:permission,code,' . $id,
            'component' => 'bail|nullable',
            'enable' => 'bail|required|boolean',
            'icon' => 'bail|nullable|string|max:50',
            'layout' => 'bail|nullable|string|max:50',
            'name' => 'bail|required|string|max:50',
            'order' => 'bail|nullable|integer|max:100',
            'path' => 'bail|nullable|string|max:50',
            'parentId' => 'bail|nullable|integer',
            'type' => 'bail|required|string|max:50',
            'show' => 'bail|required|boolean',
            'id' => 'bail|sometimes|required|integer',
            'method' => 'bail|sometimes|nullable|max:50',
            'keepAlive' => 'bail|sometimes|nullable|boolean'
        ]);

        return $validator->validated();
    }

    /**
     * 删除菜单
     * @param $id
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[DeleteMapping('{id:\d+}')]
    #[Middleware(PermissionMiddleware::class)]
    public function delete($id)
    {
        $permission = $this->permissionService->getOneById($id);

        if (!$permission) return $this->error();

        $deleteRes = $this->permissionService->deleteById($id);
        if (!$deleteRes) return $this->error();

        return $this->success();
    }


    /**
     * 验证菜单权限是404还是403
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[GetMapping('menu/validate')]
    public function menuValidate()
    {
        $query = $this->request->query('path');
        $menus = $this->permissionService->getPermissionTree();
        // 如果菜单存在返回true，报403
        if (in_array($query, array_column($menus, 'path'))) {
            return $this->success(true);
        }

        return $this->success(false);
    }
}