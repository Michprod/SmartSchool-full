<?php

use App\Models\SchoolClass;
use App\Support\ClassNameBuilder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SchoolClass::with(['gradeLevel.educationCycle', 'studyOption'])
            ->chunkById(100, function ($classes) {
                foreach ($classes as $class) {
                    if (! $class->gradeLevel) {
                        continue;
                    }

                    $displayName = ClassNameBuilder::build(
                        $class->gradeLevel,
                        $class->studyOption,
                        $class->section ?: 'A'
                    );

                    $class->forceFill([
                        'display_name' => $displayName,
                        'name' => $displayName,
                    ])->save();
                }
            });
    }

    public function down(): void
    {
        // No-op: descriptive normalization should not be reverted.
    }
};
