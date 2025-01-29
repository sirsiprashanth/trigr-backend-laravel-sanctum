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
        Schema::table('dubai_event_scans', function (Blueprint $table) {
            $table->float('pulse_rate')->nullable();
            $table->float('mean_rri')->nullable();
            $table->float('spo2')->nullable();
            $table->float('respiration_rate')->nullable();
            $table->float('stress_level')->nullable();
            $table->float('sdnn')->nullable();
            $table->float('rmssd')->nullable();
            $table->float('stress_index')->nullable();
            $table->json('blood_pressure')->nullable();
            $table->float('lfhf')->nullable();
            $table->float('pns_index')->nullable();
            $table->string('pns_zone')->nullable();
            $table->float('prq')->nullable();
            $table->float('sd1')->nullable();
            $table->float('sd2')->nullable();
            $table->float('sns_index')->nullable();
            $table->string('sns_zone')->nullable();
            $table->float('wellness_index')->nullable();
            $table->string('wellness_level')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dubai_event_scans', function (Blueprint $table) {
            $table->dropColumn([
                'pulse_rate',
                'mean_rri',
                'spo2',
                'respiration_rate',
                'stress_level',
                'sdnn',
                'rmssd',
                'stress_index',
                'blood_pressure',
                'lfhf',
                'pns_index',
                'pns_zone',
                'prq',
                'sd1',
                'sd2',
                'sns_index',
                'sns_zone',
                'wellness_index',
                'wellness_level'
            ]);
        });
    }
};
