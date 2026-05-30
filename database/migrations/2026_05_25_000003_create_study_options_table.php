<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('education_cycle_id')->constrained('education_cycles')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->default('generale');
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_options');
    }
};
