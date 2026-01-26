<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = [
        'message_id',
        'to_address',
        'otp',
        'source',
        'status',
    ];
}