<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonnelConfigItem extends Model
{
    protected $fillable = ['type', 'label', 'code', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
