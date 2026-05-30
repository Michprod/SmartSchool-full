<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'student_id', 'payment_plan_id', 'payment_installment_id', 'amount', 'currency', 'type', 'status',
        'payment_method', 'mobile_money_provider', 'transaction_id',
        'reference', 'description', 'due_date', 'paid_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function student() { return $this->belongsTo(Student::class); }
    public function paymentPlan() { return $this->belongsTo(PaymentPlan::class); }
    public function paymentInstallment() { return $this->belongsTo(PaymentInstallment::class); }
}
