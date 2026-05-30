<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Étend les périodes (S1/S2 secondaire) et types d'évaluation (travail hebdomadaire, concours).
 * Compatible SQLite : recréation des tables concernées.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['assessments', 'student_averages', 'report_cards'] as $table) {
            if (Schema::hasTable("{$table}_legacy")) {
                Schema::dropIfExists($table);
            }
        }

        $this->rebuildAssessments();
        $this->rebuildStudentAverages();
        $this->rebuildReportCards();
    }

    public function down(): void
    {
        // Pas de retour arrière automatique (données S1/S2 seraient perdues).
    }

    private function rebuildAssessments(): void
    {
        if (! Schema::hasTable('assessments')) {
            return;
        }

        Schema::rename('assessments', 'assessments_legacy');

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('school_classes')->onDelete('cascade');
            $table->string('type', 32)->default('devoir');
            $table->string('term', 3)->default('T1');
            $table->string('academic_year');
            $table->decimal('score', 5, 2);
            $table->decimal('max_score', 5, 2)->default(20);
            $table->decimal('coefficient', 3, 1)->default(1);
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->date('date');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->index(['student_id', 'subject_id', 'term', 'academic_year'], 'idx_assessments_calc_v2');
            $table->index(['teacher_id', 'class_id'], 'idx_assessments_teacher_v2');
        });

        DB::table('assessments')->insertUsing(
            [
                'id', 'student_id', 'subject_id', 'teacher_id', 'class_id', 'type', 'term',
                'academic_year', 'score', 'max_score', 'coefficient', 'title', 'comment',
                'date', 'is_published', 'created_at', 'updated_at',
            ],
            DB::table('assessments_legacy')->select([
                'id', 'student_id', 'subject_id', 'teacher_id', 'class_id', 'type', 'term',
                'academic_year', 'score', 'max_score', 'coefficient', 'title', 'comment',
                'date', 'is_published', 'created_at', 'updated_at',
            ])
        );

        Schema::drop('assessments_legacy');
    }

    private function rebuildStudentAverages(): void
    {
        if (! Schema::hasTable('student_averages')) {
            return;
        }

        Schema::rename('student_averages', 'student_averages_legacy');

        Schema::create('student_averages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('school_classes')->onDelete('cascade');
            $table->string('term', 3)->default('T1');
            $table->string('academic_year');
            $table->decimal('average_score', 5, 2);
            $table->integer('total_coefficient')->default(0);
            $table->integer('assessments_count')->default(0);
            $table->decimal('general_average', 5, 2)->nullable();
            $table->integer('class_rank')->nullable();
            $table->integer('total_students')->nullable();
            $table->string('appreciation')->nullable();
            $table->text('teacher_comment')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();
            $table->unique(['student_id', 'subject_id', 'term', 'academic_year'], 'unique_student_avg_v2');
            $table->index(['class_id', 'term', 'academic_year', 'general_average'], 'idx_student_avg_class_v2');
        });

        DB::table('student_averages')->insertUsing(
            [
                'id', 'student_id', 'subject_id', 'class_id', 'term', 'academic_year',
                'average_score', 'total_coefficient', 'assessments_count', 'general_average',
                'class_rank', 'total_students', 'appreciation', 'teacher_comment',
                'calculated_at', 'created_at', 'updated_at',
            ],
            DB::table('student_averages_legacy')->select([
                'id', 'student_id', 'subject_id', 'class_id', 'term', 'academic_year',
                'average_score', 'total_coefficient', 'assessments_count', 'general_average',
                'class_rank', 'total_students', 'appreciation', 'teacher_comment',
                'calculated_at', 'created_at', 'updated_at',
            ])
        );

        Schema::drop('student_averages_legacy');
    }

    private function rebuildReportCards(): void
    {
        if (! Schema::hasTable('report_cards')) {
            return;
        }

        Schema::rename('report_cards', 'report_cards_legacy');

        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('school_classes')->onDelete('cascade');
            $table->string('term', 3)->default('T1');
            $table->string('academic_year');
            $table->decimal('general_average', 5, 2);
            $table->integer('class_rank');
            $table->integer('total_students');
            $table->enum('decision', ['pass', 'conditional_pass', 'fail', 'exclude'])->nullable();
            $table->text('class_council_observation')->nullable();
            $table->string('work_recommendations')->nullable();
            $table->string('behavior_recommendations')->nullable();
            $table->string('pdf_path')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('generated_at');
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->timestamp('validated_at')->nullable();
            $table->boolean('is_validated')->default(false);
            $table->timestamps();
            $table->unique(['student_id', 'term', 'academic_year'], 'unique_report_card_v2');
            $table->index(['class_id', 'term', 'academic_year'], 'idx_report_cards_class_v2');
        });

        DB::table('report_cards')->insertUsing(
            [
                'id', 'student_id', 'class_id', 'term', 'academic_year', 'general_average',
                'class_rank', 'total_students', 'decision', 'class_council_observation',
                'work_recommendations', 'behavior_recommendations', 'pdf_path', 'is_published',
                'published_at', 'generated_by', 'generated_at', 'validated_by', 'validated_at',
                'is_validated', 'created_at', 'updated_at',
            ],
            DB::table('report_cards_legacy')->select([
                'id', 'student_id', 'class_id', 'term', 'academic_year', 'general_average',
                'class_rank', 'total_students', 'decision', 'class_council_observation',
                'work_recommendations', 'behavior_recommendations', 'pdf_path', 'is_published',
                'published_at', 'generated_by', 'generated_at', 'validated_by', 'validated_at',
                'is_validated', 'created_at', 'updated_at',
            ])
        );

        Schema::drop('report_cards_legacy');
    }
};
