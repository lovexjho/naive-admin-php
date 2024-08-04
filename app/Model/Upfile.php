<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use Hyperf\ModelCache\Cacheable;
use Hyperf\ModelCache\CacheableInterface;
use function Hyperf\Support\env;

/**
 * @property int $id 
 * @property string $model_type 
 * @property int $model_id 
 * @property string $client_name 
 * @property string $mime 
 * @property int $size 
 * @property int $visible 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property-read mixed $path 
 */
class Upfile extends Model implements CacheableInterface
{
    use Cacheable;
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'upfile';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['client_name', 'path', 'mime', 'size', 'visible'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'model_id' => 'integer', 'size' => 'integer', 'visible' => 'boolean', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function getPathAttribute()
    {
        if ($cname = env('TOS_CNAME')) {
            if (str_starts_with($cname,'https://')) {
                return trim($cname, '/'). '/'.trim($this->attributes['path'], '/');
            }

            return 'https://'.trim($cname, '/'). '/'.trim($this->attributes['path'], '/');
        }

        return 'https://'.env('TOS_BUCKET').
            '.'.'tos-'. env('TOS_REGION').
            '.volces.com'. '/'.
            trim($this->attributes['path'], '/');
    }
}
