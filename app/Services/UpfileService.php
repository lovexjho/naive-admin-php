<?php

namespace App\Services;

use App\Model\Upfile;

/**
 * 上传文件service
 * @package App\Services
 * @class UpfileService
 * @author lovexjho 2024-08-03
 */
class UpfileService
{
    /**
     * 创建一条数据
     * @param array $data
     * @return bool
     * @user lovexjho 2024-08-03
     */
    public function create(array $data)
    {
        $model = auth()->user()->upfile()->make($data);
        if (!$model->save()) {
            return false;
        }
        return true;
    }

    /**
     * 通过Name获取一条数据
     * @param $name
     * @return false|\Hyperf\Database\Model\Builder|\Hyperf\Database\Model\Model|object
     * @user lovexjho 2024-08-03
     */
    public function findByName($name)
    {
        $model = Upfile::where('client_name', $name)->first();

        if (!$model) {
            return false;
        }

        return $model;
    }

    /**
     * 通过id获取一条数据
     * @param $id
     * @return Upfile|Upfile[]|false|\Hyperf\Database\Model\Collection|\Hyperf\Database\Model\Model
     * @user lovexjho 2024-08-03
     */
    public function findById($id)
    {
        $model = Upfile::findFromCache($id);

        if (!$model) {
            return false;
        }

        return $model;
    }

    /**
     * 通过id更新一条数据
     * @param int $id
     * @param array $array
     * @return bool
     * @user lovexjho 2024-08-03
     */
    public function updateById(int $id, array $array)
    {
        $model = Upfile::find($id);

        if (!$model) {
            return false;
        }

        foreach ($array as $k => $v) {
            $model->{$k} = $v;
        }

        if (!$model->save()) {
            return false;
        }

        return true;

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
        $model = Upfile::find($id);

        if (!$model) {
            return false;
        }
        if (!$model->delete()) {
            return false;
        }

        return true;
    }

    /**
     * 分页
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
        $model = auth()->user()->upfile();

        $count = $model->where($conditions)->count();
        $pageCount = ceil($count/$pageSize);

        $data = $model->where($conditions)->forPage($page, $pageSize)->get($column);

        return [
            'pageNo' => $page,
            'pageData' =>$data->makeHidden(['model_id','model_type'])->toArray(),
            'pageCount' => $pageCount,
            'total' => $count,
            'pageSize' => $pageSize
        ];
    }
}