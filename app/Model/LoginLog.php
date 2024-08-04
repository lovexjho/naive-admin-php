<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $user_id 
 * @property string $ip 
 * @property string $address 
 * @property string $browse 
 * @property string $operating_system 
 * @property int $status 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class LoginLog extends Model
{
    const SUCCESS = 1;
    const ERROR = 2;

    const STATUS_LABEL = [
      self::SUCCESS => '登录成功',
      self::ERROR => '登录失败'
    ];
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'login_log';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'user_id' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
