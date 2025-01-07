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
        Schema::create('vital_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->float('pulse_rate')->nullable();
            $table->float('mean_rri')->nullable();
            $table->float('oxygen_saturation')->nullable();
            $table->float('respiration_rate')->nullable();
            $table->float('stress_level')->nullable();
            $table->float('sdnn')->nullable();
            $table->float('rmssd')->nullable();
            $table->float('stress_index')->nullable();
            $table->string('blood_pressure')->nullable();
            $table->float('lfhf')->nullable();
            $table->float('pns_index')->nullable();
            $table->integer('pns_zone')->nullable();
            $table->float('prq')->nullable();
            $table->float('sd1')->nullable();
            $table->float('sd2')->nullable();
            $table->float('sns_index')->nullable();
            $table->integer('sns_zone')->nullable();
            $table->float('wellness_index')->nullable();
            $table->integer('wellness_level')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vital_scans');
    }
};
