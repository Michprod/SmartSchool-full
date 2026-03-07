<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'created_by', 'title', 'message', 'type', 'channels', 'recipients', 'read_by', 'sent_at',
    ];

    protected $casts = [
        'channels' => 'array',
        'recipients' => 'array',
        'read_by' => 'array',
        'sent_at' => 'datetime',
    ];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
