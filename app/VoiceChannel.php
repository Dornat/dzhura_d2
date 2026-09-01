<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|static create(array $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder|static where(string|array|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder|static find(mixed $id)
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @property string $lfg_uuid
 * @property string $guild_id
 * @property string $vc_discord_id
 * @property string $owner
 * @property string $name
 * @property integer $user_limit
 * @property string $category
 */
class VoiceChannel extends Model
{
    use HasFactory;

    protected $table = 'voice_channels';

    protected $fillable = ['guild_id', 'vc_discord_id', 'owner', 'name', 'user_limit', 'category'];
}
