<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RdcCity extends Model
{
    protected $table = 'rdc_cities';

    protected $fillable = ['province_id', 'name'];

    public function province(): BelongsTo
    {
        return $this->belongsTo(RdcProvince::class, 'province_id');
    }

    public function communes(): HasMany
    {
        return $this->hasMany(RdcCommune::class, 'city_id')->orderBy('name');
    }
}
