<?php

namespace App\Services;

use App\Model\Role;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Hyperf\DbConnection\Db;

/**
 * 角色service
 * @package App\Services
 * @class RoleService
 * @author lovexjho 2024-08-03
 */
class RoleService
{
    /**
     * 角色列表分页
     * @param array $conditions
     * @param int $page
     * @param int $pageSize
     * @param $order
     * @param $column
     * @return array
     * @user lovexjho 2024-08-03
     */
    public function paginate(array $conditions, int $page, int $pageSize, $order = [], $column = ['*'])
    {
        $model = Role::query()->with(['rolePermission']);

        $count = $model->where($conditions)->count();

        $pageCount = ceil($count / $pageSize);

        $data = $model->where($conditions)->forPage($page, $pageSize)->get($column);

        // 权限信息获取
        $pageData = $data->toArray();
        foreach ($pageData as &$value) {
            $value['permissionIds'] = array_column($value['role_permission'], 'permission_id');
            unset($value['role_permission']);
        }

        return [
            'pageNo' => $page,
            'pageData' => $pageData,
            'pageCount' => $pageCount,
            'total' => $count,
            'pageSize' => $pageSize
        ];
    }

    /**
     * 添加一条数据
     * @param $data
     * @return bool
     * @user lovexjho 2024-08-03
     */
    public function add($data)
    {
        if (empty($data['code']) || empty($data['name']) || !is_bool($data['enable'])) {
            return false;
        }
        Db::beginTransaction();
        $permissionIds = $data['permissionIds'];
        unset($data['permissionIds']);

        $model = new Role();
        foreach ($data as $k => $v) {
            $model->{$k} = $v;
        }

        if (!$model->save()) {
            Db::rollBack();
            return false;
        }

        if (count($permissionIds)) {
            $model->permission()->syncWithoutDetaching($permissionIds);
        }

        Db::commit();
        return true;
    }

    /**
     * 通过id更新一条数据
     * @param $id
     * @param $data
     * @return bool
     * @user lovexjho 2024-08-03
     */
    public function updateById($id, $data)
    {
        $model = $this->findById($id);
        if (!$model) return false;
        if (array_key_exists('permissionIds', $data)) {
            $permissionIds = $data['permissionIds'];
            $model->permission()->sync($permissionIds);
            unset($data['permissionIds']);
        }

        foreach ($data as $k => $v) {
            $model->{$k} = $v;
        }

        if (!$model->save()) {
            return false;
        }
        return true;
    }

    /**
     * 通过id获取一条数据
     * @param $id
     * @return Role|Role[]|false|\Hyperf\Database\Model\Collection|\Hyperf\Database\Model\Model
     * @user lovexjho 2024-08-03
     */
    public function findById($id)
    {
        $model = Role::findFromCache($id);

        if (!$model) {
            return false;
        }

        return $model;
    }

    /**
     * 通过id删除一条数据
     * @param $id
     * @return bool
     * @throws \Exception
     * @user lovexjho 2024-08-03
     */
    public function deleteById($id)
    {
        $model = $this->findById($id);
        if (!$model) return false;
        // 能删除的角色是未分配给用户的
        if (!empty($model->user->toArray())) return false;
        if (!$model->delete()) return false;
        return true;
    }

    /**
     * 取消用户角色授权
     * @param $id
     * @param array $data
     * @return bool
     * @user lovexjho 2024-08-03
     */
    public function cancel($id, array $data)
    {
        $model = $this->findById($id);
        if (!$model) return false;

        $model->user()->detach($data);

        return true;
    }

    /**
     * 添加用户授权
     * @param $id
     * @param $data
     * @return bool
     * @user lovexjho 2024-08-03
     */
    public function assign($id, $data)
    {
        $model = $this->findById($id);
        if (!$model) return false;

        $model->user()->syncWithoutDetaching($data);

        return true;
    }

    /**
     * 根据条件获取所有数据
     * @param array $condition
     * @param array $with
     * @param $column
     * @return mixed[]|null
     * @user lovexjho 2024-08-03
     */
    public function getAll(array $condition, array $with = [], $column = ['*'])
    {
        $model = Role::where($condition);

        if ($with) {
            $model = $model->with($with);
        }

        return $model->get($column)?->toArray();
    }

    /**
     * 根据条件获取一条数据
     * @param array $condition
     * @return array|false
     * @user lovexjho 2024-08-03
     */
    public function getOne(array $condition)
    {
        $model = Role::where($condition)->first();

        if (!$model) return false;

        return $model->toArray();
    }

    /**
     * 获取登录用户的角色
     * @param array $condition
     * @return mixed[]
     * @user lovexjho 2024-08-03
     */
    public function getUserRoles(array $condition)
    {
        $user = auth()->user();

        $roles = $user->roles()
            ->where($condition)
            ->get()
            ->toArray();

        return $roles;

    }
}