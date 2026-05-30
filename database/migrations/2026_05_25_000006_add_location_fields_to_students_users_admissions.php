<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('province_id')->nullable()->after('province')->constrained('rdc_provinces')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('city')->constrained('rdc_cities')->nullOnDelete();
            $table->foreignId('commune_id')->nullable()->after('city_id')->constrained('rdc_communes')->nullOnDelete();
            $table->string('quartier')->nullable()->after('commune_id');
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'commune_id')) {
                $table->foreignId('province_id')->nullable()->after('province')->constrained('rdc_provinces')->nullOnDelete();
                $table->foreignId('city_id')->nullable()->after('city')->constrained('rdc_cities')->nullOnDelete();
                $table->foreignId('commune_id')->nullable()->after('city_id')->constrained('rdc_communes')->nullOnDelete();
                $table->string('quartier')->nullable()->after('commune_id');
            }
        });

        Schema::table('admissions', function (Blueprint $table) {
            $table->foreignId('applied_class_id')->nullable()->after('applied_class')->constrained('school_classes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('applied_class_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('province_id');
            $table->dropConstrainedForeignId('city_id');
            $table->dropConstrainedForeignId('commune_id');
            $table->dropColumn('quartier');
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'commune_id')) {
                $table->dropConstrainedForeignId('province_id');
                $table->dropConstrainedForeignId('city_id');
                $table->dropConstrainedForeignId('commune_id');
                $table->dropColumn('quartier');
            }
        });
    }
};
