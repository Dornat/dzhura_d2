<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, string $guildId)
 * @property string $guild_id
 * @property string $user_id
 * @property int $level
 * @property int $xp_current
 * @property int $xp_total
 * @property int $messages
 * @property Carbon|mixed $suspended
 */
class Level extends Model
{
    use HasFactory;

    protected $table = 'levels';

    protected $fillable = ['guild_id', 'user_id', 'level', 'xp_current', 'xp_total', 'messages', 'suspended'];
}
