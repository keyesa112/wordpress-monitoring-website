<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileSnapshot extends Model
{
    protected $fillable = [
        'website_id',
        'file_path',
        'file_hash',
        'file_size',
        'last_modified',
        'status',
    ];

    protected $casts = [
        'last_modified' => 'datetime',
    ];

    public function website()
    {
        return $this->belongsTo(Website::class);
    }
}
