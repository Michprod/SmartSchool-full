<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentInstallment extends Model
{
    protected $fillable = [
        'payment_plan_id',
        'installment_number',
        'amount_due',
        'amount_paid',
        'status',
        'due_date',
        'paid_at',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function paymentPlan()
    {
        return $this->belongsTo(PaymentPlan::class);
    }
}
