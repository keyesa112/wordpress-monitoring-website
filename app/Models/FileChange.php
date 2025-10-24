<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileChange extends Model
{
    protected $fillable = [
        'website_id',
        'file_path',
        'change_type',
        'old_hash',
        'new_hash',
        'is_suspicious',
        'suspicious_patterns',
        'file_preview',
        'severity',
        'recommendation',
    ];

    protected $casts = [
        'suspicious_patterns' => 'array',
        'is_suspicious' => 'boolean',
    ];

    public function website()
    {
        return $this->belongsTo(Website::class);
    }

    public function getSeverityBadgeAttribute()
    {
        $badges = [
            'critical' => '<span class="badge badge-danger">Critical</span>',
            'warning' => '<span class="badge badge-warning">Warning</span>',
            'info' => '<span class="badge badge-info">Info</span>',
        ];
        return $badges[$this->severity] ?? '<span class="badge badge-secondary">Unknown</span>';
    }

    public function getChangeTypeBadgeAttribute()
    {
        $badges = [
            'new' => '<span class="badge badge-success">New</span>',
            'modified' => '<span class="badge badge-warning">Modified</span>',
            'deleted' => '<span class="badge badge-danger">Deleted</span>',
        ];
        return $badges[$this->change_type] ?? '<span class="badge badge-secondary">Unknown</span>';
    }
}
