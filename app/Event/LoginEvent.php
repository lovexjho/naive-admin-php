<?php

namespace App\Event;

use App\Model\User;

/**
 * 登录事件
 * @package App\Event
 * @class LoginEvent
 * @author lovexjho 2024-08-03
 */
class LoginEvent
{
    public User $user;
    public bool $status;
    public function __construct(User $user, bool $status)
    {
        $this->user = $user;
        $this->status = $status;
    }
}