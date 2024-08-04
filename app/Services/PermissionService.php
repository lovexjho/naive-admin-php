<?php

namespace App\Services;

use App\Model\Permission;
use Hyperf\DbConnection\Db;

/**
 * 权限service
 * @package App\Services
 * @class PermissionService
 * @author lovexjho 2024-08-03
 */
class PermissionService
{

    /**
     * 获取所有权限
     * @return mixed[]
     * @user lovexjho 2024-08-03
     */
    public function getPermissionTree()
    {
        return Permission::orderBy('id')->get()->toArray();
    }

    /**
     * 通过条件获取多条数据
     * @param array|\Closure $condition
     * @param $order
     * @param $column
     * @return mixed[]
     * @user lovexjho 2024-08-03
     */
    public function getAll(array|\Closure $condition = [],$order = [],$column = ['*'])
    {
        $menus = Permission::where($condition);

        return $menus->get($column)->toArray();
    }

    /**
     * 新增一条数据
     * @param $data
     * @return bool
     * @user lovexjho 2024-08-03
     */
    public function add($data)
    {
        if (empty($data['code']) || empty($data['name'])) {
            return false;
        }

        $model = new Permission();

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
     * @return false|\Hyperf\Database\Model\Model|Permission
     * @user lovexjho 2024-08-03
     */
    public function getOneById($id)
    {
        $model = Permission::findFromCache($id);

        if (!$model) return false;

        return $model;
    }

    /**
     * 通过id更新一条数据
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

        if (!$model->save()) return false;

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
        Db::beginTransaction();

        if (!$model->delete()) {
            Db::rollBack();
            return false;
        }

        $delRes = Permission::where('parentId', $model->id);

        if (!$delRes->delete()) {
            Db::rollBack();
            return false;
        }

        Db::commit();

        return true;
    }
}