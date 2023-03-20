<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, string|null $guildId)
 * @property mixed|string|null $guild_id
 * @property mixed|string $object
 * @property mixed|string $created_by
 * @property mixed|string $updated_by
 */
class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    protected $fillable = ['guild_id', 'object', 'updated_by', 'created_by'];
}
