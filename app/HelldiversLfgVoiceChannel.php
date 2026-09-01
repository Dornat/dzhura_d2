<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|static create(array $array = [])
 * @method static \Illuminate\Database\Eloquent\Builder|static where(string|array|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder|static find(mixed $id)
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @property string $guild_id
 * @property string $vc_discord_id
 * @property string $lfg_channel_id
 * @property string $lfg_message_id
 * @property string $tag_message_id
 * @property string $owner
 * @property string $name
 * @property integer $user_limit
 * @property string $category
 * @property string $participants
 * @property int $vc_rename_count
 * @property $vc_rename_date
 */
class HelldiversLfgVoiceChannel extends Model
{
    use HasFactory;

    protected $casts = [
        'vc_rename_date' => 'datetime'
    ];

    protected $table = 'hd_lfg_vcs';

    protected $fillable = ['guild_id', 'vc_discord_id', 'lfg_channel_id', 'lfg_message_id', 'owner', 'name', 'user_limit', 'category', 'participants'];
}
