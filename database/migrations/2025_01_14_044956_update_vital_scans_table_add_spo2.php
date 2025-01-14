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
        Schema::table('vital_scans', function (Blueprint $table) {
            // Add spo2 column
            $table->float('spo2')->nullable()->after('oxygen_saturation');
            
            // Modify blood_pressure to be JSON
            $table->json('blood_pressure')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vital_scans', function (Blueprint $table) {
            $table->dropColumn('spo2');
            $table->string('blood_pressure')->nullable()->change();
        });
    }
};
