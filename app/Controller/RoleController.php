<?php

namespace App\Controller;

use App\Middleware\PermissionMiddleware;
use App\Services\RoleService;
use App\Services\UserService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PatchMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 角色控制器
 * @package App\Controller
 * @class RoleController
 * @author lovexjho 2024-08-03
 */
#[Controller]
class RoleController extends AbstractController
{

    #[Inject]
    public UserService $userService;

    #[Inject]
    public RoleService $roleService;

    #[Inject]
    public ValidatorFactoryInterface $validatorFactory;

    /**
     * 获取角色权限树
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[GetMapping('permissions/tree')]
    public function permissionsTree()
    {
        $menuRes = $this->userService->getPermissionTree();

        if (empty($menuRes))  return $this->success();

        $tree = [];
        $data = [];
        // 对菜单进行排序，
        // array_multisort(array_column($menuRes, 'id'), SORT_ASC, $menuRes);
        foreach ($menuRes as $menu) {
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
     * 角色分页
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[GetMapping('page')]
    #[Middleware(PermissionMiddleware::class)]
    public function page()
    {
        $page = $this->request->query('pageNo');
        $page = max($page, 1);
        $pageSize = $this->request->query('pageSize', 10);
        $name = $this->request->query('name');
        $enable = $this->request->query('enable');
        $conditions = [];
        if ($name) {
            $conditions[] = ['name', 'like', "%$name%"];
        }

        if ($enable) {
            $conditions[] = ['enable', $enable];
        }

        $paginate = $this->roleService->paginate($conditions, $page, $pageSize, ['created_at' => 'desc']);

        return $this->success($paginate);
    }

    /**
     * 角色更新
     * @param $id
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[PatchMapping('{id:\d+}')]
    #[Middleware(PermissionMiddleware::class)]
    public function update($id)
    {
        $validator = $this->validatorFactory->make($this->request->all(), [
            'id' => 'required|exists:role',
            'enable' => 'required|boolean',
            'name' => 'bail|sometimes|required',
            'permissionIds' => 'bail|sometimes|nullable|array'
        ]);

        $data = $validator->validated();

        $updateRes = $this->roleService->updateById($data['id'], $data);

        if (!$updateRes) {
            return $this->error();
        }

        return $this->success();
    }

    /**
     * 添加角色
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[PostMapping('')]
    #[Middleware(PermissionMiddleware::class)]
    public function add()
    {
        $validator = $this->validatorFactory->make( $this->request->all(), [
            'code' => 'bail|required|unique:role,code',
            'enable' => 'required|boolean',
            'name' => 'bail|required|between:2,10',
            'permissionIds' => 'bail|sometimes|nullable|array'
        ]);

        $data = $validator->validated();

        $addRes = $this->roleService->add($data);

        if (!$addRes) {
            return $this->error();
        }

        return $this->success();
    }

    /**
     * 删除角色
     * @param $id
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[DeleteMapping('{id:\d+}')]
    #[Middleware(PermissionMiddleware::class)]
    public function delete($id)
    {
        $role = $this->roleService->findById($id);

        if (!$role) {
            return $this->error();
        }

        $deleteRes = $this->roleService->deleteById($id);

        if (!$deleteRes) {
            return $this->error([], 600, '删除失败，角色不存在或已分配给用户使用');
        }

        return $this->success();
    }

    /**
     * 取消角色授权
     * @param $id
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[PatchMapping('users/remove/{id:\d+}')]
    #[Middleware(PermissionMiddleware::class)]
    public function usersRemove($id)
    {
        $validator = $this->validatorFactory->make($this->request->all(), [
            'userIds' => 'bail|required|array'
        ]);

        $data = $validator->validated();

        $roleRes = $this->roleService->findById($id);

        if (!$roleRes) {
            return $this->error();
        }

        $updateRes = $this->roleService->cancel($id, $data['userIds']);

        if(!$updateRes) {
            return $this->error();
        }

        return $this->success();
    }

    /**
     * 角色添加
     * @param $id
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[PatchMapping('users/add/{id:\d+}')]
    #[Middleware(PermissionMiddleware::class)]
    public function usersAdd($id)
    {
        $validator = $this->validatorFactory->make($this->request->all(), [
            'userIds' => 'bail|required|array'
        ]);

        $data = $validator->validated();

        $roleRes = $this->roleService->findById($id);

        if(!$roleRes) {
            return $this->error();
        }

        $updateRes = $this->roleService->assign($id, $data['userIds']);

        if(!$updateRes) {
            return $this->error();
        }

        return $this->success();
    }

    /**
     * 获取所有角色信息
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[GetMapping('')]
    #[Middleware(PermissionMiddleware::class)]
    public function enable()
    {
        $query = $this->request->query('enable', 1);

        $condition = [
            'enable' => (bool)$query
        ];

        $role = $this->roleService->getAll($condition);

        return $this->success($role);
    }
}