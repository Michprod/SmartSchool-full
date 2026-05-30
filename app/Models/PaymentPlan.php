<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentPlan extends Model
{
    protected $fillable = [
        'student_id',
        'fee_type_id',
        'fee_rate_id',
        'installment_type_id',
        'academic_year',
        'currency',
        'total_amount',
        'paid_amount',
        'status',
        'start_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

    public function installments()
    {
        return $this->hasMany(PaymentInstallment::class)->orderBy('installment_number');
    }
}
