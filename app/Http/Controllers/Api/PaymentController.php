<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

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
            'amount'               => 'required|numeric|min:0',
            'currency'             => 'required|in:CDF,USD',
            'type'                 => 'required|in:tuition,registration,exam,uniform,transport,meal,other',
            'payment_method'       => 'required|in:mobile_money,cash,bank_transfer,check',
            'due_date'             => 'required|date',
            'status'               => 'sometimes|in:pending,completed,failed,refunded',
            'mobile_money_provider'=> 'nullable|string',
            'transaction_id'       => 'nullable|string',
            'reference'            => 'nullable|string',
            'description'          => 'nullable|string',
            'paid_at'              => 'nullable|date',
        ]);

        $payment = Payment::create($validated);
        return response()->json($payment->load('student'), 201);
    }

    public function show(string $id)
    {
        return response()->json(Payment::with('student')->findOrFail($id));
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
