<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use Hyperf\ModelCache\Cacheable;
use Hyperf\ModelCache\CacheableInterface;
use Qbhy\HyperfAuth\AuthAbility;
use Qbhy\HyperfAuth\Authenticatable;

/**
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $password
 * @property int $enable
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $salt
 * @property-read null|\Hyperf\Database\Model\Collection|Role[] $roles
 * @property-read null|Profile $profile
 * @property-read null|\Hyperf\Database\Model\Collection|Upfile[] $upfile
 * @property-read null|\Hyperf\Database\Model\Collection|LoginLog[] $loginLog
 */
class User extends Model implements Authenticatable, CacheableInterface
{
    use AuthAbility, Cacheable;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'user';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    protected array $hidden = [
        'password', 'salt'
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'enable' => 'boolean', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function upfile()
    {
        return $this->morphMany(Upfile::class, 'model');
    }

    public function loginLog()
    {
        return $this->hasMany(LoginLog::class);
    }

    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = password_hash($this->getSaltKey($password), PASSWORD_DEFAULT);
    }

    public function getSaltKey($password): string
    {
        return sprintf(
            '%s%s',
            $this->attributes['salt'],
            $password);
    }

    public function isEnable()
    {
        return $this->enable;
    }

    public function isNotEnable()
    {
        return $this->enable == false;
    }
}
