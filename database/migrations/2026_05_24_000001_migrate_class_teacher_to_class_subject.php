<?php

use App\Models\ClassSubject;
use App\Models\Subject;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Migre les données legacy class_teacher vers class_subject puis supprime la table obsolète.
     */
    public function up(): void
    {
        if (! Schema::hasTable('class_teacher')) {
            return;
        }

        if (Schema::hasTable('subjects') && Schema::hasTable('class_subject')) {
            $this->migratePivotData();
        }

        Schema::dropIfExists('class_teacher');
    }

    public function down(): void
    {
        Schema::create('class_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('school_classes')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('subject')->nullable();
            $table->string('academic_year')->nullable();
            $table->json('schedule')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['class_id', 'teacher_id', 'subject', 'academic_year'], 'unique_class_teacher_subject_year');
        });
    }

    private function migratePivotData(): void
    {
        $rows = DB::table('class_teacher')->get();

        foreach ($rows as $row) {
            if (empty($row->subject)) {
                continue;
            }

            $subject = Subject::firstOrCreate(
                ['name' => $row->subject],
                [
                    'code' => $this->uniqueSubjectCode($row->subject),
                    'type' => 'core',
                ]
            );

            $academicYear = $row->academic_year ?? '2025-2026';

            ClassSubject::firstOrCreate(
                [
                    'class_id' => $row->class_id,
                    'subject_id' => $subject->id,
                    'academic_year' => $academicYear,
                ],
                [
                    'teacher_id' => $row->teacher_id,
                    'coefficient' => 1,
                    'hours_per_week' => 2,
                    'is_active' => (bool) $row->is_active,
                ]
            );
        }
    }

    private function uniqueSubjectCode(string $name): string
    {
        $base = strtoupper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', Str::slug($name, '')), 0, 4));
        $base = $base !== '' ? $base : 'SUBJ';
        $code = $base;
        $suffix = 1;

        while (Subject::where('code', $code)->exists()) {
            $code = $base . $suffix;
            $suffix++;
        }

        return $code;
    }
};
