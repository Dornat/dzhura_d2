<?php

namespace App;

use DateTime;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
 * @property Collection $participants
 * @property Collection $participantsInQueue
 * @property Collection $reserve
 * @property string $type
 * @property boolean $manual
 * @property string $discord_id
 * @property DateTime|false|mixed $time_of_start
 */
class Lfg extends Model
{
    use HasFactory;

    protected $table = 'lfg';

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['owner', 'title', 'description', 'group_size', 'type', 'manual', 'time_of_start'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->setAttribute('uuid', Str::uuid());
        });
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class, 'lfg_uuid', 'uuid');
    }

    public function participantsInQueue(): HasMany
    {
        return $this->hasMany(ParticipantInQueue::class, 'lfg_uuid', 'uuid');
    }

    public function reserve(): HasMany
    {
        return $this->hasMany(Reserve::class, 'lfg_uuid', 'uuid');
    }

    protected function timeOfStart(): Attribute
    {
        return Attribute::get(
            function ($value) {
                return (new DateTime($value))->format('G:i j n');
            }
        );
    }
}
