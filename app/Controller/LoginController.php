<?php

declare(strict_types=1);

namespace App\Controller;

use App\Event\LoginEvent;
use App\Services\RoleService;
use App\Services\UserService;
use App\Tools\CaptchaVerify;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Qbhy\HyperfAuth\AuthManager;
use Qbhy\HyperfAuth\AuthMiddleware;
use function App\Tools\isProd;

/**
 * 登录控制器
 * @package App\Controller
 * @class LoginController
 * @author lovexjho 2024-08-03
 */
#[Controller(prefix: 'auth')]
class LoginController extends AbstractController
{
    #[Inject]
    public AuthManager $auth;

    #[Inject]
    public UserService $userService;

    #[Inject]
    public EventDispatcherInterface $dispatcher;

//    #[Inject]
//    public CaptchaVerify $captchaVerify;

    /**
     * 登录提交
     * @param ValidatorFactoryInterface $validatorFactory
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[PostMapping('login')]
    public function login(ValidatorFactoryInterface $validatorFactory)
    {
        $valData = [
            'username' => 'required',
            'password' => 'required',
        ];


//        if (isProd()) {
//            $valData['captchaVerifyParam'] = 'bail|required|json';
//        }

        $validator = $validatorFactory->make($this->request->all(), $valData);

        $data = $validator->validated();

        $user = $this->userService->getOne(['username' => $data['username']]);

        if (!$user) {
            return $this->error(['captchaResult' => false, 'bizResult' => false], 600, '用户名或密码错误');
        }

        // 生产环境验证
//        if (isProd()) {
//            $validateRes = $this->captchaVerify->validateCaptcha($data['captchaVerifyParam']);
//            if (!$validateRes) {
//                return $this->error(['captchaResult' => false, 'bizResult' => false], 600, '验证失败');
//            }
//        }

        $validateStr = $user->getAttribute('salt') . $data['password'];

        if (!password_verify($validateStr, $user->getAttribute('password'))) {
            $this->dispatcher->dispatch(new LoginEvent($user, false));
            return $this->error(['captchaResult' => true, 'bizResult' => false], 600, '用户名或密码错误');
        }

        if ($user->isNotEnable()) {
            return $this->error(['captchaResult' => false, 'bizResult' => false], 600, '用户已停用');
        }

        $this->dispatcher->dispatch(new LoginEvent($user, true));

        return $this->success(['captchaResult' => true, 'bizResult' => true, 'accessToken' => $this->auth->login($user)]);
    }

    /**
     * 退出登录
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[PostMapping('logout')]
    public function logout()
    {
        return $this->success($this->auth->logout());
    }

    /**
     * 更新密码
     * @param ValidatorFactoryInterface $validatorFactory
     * @return ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[PostMapping('password')]
    #[Middleware(AuthMiddleware::class)]
    public function password(ValidatorFactoryInterface $validatorFactory)
    {
        $validator = $validatorFactory->make($this->request->all(), [
            'oldPassword' => [
                'bail',
                'required',
                'between:6,50',
                function ($key, $value, $fail) {
                    $user = auth()->user();
                    if (!password_verify($user->salt . $value, $user->password)) {
                        $fail('原密码不正确');
                    }
                }
            ],
            'newPassword' => [
                'bail',
                'required',
                'between:6,50',
//                'confirmed:newPassword_confirmation',
                function ($k, $value, $fail) {
                    $user = auth()->user();
                    if (password_verify($user->salt . $value, $user->password)) {
                        $fail('新旧密码不能相同');
                    }
                }
            ]
        ]);

        $data = $validator->validated();

        $updateRes = $this->userService->updateOne(['id' => $this->auth->id()], ['password' => $data['newPassword']]);

        if (!$updateRes) {
            return $this->error();
        }

        return $this->success();
    }

    /**
     * 切换角色
     * @param $code
     * @param RoleService $roleService
     * @return ResponseInterface
     * @throws \RedisException
     * @user lovexjho 2024-08-03
     */
    #[PostMapping('current-role/switch/{code:[a-zA-Z0-9_]+}')]
    public function roleSwitch($code, RoleService $roleService)
    {
        $roles = $roleService->getUserRoles(['code' => $code]);

        if (!in_array($code, array_column($roles, 'code'))) return $this->error([], 600, '角色不存在');

        $role = array_filter($roles, function ($item) use ($code) {
            return $item['code'] == $code;
        });

        $setRes = $this->userService->setCurrRole(array_shift($role));

        if (!$setRes) return $this->error();

        return $this->success(['accessToken' => auth()->parseToken()]);
    }
}
