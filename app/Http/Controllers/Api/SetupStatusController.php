<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EducationCycle;
use App\Models\FeeRate;
use App\Models\FeeType;
use App\Models\Personnel;
use App\Models\RdcProvince;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Subject;
use Illuminate\Http\Request;

class SetupStatusController extends Controller
{
    public function status(Request $request)
    {
        $hasSettings = Setting::where('key', 'school_settings')->exists();
        $teacherCount = Personnel::where('staff_type', 'teacher')->where('is_active', true)->count();

        $checks = [
            ['key' => 'roles', 'label' => 'Rôles RBAC', 'ok' => Role::count() >= 5],
            ['key' => 'school_settings', 'label' => 'Paramètres école', 'ok' => $hasSettings],
            ['key' => 'education_catalog', 'label' => 'Catalogue RDC', 'ok' => EducationCycle::count() >= 4],
            ['key' => 'geo', 'label' => 'Géographie', 'ok' => RdcProvince::count() >= 1],
            ['key' => 'fee_types', 'label' => 'Types de frais', 'ok' => FeeType::where('is_active', true)->count() >= 1],
            ['key' => 'fee_rates', 'label' => 'Barèmes finance', 'ok' => FeeRate::where('is_active', true)->count() >= 1],
            ['key' => 'subjects', 'label' => 'Matières', 'ok' => Subject::count() >= 1],
            ['key' => 'classes', 'label' => 'Classes', 'ok' => SchoolClass::count() >= 1],
            ['key' => 'personnel', 'label' => 'Personnel enseignant', 'ok' => $teacherCount >= 1],
        ];

        $ready = collect($checks)->every(fn ($c) => $c['ok']);

        return response()->json([
            'ready' => $ready,
            'checks' => $checks,
        ]);
    }
}
