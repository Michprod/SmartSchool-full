<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_subject', function (Blueprint $table) {
            if (! Schema::hasColumn('class_subject', 'schedule')) {
                $table->json('schedule')->nullable()->after('hours_per_week');
            }
        });
    }

    public function down(): void
    {
        Schema::table('class_subject', function (Blueprint $table) {
            if (Schema::hasColumn('class_subject', 'schedule')) {
                $table->dropColumn('schedule');
            }
        });
    }
};
