<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table des matières scolaires
     */
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nom de la matière (ex: Mathématiques)
            $table->string('code')->unique(); // Code (ex: MATH, FRAN, HIST)
            $table->text('description')->nullable();
            $table->enum('type', ['core', 'elective'])->default('core'); // core=fondamental, elective=optionnel
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
