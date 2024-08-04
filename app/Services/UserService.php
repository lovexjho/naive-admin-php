<?php

namespace App\Services;

use App\Model\Permission;
use App\Model\Role;
use App\Model\User;
use Hyperf\Context\ApplicationContext;
use Hyperf\DbConnection\Db;
use Hyperf\Redis\Redis;
use function App\Tools\generateRandomString;

/**
 * 用户service
 * @package App\Services
 * @class UserService
 * @author lovexjho 2024-08-03
 */
class UserService
{

    private $cacheCurrRolePrefix = 'c:userCurrRole:%d';

    /**
     * 根据条件获取一条数据
     * @param array $condition
     * @return false|\Hyperf\Database\Model\Builder|\Hyperf\Database\Model\Model|object
     * @user lovexjho 2024-08-03
     */
    public function getOne(array $condition)
    {
        $data = User::where($condition)->first();
        if (is_null($data)) {
            return false;
        }

        return $data;
    }

    /**
     * 获取用户详情
     * @param array $conditions
     * @return array|false
     * @user lovexjho 2024-08-03
     * @throws \RedisException
     */
    public function getUserInfo(array $conditions)
    {
        $user = User::where($conditions)
            ->with(['profile', 'roles'])
            ->first();

        if (!$user) {
            return false;
        }

        $user = $user->toArray();

        if (empty($user['roles'])) return $user;

        // 从redis中取当前角色，不存在则使用用户的第一个角色
        $redis = ApplicationContext::getContainer()->get(Redis::class);
        if ($currData = $redis->hGetAll($this->getCacheKey($user['id']))) {
            $user['currentRole'] = $currData;
        } else {
            $user['currentRole'] = $user['roles'][0];
            $setRole = $redis->hMSet($this->getCacheKey($user['id']), $user['roles'][0]);
            if (!$setRole) return false;
        }

        return $user;
    }

    /**
     * 获取用户权限树
     * @param array $condition
     * @return mixed[]
     * @user lovexjho 2024-08-03
     */
    public function getPermissionTree()
    {
        $roles = auth()->user()->roles;

        // 超级管理员返回全部权限
        if (in_array('SUPER_ADMIN', array_column($roles->toArray(), 'code'))) {
            return Permission::orderBy('id')->get()->toArray();
        }
        $permission = [];
        foreach ($roles as $role) {
            /** @var Role $role */
            if ($role->isNotEnable()) continue; // 角色未启用，跳过
            $permission = array_merge($permission, $role->permission()->orderBy('id')->get()->toArray());
        }
        return $permission;
    }

    /**
     * 更新登录用户详细信息
     * @param array $data
     * @return bool
     * @user lovexjho 2024-08-03
     */
    public function updateProfile(array $data)
    {
        $model = auth()->user()->profile;

        foreach ($data as $k => $v) {
            $model->{$k} = $v;
        }

        if (!$model->save()) {
            return false;
        }

        return true;
    }

    /**
     * 通过id更新一条数据
     * @param $condition
     * @param $data
     * @return bool
     * @user lovexjho 2024-08-03
     */
    public function updateOne($condition, $data)
    {
        $model = User::where($condition)->first();

        if (!$model) {
            return false;
        }

        $roleIds = [];
        if (array_key_exists('roleIds', $data)) {
            $roleIds = $data['roleIds'];
            unset($data['roleIds']);
        }

        foreach ($data as $k => $v) {
            $model->{$k} = $v;
        }

        if (!$model->save()) {
            return false;
        }

        if (count($roleIds)) {
            $model->roles()->sync($roleIds);
        }

        return true;
    }

    /**
     * 用户分页
     * @param array $conditions
     * @param int $page
     * @param $pageSize
     * @param $order
     * @param $column
     * @return array
     * @user lovexjho 2024-08-03
     */
    public function paginate(array $conditions, int $page, $pageSize, $order = [], $column = ['*'])
    {
        $model = User::with(['roles'])->leftJoin('profile','user.id', 'profile.user_id');

        $count = $model->where($conditions)->count();
        $pageCount = ceil($count / $pageSize);

        $data = $model->where($conditions)->forPage($page, $pageSize)->get($column);

        return [
            'pageNo' => $page,
            'pageData' => $data->makeHidden('user_id')->toArray(),
            'pageCount' => $pageCount,
            'total' => $count,
            'pageSize' => $pageSize
        ];
    }

    /**
     * 通过id获取一条数据
     * @param $id
     * @return User|false|\Hyperf\Database\Model\Model
     * @user lovexjho 2024-08-03
     */
    public function getOneById($id)
    {
        $model = User::findFromCache($id);
        if (!$model) return false;

        return $model;
    }

    /**
     * 根据id更新一条数据
     * @param $id
     * @param array $data
     * @return bool
     * @user lovexjho 2024-08-03
     */
    public function updateById($id, array $data)
    {
        $model = $this->getOneById($id);
        if (!$model) return false;

        foreach ($data as $k => $v) {
            $model->{$k} = $v;
        }

        if (!$model->save()) {
            return false;
        }

        return true;
    }

    /**
     * 根据id删除一条数据
     * @param $id
     * @return bool
     * @throws \Exception
     * @user lovexjho 2024-08-03
     */
    public function deleteById($id)
    {
        $model = $this->getOneById($id);
        if (!$model) return false;

        if (!$model->delete()) return false;

        return true;
    }

    /**
     * 添加一条数据
     * @param $data
     * @return bool
     * @user lovexjho 2024-08-03
     */
    public function add($data)
    {
        if (empty($data['password'])) {
            return false;
        }

        $roleIds = [];
        if (array_key_exists('roleIds', $data)) {
            $roleIds = $data['roleIds'];
            unset($data['roleIds']);
        }

        $data['salt'] = generateRandomString(8);
        $data = array_reverse($data);

        $model = new User();

        foreach ($data as $k => $v) {
            $model->{$k} = $v;
        }

        Db::beginTransaction();
        if (!$model->save()) {
            Db::rollBack();
            return false;
        }

        if (!$model->profile()->create([])) {
            Db::rollBack();
            return false;
        }

        if (count($roleIds)) {
            $model->roles()->syncWithoutDetaching($roleIds);
        }
        Db::commit();
        return true;
    }

    /**
     * 获取当前用户角色缓存前缀
     * @param $id
     * @return string
     * @user lovexjho 2024-08-03
     */
    private function getCacheKey($id): string
    {
        return sprintf(
            $this->cacheCurrRolePrefix,
            $id
        );
    }

    /**
     * 设置当前登录用户角色
     * @param array $role
     * @return bool
     * @throws \RedisException
     * @user lovexjho 2024-08-03
     */
    public function setCurrRole(array $role)
    {
        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis->del($this->getCacheKey(auth()->id()));
        $setRes = $redis->hMSet($this->getCacheKey(auth()->id()), $role);

        if (!$setRes) return false;

        return true;
    }
}