<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @method static create(array $array)
 * @method static where(string $string, mixed $int)
 * @method static find(mixed $int)
 * @property string $uuid
 * @property string $owner
 * @property string $title
 * @property string $description
 * @property int $group_size
 * @property string $participants
 * @property string $reserve
 * @property string $type
 */
class Lfg extends Model
{
    use HasFactory;

    protected $table = 'lfg';

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['owner', 'title', 'description', 'group_size', 'type', 'time_of_start'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->setAttribute('uuid', Str::uuid());
        });
    }
}
