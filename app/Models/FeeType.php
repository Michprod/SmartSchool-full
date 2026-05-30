<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeType extends Model
{
    protected $fillable = ['code', 'label', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function rates()
    {
        return $this->hasMany(FeeRate::class);
    }
}
