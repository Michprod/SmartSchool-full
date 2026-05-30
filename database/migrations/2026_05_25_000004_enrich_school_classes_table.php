<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->foreignId('grade_level_id')->nullable()->after('id')->constrained('grade_levels')->nullOnDelete();
            $table->foreignId('study_option_id')->nullable()->after('grade_level_id')->constrained('study_options')->nullOnDelete();
            $table->string('section', 8)->default('A')->after('study_option_id');
            $table->string('academic_year', 16)->default('2025-2026')->after('section');
            $table->string('display_name')->nullable()->after('name');
        });

        Schema::table('school_classes', function (Blueprint $table) {
            $table->unique(
                ['grade_level_id', 'study_option_id', 'section', 'academic_year'],
                'school_classes_level_option_section_year_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropUnique('school_classes_level_option_section_year_unique');
            $table->dropConstrainedForeignId('grade_level_id');
            $table->dropConstrainedForeignId('study_option_id');
            $table->dropColumn(['section', 'academic_year', 'display_name']);
        });
    }
};
