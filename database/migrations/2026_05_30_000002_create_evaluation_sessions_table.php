<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('evaluation_sessions')) {
            Schema::create('evaluation_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_id')->constrained('school_classes')->onDelete('cascade');
                $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
                $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
                $table->string('type', 32);
                $table->string('term', 3);
                $table->string('academic_year');
                $table->string('title');
                $table->date('date');
                $table->decimal('max_score', 5, 2)->default(20);
                $table->decimal('coefficient', 3, 1)->default(1);
                $table->boolean('is_published')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->timestamps();

                $table->index(['class_id', 'subject_id', 'term', 'academic_year'], 'idx_eval_sessions_class');
            });
        }

        if (Schema::hasTable('assessments') && ! Schema::hasColumn('assessments', 'evaluation_session_id')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->foreignId('evaluation_session_id')
                    ->nullable()
                    ->after('class_id')
                    ->constrained('evaluation_sessions')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (Schema::hasColumn('assessments', 'evaluation_session_id')) {
                $table->dropConstrainedForeignId('evaluation_session_id');
            }
        });

        Schema::dropIfExists('evaluation_sessions');
    }
};
