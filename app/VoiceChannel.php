<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 * @method static where(string $string, string|null $id)
 * @method static find(string $id)
 * @property mixed $vc_discord_id
 */
class VoiceChannel extends Model
{
    use HasFactory;

    protected $table = 'voice_channels';

    protected $fillable = ['vc_discord_id', 'owner', 'name', 'user_limit'];
}
