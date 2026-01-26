<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'message_id',
        'to_address',
        'subject',
        'intro',
        'created_at_api',
        'raw_json',
    ];

    protected $casts = [
        'raw_json' => 'array',
        'created_at_api' => 'datetime',
    ];

    public function otpCode()
    {
        return $this->hasOne(OtpCode::class, 'message_id', 'message_id');
    }
}
