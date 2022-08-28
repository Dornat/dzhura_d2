<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed $user_id
 * @property mixed $declined
 * @property mixed $approved
 */
class ParticipantInQueue extends Model
{
    use HasFactory;

    protected $table = 'participants_in_queue';

    protected $fillable = ['user_id', 'declined', 'approved'];
}
