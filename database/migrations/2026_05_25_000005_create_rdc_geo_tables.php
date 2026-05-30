<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rdc_provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 16)->nullable();
            $table->timestamps();
        });

        Schema::create('rdc_cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained('rdc_provinces')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['province_id', 'name']);
        });

        Schema::create('rdc_communes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('rdc_cities')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['city_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rdc_communes');
        Schema::dropIfExists('rdc_cities');
        Schema::dropIfExists('rdc_provinces');
    }
};
