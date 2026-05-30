<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('education_cycle_id')->constrained('education_cycles')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('official_name');
            $table->string('legacy_name')->nullable();
            $table->string('degree_group')->nullable();
            $table->string('exam_label')->nullable();
            $table->unsignedTinyInteger('typical_age')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_levels');
    }
};
