<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'url',
        'server_path', 
        'status',
        'response_time',
        'http_code',
        'has_suspicious_content',
        'suspicious_posts_count',
        'last_check_result',
        'last_checked_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
        'has_suspicious_content' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship ke monitoring logs
     */
    public function monitoringLogs()
    {
        return $this->hasMany(MonitoringLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get latest log
     */
    public function latestLog()
    {
        return $this->hasOne(MonitoringLog::class)->latestOfMany();
    }

     public function fileChanges()
    {
        return $this->hasMany(FileChange::class);
    }

    /**
     * Badge HTML untuk status
     */
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'online' => '<span class="badge badge-success">Online</span>',
            'offline' => '<span class="badge badge-danger">Offline</span>',
            'checking' => '<span class="badge badge-warning">Checking...</span>',
            'error' => '<span class="badge badge-secondary">Error</span>',
            default => '<span class="badge badge-secondary">Unknown</span>',
        };
    }

    /**
     * Badge HTML untuk konten mencurigakan
     */
    public function getSuspiciousBadgeAttribute()
    {
        if ($this->has_suspicious_content) {
            return '<span class="badge badge-danger">
                <i class="fas fa-exclamation-triangle"></i> ' . 
                $this->suspicious_posts_count . ' Post
            </span>';
        }
        
        return '<span class="badge badge-success"><i class="fas fa-check"></i> Clean</span>';
    }

    /**
     * Format response time
     */
    public function getFormattedResponseTimeAttribute()
    {
        if (!$this->response_time) {
            return 'N/A';
        }
        
        return $this->response_time . ' ms';
    }

    /**
     * Human readable last checked
     */
    public function getLastCheckedHumanAttribute()
    {
        if (!$this->last_checked_at) {
            return 'Belum pernah dicek';
        }
        
        return $this->last_checked_at->diffForHumans();
    }

    /**
     * Scope untuk website aktif saja
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk website dengan konten mencurigakan
     */
    public function scopeSuspicious($query)
    {
        return $query->where('has_suspicious_content', true);
    }
}