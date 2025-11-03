<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'website_id',
        'file_path',
        'change_type',          // added, modified, deleted
        'is_suspicious',
        'old_hash',
        'new_hash',
        'file_size',
        'last_modified',
    ];

    protected $casts = [
        'is_suspicious' => 'boolean',
        'last_modified' => 'datetime',
    ];

    // ✅ Relationship
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
