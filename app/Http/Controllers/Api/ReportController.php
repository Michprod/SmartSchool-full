<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Dashboard statistics – matches DashboardStats frontend interface.
     */
    public function stats()
    {
        $totalStudents  = Student::where('is_active', true)->count();
        $totalTeachers  = User::where('role', 'teacher')->where('is_active', true)->count();
        $totalParents   = User::where('role', 'parent')->where('is_active', true)->count();
        $pendingAdmissions = Admission::where('status', 'submitted')->count();

        $revenueByStatus = fn(string $currency) =>
            Payment::where('currency', $currency)
                   ->where('status', 'completed')
                   ->sum('amount');

        $pendingByStatus = fn(string $currency) =>
            Payment::where('currency', $currency)
                   ->where('status', 'pending')
                   ->sum('amount');

        $recentActivities = Payment::with('student')
            ->where('status', 'completed')
            ->orderByDesc('paid_at')
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'type'        => 'payment',
                'description' => "Paiement de {$p->amount} {$p->currency} pour {$p->student?->first_name} {$p->student?->last_name}",
                'timestamp'   => $p->paid_at,
            ]);

        $mobileMoney = fn(string $currency) => Payment::where('currency', $currency)->where('payment_method', 'mobile_money')->where('status', 'completed')->sum('amount');
        $cash = fn(string $currency) => Payment::where('currency', $currency)->whereIn('payment_method', ['cash', 'check'])->where('status', 'completed')->sum('amount');

        return response()->json([
            'totalStudents'       => $totalStudents,
            'totalTeachers'       => $totalTeachers,
            'totalParents'        => $totalParents,
            'pendingApplications' => $pendingAdmissions,
            'finance' => [
                'totalRevenue'        => ['cdf' => $revenueByStatus('CDF'), 'usd' => $revenueByStatus('USD')],
                'pendingPayments'     => ['cdf' => $pendingByStatus('CDF'), 'usd' => $pendingByStatus('USD')],
                'by_method'           => [
                    'mobile_money' => ['cdf' => $mobileMoney('CDF'), 'usd' => $mobileMoney('USD')],
                    'cash'         => ['cdf' => $cash('CDF'), 'usd' => $cash('USD')]
                ]
            ],
            'recentActivities'    => $recentActivities,
        ]);
    }
}
