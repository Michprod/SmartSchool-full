<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel_config_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32);
            $table->string('label');
            $table->string('code', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['type', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_config_items');
    }
};
