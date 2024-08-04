<?php

namespace App\Services;

use App\Model\LoginLog;

/**
 * 登录日志service
 * @package App\Services
 * @class LoginLogService
 * @author lovexjho 2024-08-03
 */
class LoginLogService
{

    /**
     * 创建登录日志
     * @param $data
     * @return bool
     * @user lovexjho 2024-08-03
     */
    public function create($data)
    {
        $model = new LoginLog();

        foreach ($data as $k => $v) {
            $model->{$k} = $v;
        }

        if (!$model->save()) {
            return false;
        }

        return true;
    }

    /**
     * 登录日志分页
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
        $model = LoginLog::with(['user' => function ($query) {
            $query->with(['profile' => function ($query) {
                $query->select(['user_id', 'avatar', 'nickName']);
            }])->select(['id', 'username']);
        }]);

        $count = $model->where($conditions)->count();
        $pageCount = ceil($count / $pageSize);

        if ($order) {
            foreach ($order as $k => $v) {
                $model = $model->orderBy($k, $v);
            }
        }

        $data = $model->where($conditions)->forPage($page, $pageSize)->get($column);

        return [
            'pageNo' => $page,
            'pageData' => $data->toArray(),
            'pageCount' => $pageCount,
            'total' => $count,
            'pageSize' => $pageSize
        ];

    }
}