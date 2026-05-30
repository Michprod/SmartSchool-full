<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeRate;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\PaymentPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentPlan::with(['student.schoolClass', 'feeType', 'installments']);
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->integer('student_id'));
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'fee_rate_id' => 'nullable|exists:fee_rates,id',
            'installment_type_id' => 'nullable|exists:installment_types,id',
            'academic_year' => 'required|string|max:20',
            'currency' => 'required|in:CDF,USD',
            'total_amount' => 'nullable|numeric|min:0',
            'installments' => 'nullable|array|min:1',
            'installments.*.amount_due' => 'required_with:installments|numeric|min:0',
            'installments.*.due_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $plan = DB::transaction(function () use ($validated) {
            $totalAmount = $validated['total_amount'] ?? null;
            if ($totalAmount === null && ! empty($validated['fee_rate_id'])) {
                $totalAmount = (float) FeeRate::findOrFail($validated['fee_rate_id'])->amount;
            }
            if ($totalAmount === null && ! empty($validated['installments'])) {
                $totalAmount = collect($validated['installments'])->sum('amount_due');
            }
            $totalAmount = $totalAmount ?? 0;

            $plan = PaymentPlan::create([
                'student_id' => $validated['student_id'],
                'fee_type_id' => $validated['fee_type_id'],
                'fee_rate_id' => $validated['fee_rate_id'] ?? null,
                'installment_type_id' => $validated['installment_type_id'] ?? null,
                'academic_year' => $validated['academic_year'],
                'currency' => $validated['currency'],
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'status' => 'pending',
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (! empty($validated['installments'])) {
                foreach ($validated['installments'] as $index => $item) {
                    PaymentInstallment::create([
                        'payment_plan_id' => $plan->id,
                        'installment_number' => $index + 1,
                        'amount_due' => $item['amount_due'],
                        'amount_paid' => 0,
                        'status' => 'pending',
                        'due_date' => $item['due_date'] ?? null,
                    ]);
                }
            } else {
                PaymentInstallment::create([
                    'payment_plan_id' => $plan->id,
                    'installment_number' => 1,
                    'amount_due' => $plan->total_amount,
                    'amount_paid' => 0,
                    'status' => 'pending',
                    'due_date' => $plan->end_date,
                ]);
            }

            return $plan;
        });

        return response()->json($plan->load(['student.schoolClass', 'feeType', 'installments']), 201);
    }

    public function payInstallment(Request $request, int $installmentId)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:mobile_money,cash,bank_transfer,check',
            'description' => 'nullable|string',
        ]);

        $installment = PaymentInstallment::with('paymentPlan')->findOrFail($installmentId);

        $payment = DB::transaction(function () use ($validated, $installment) {
            $payment = Payment::create([
                'student_id' => $installment->paymentPlan->student_id,
                'payment_plan_id' => $installment->payment_plan_id,
                'payment_installment_id' => $installment->id,
                'amount' => $validated['amount'],
                'currency' => $installment->paymentPlan->currency,
                'type' => $installment->paymentPlan->feeType?->code ?? 'other',
                'payment_method' => $validated['payment_method'],
                'due_date' => now()->toDateString(),
                'status' => 'completed',
                'description' => $validated['description'] ?? null,
                'paid_at' => now(),
            ]);

            $installment->amount_paid = (float) $installment->amount_paid + (float) $validated['amount'];
            $installment->status = $installment->amount_paid >= $installment->amount_due ? 'completed' : 'partial';
            if ($installment->status === 'completed') {
                $installment->paid_at = now();
            }
            $installment->save();

            $plan = $installment->paymentPlan;
            $plan->paid_amount = (float) $plan->installments()->sum('amount_paid');
            $plan->status = $plan->paid_amount >= $plan->total_amount
                ? 'completed'
                : ($plan->paid_amount > 0 ? 'partial' : 'pending');
            $plan->save();

            return $payment;
        });

        return response()->json($payment->load(['student', 'paymentPlan', 'paymentInstallment']));
    }
}
