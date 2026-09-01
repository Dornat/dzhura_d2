<?php

namespace App;

use DateTime;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|static create(array $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder|static where(string|array|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder|static find(mixed $id)
 * @method static \Illuminate\Database\Eloquent\Builder|static whereIn(string $column, mixed $values)
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @property string $uuid
 * @property string $discord_id
 * @property string $channel_id
 * @property string $guild_id
 * @property string $owner
 * @property string $title
 * @property string $description
 * @property int $group_size
 * @property DateTime|false|mixed $time_of_start
 * @property string $type
 * @property boolean $manual
 * @property Collection $participants
 * @property Collection $participantsInQueue
 * @property Collection $reserve
 * @property VoiceChannel $vc
 */
class Lfg extends Model
{
    use HasFactory;

    protected $table = 'lfg';

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['channel_id', 'guild_id', 'owner', 'title', 'description', 'group_size', 'time_of_start', 'type', 'manual'];

    public static function boot(): void
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

    public function vc(): HasOne
    {
        return $this->hasOne(VoiceChannel::class, 'lfg_uuid', 'uuid');
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
