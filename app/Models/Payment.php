<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'student_id', 'amount', 'currency', 'type', 'status',
        'payment_method', 'mobile_money_provider', 'transaction_id',
        'reference', 'description', 'due_date', 'paid_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function student() { return $this->belongsTo(Student::class); }
}
