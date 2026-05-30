<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallmentType extends Model
{
    protected $fillable = ['code', 'label', 'default_count', 'is_active'];

    protected $casts = [
        'default_count' => 'integer',
        'is_active' => 'boolean',
    ];
}
