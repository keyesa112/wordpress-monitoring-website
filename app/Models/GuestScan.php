<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestScan extends Model
{
    protected $fillable = [
        'url',
        'ip_address',
        'user_agent',
        'status',
        'has_suspicious_content',
        'suspicious_posts_count',
        'scan_result',
    ];

    protected $casts = [
        'has_suspicious_content' => 'boolean',
        'scan_result' => 'array',
    ];
}
