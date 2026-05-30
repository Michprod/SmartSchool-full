<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RdcProvince extends Model
{
    protected $table = 'rdc_provinces';

    protected $fillable = ['name', 'code'];

    public function cities(): HasMany
    {
        return $this->hasMany(RdcCity::class, 'province_id')->orderBy('name');
    }
}
