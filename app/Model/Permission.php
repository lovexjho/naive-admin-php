<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use Hyperf\ModelCache\Cacheable;
use Hyperf\ModelCache\CacheableInterface;

/**
 * @property int $id 
 * @property string $name 
 * @property string $code 
 * @property string $type 
 * @property int $parentId 
 * @property string $path 
 * @property string $redirect 
 * @property string $icon 
 * @property string $component 
 * @property string $layout 
 * @property int $keepAlive 
 * @property string $method 
 * @property int $enable 
 * @property string $description 
 * @property int $show 
 * @property int $order 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class Permission extends Model implements CacheableInterface
{
    use Cacheable;
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'permission';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'parentId' => 'integer', 'keepAlive' => 'boolean', 'enable' => 'boolean', 'show' => 'boolean', 'order' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function isEnable()
    {
        return $this->enable;
    }

    public function isNotEnable()
    {
        return $this->enable == false;
    }
}
