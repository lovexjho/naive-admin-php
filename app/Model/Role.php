<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Db;
use Hyperf\DbConnection\Model\Model;
use Hyperf\ModelCache\Cacheable;
use Hyperf\ModelCache\CacheableInterface;

/**
 * @property int $id 
 * @property string $code 
 * @property string $name 
 * @property int $enable 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class Role extends Model implements CacheableInterface
{
    use Cacheable;
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'role';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];
    protected array $hidden = ['pivot'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'enable' => 'boolean', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function user()
    {
        return $this->belongsToMany(User::class, 'user_role', 'role_id', 'user_id')->withTimestamps();
    }

    public function permission()
    {
        return $this->belongsToMany(Permission::class, 'role_permission', 'role_id', 'permission_id')->withTimestamps();
    }

    public function rolePermission()
    {
        return $this->hasMany(RolePermission::class)->select(['permission_id', 'role_id']);
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
