<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RdcCommune extends Model
{
    protected $table = 'rdc_communes';

    protected $fillable = ['city_id', 'name'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(RdcCity::class, 'city_id');
    }
}
