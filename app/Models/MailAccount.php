<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'domain',
        'account_id',
        'bearer_token',
        'quota',
        'used',
        'is_active',
        'is_disabled',
        'is_deleted',
        'message_count',
        'last_fetch_at',
        'account_created_at',
        'account_updated_at',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_disabled' => 'boolean',
        'is_deleted' => 'boolean',
        'message_count' => 'integer',
        'quota' => 'integer',
        'used' => 'integer',
        'last_fetch_at' => 'datetime',
        'account_created_at' => 'datetime',
        'account_updated_at' => 'datetime',
    ];

    /**
     * Scope untuk hanya mengambil akun aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get messages from this account
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'account_id');
    }
}
