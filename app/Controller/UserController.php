<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\PermissionMiddleware;
use App\Request\UserRequest;
use App\Services\ProfileService;
use App\Services\UserService;
use App\Tools\Client;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PatchMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\ValidatorFactory;
use Psr\Http\Message\ResponseInterface;
use Qbhy\HyperfAuth\AuthManager;
use Qbhy\HyperfAuth\AuthMiddleware;

/**
 * 用户控制器
 * @package App\Controller
 * @class UserController
 * @author lovexjho 2024-08-03
 */
#[Controller]
#[Middleware(AuthMiddleware::class)]
class UserController extends AbstractController
{
    #[Inject]
    public AuthManager $auth;

    #[Inject]
    public UserService $userService;

    #[Inject]
    public ValidatorFactoryInterface $validatorFactory;

    /**
     * 用户详细信息
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[RequestMapping('detail', 'get')]
    public function detail()
    {
        $userRes = $this->userService->getUserInfo(['id' => $this->auth->id()]);
        if (!$userRes) {
            return $this->error();
        }

        return $this->success($userRes);
    }

    /**
     * 用户信息更改提交
     * @param $id
     * @param ValidatorFactory $factory
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[PatchMapping('profile/{id:\d+}')]
    public function profile($id, ValidatorFactory $factory)
    {
        $data = $this->request->all();

        $validator = $factory->make($data,[
            'avatar' => 'bail|nullable|url',
            'nickName' => 'bail|nullable|between:2,30',
            'gender' => 'bail|nullable|in:0,1,2',
            'email' => 'bail|nullable|email',
            'address' => 'bail|nullable|string|between:2,30'
        ]);

        $data = $validator->validated();

        $updateRes = $this->userService->updateProfile($data);

        if (!$updateRes) {
            $this->error();
        }

        return $this->success();
    }

    /**
     * 用户列表
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[GetMapping('')]
    #[Middleware(PermissionMiddleware::class)]
    public function paginate()
    {
        $page = $this->request->query('page', 1);
        $pageSize = $this->request->query('pageSize', 10);
        $gender = $this->request->query('gender');
        $enable = $this->request->query('enable', '');
        $username = $this->request->query('username');
        $condition = [];

        if ($username) {
            $condition[] = ['username','like', '%'.$username.'%'];
        }

        if ($gender) {
            $condition[] = ['profile.gender', $gender];
        }

        if (strlen($enable)) {
            $condition[] = ['enable','=', $enable];
        }

        $paginate = $this->userService->paginate($condition, $page, $pageSize, ['created_at' => 'desc']);

        return $this->success($paginate);
    }


    /**
     * 更新用户
     * @param $id
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[PatchMapping('{id:\d+}')]
    #[Middleware(PermissionMiddleware::class)]

    public function update($id)
    {
        $validator = $this->validatorFactory->make($this->request->all(),[
            'roleIds' => 'bail|required|sometimes|array',
            'enable'  => 'bail|sometimes|boolean',
            'username' => 'bail|sometimes|required'
        ]);

        $data = $validator->validated();

        $assignRes = $this->userService->updateOne(['id' => $id], $data);

        if (!$assignRes) {
            return $this->error();
        }

        return $this->success();
    }

    /**
     * 删除用户
     * @param $id
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[DeleteMapping('{id:\d+}')]
    #[Middleware(PermissionMiddleware::class)]

    public function delete($id)
    {
        $userRes = $this->userService->getOneById($id);

        if (!$userRes) {
            return $this->error();
        }

        $deleteRes = $this->userService->deleteById($id);

        if (!$deleteRes) {
            return $this->error();
        }

        return $this->success();
    }

    /**
     * 新增用户
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[PostMapping('')]
    #[Middleware(PermissionMiddleware::class)]
    public function add()
    {
        $validator = $this->validatorFactory->make($this->request->all(), [
            'enable'    => 'bail|required|boolean',
            'password'  => 'bail|required|between:6,50', // 前端存在确认密码时添加验证参数 confirmed
            'username'  => 'bail|required|between:2,10|unique:user,username|alpha_dash',
            'roleIds'   => 'bail|required|array'
        ]);
        $data = $validator->validated();

        $addUser = $this->userService->add($data);

        if (!$addUser) {
            return $this->error();
        }

        return $this->success();
    }

    /**
     * 重制用户密码
     * @param $id
     * @return ResponseInterface
     * @user lovexjho 2024-08-04
     */
    #[PatchMapping('password/reset/{id:\d+}')]
    #[Middleware(PermissionMiddleware::class)]
    public function passwordRest($id)
    {
        $user = $this->userService->getOneById($id);

        if (!$user) return $this->error();

        $validator = $this->validatorFactory->make($this->request->all(), [
           'password' =>  'bail|required|between:6,50'
        ]);

        $data  = $validator->validated();

        $updateRes = $this->userService->updateById($id, $data);

        if (!$updateRes) return $this->error();

        return $this->success();
    }
}
