<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with('student');

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return response()->json($query->orderByDesc('created_at')->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id'           => 'required|exists:students,id',
            'payment_plan_id'      => 'nullable|exists:payment_plans,id',
            'payment_installment_id' => 'nullable|exists:payment_installments,id',
            'amount'               => 'required|numeric|min:0',
            'currency'             => 'required|in:CDF,USD',
            'type'                 => 'required|string|max:64',
            'payment_method'       => 'required|in:mobile_money,cash,bank_transfer,check',
            'due_date'             => 'required|date',
            'status'               => 'sometimes|in:pending,completed,failed,refunded',
            'mobile_money_provider'=> 'nullable|string',
            'transaction_id'       => 'nullable|string',
            'reference'            => 'nullable|string',
            'description'          => 'nullable|string',
            'paid_at'              => 'nullable|date',
        ]);

        $payment = DB::transaction(function () use ($validated) {
            $payment = Payment::create($validated);

            if (! empty($validated['payment_installment_id']) && $payment->status === 'completed') {
                $installment = PaymentInstallment::with('paymentPlan')->findOrFail($validated['payment_installment_id']);
                $installment->amount_paid = (float) $installment->amount_paid + (float) $payment->amount;
                $installment->status = $installment->amount_paid >= $installment->amount_due ? 'completed' : 'partial';
                if ($installment->status === 'completed') {
                    $installment->paid_at = now();
                }
                $installment->save();

                $plan = $installment->paymentPlan;
                if ($plan) {
                    $plan->paid_amount = (float) $plan->installments()->sum('amount_paid');
                    $plan->status = $plan->paid_amount >= $plan->total_amount
                        ? 'completed'
                        : ($plan->paid_amount > 0 ? 'partial' : 'pending');
                    $plan->save();
                }
            }

            return $payment;
        });

        return response()->json($payment->load(['student', 'paymentPlan', 'paymentInstallment']), 201);
    }

    public function show(string $id)
    {
        return response()->json(Payment::with(['student', 'paymentPlan', 'paymentInstallment'])->findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $payment = Payment::findOrFail($id);
        $validated = $request->validate([
            'amount'         => 'numeric|min:0',
            'status'         => 'in:pending,completed,failed,refunded',
            'transaction_id' => 'nullable|string',
            'paid_at'        => 'nullable|date',
        ]);
        $payment->update($validated);
        return response()->json($payment);
    }

    public function destroy(string $id)
    {
        Payment::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
