<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 * @method static where(string $string, string|null $id)
 * @method static find(string $id)
 * @property string $guild_id
 * @property string $vc_discord_id
 * @property string $lfg_channel_id
 * @property string $lfg_message_id
 * @property string $owner
 * @property string $name
 * @property integer $user_limit
 * @property string $category
 * @property string $participants
 */
class HelldiversLfgVoiceChannel extends Model
{
    use HasFactory;

    protected $table = 'hd_lfg_vcs';

    protected $fillable = ['guild_id', 'vc_discord_id', 'lfg_channel_id', 'lfg_message_id', 'owner', 'name', 'user_limit', 'category', 'participants'];
}
