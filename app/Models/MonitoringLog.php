<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'website_id',
        'check_type',
        'user_id',
        'status',
        'response_time',
        'http_code',
        'has_suspicious_content',
        'suspicious_posts_count',
        'suspicious_posts',
        'error_message',
        'raw_result',
    ];

    protected $casts = [
        'has_suspicious_content' => 'boolean',
        'suspicious_posts' => 'array',
        'raw_result' => 'array',
    ];

    /**
     * Relationship ke website
     */
    public function website()
    {
        return $this->belongsTo(Website::class);
    }

    /**
     * Badge untuk check type
     */
    public function getCheckTypeBadgeAttribute()
    {
        return match($this->check_type) {
            'ping' => '<span class="badge badge-info">Ping</span>',
            'backlink' => '<span class="badge badge-warning">Backlink</span>',
            'full' => '<span class="badge badge-primary">Full Check</span>',
            default => '<span class="badge badge-secondary">Unknown</span>',
        };
    }

    /**
     * Badge untuk status
     */
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'online' => '<span class="badge badge-success">Online</span>',
            'offline' => '<span class="badge badge-danger">Offline</span>',
            'error' => '<span class="badge badge-secondary">Error</span>',
            default => '<span class="badge badge-secondary">Unknown</span>',
        };
    }
}