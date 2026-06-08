<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TimetableService;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    public function __construct(
        protected TimetableService $timetable
    ) {}

    public function conflicts(Request $request)
    {
        $year = $request->string('academic_year')->toString() ?: null;

        return response()->json([
            'academic_year' => $year,
            'conflicts' => $this->timetable->detectConflicts($year),
        ]);
    }
}
