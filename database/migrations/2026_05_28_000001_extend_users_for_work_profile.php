<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('has_professional_profile')->default(false)->after('department');
            $table->unsignedSmallInteger('workload_hours')->nullable()->after('has_professional_profile');
            $table->string('job_grade')->nullable()->after('workload_hours');
            $table->string('job_title')->nullable()->after('job_grade');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'has_professional_profile',
                'workload_hours',
                'job_grade',
                'job_title',
            ]);
        });
    }
};
