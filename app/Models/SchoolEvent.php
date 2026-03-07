<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolEvent extends Model
{
    protected $fillable = ['title', 'description', 'date', 'location', 'organizer', 'media'];

    protected $casts = ['date' => 'datetime', 'media' => 'array'];
}
