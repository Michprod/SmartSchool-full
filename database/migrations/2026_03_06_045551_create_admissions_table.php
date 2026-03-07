<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            // Infos étudiant
            $table->string('student_first_name');
            $table->string('student_last_name');
            $table->date('student_date_of_birth');
            $table->enum('student_gender', ['M', 'F']);
            // Infos parent
            $table->string('parent_first_name');
            $table->string('parent_last_name');
            $table->string('parent_email');
            $table->string('parent_phone');
            // Demande
            $table->string('applied_class');
            $table->enum('status', ['submitted', 'under_review', 'accepted', 'rejected'])->default('submitted');
            $table->json('documents')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
