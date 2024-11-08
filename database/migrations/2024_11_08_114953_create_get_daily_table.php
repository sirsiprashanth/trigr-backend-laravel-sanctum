<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGetDailyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('get_daily', function (Blueprint $table) {
            $table->id();
            $table->string('reference_id');
            $table->uuid('user_id');
            $table->float('distance_in_meters')->nullable();
            $table->integer('swimming_strokes')->nullable();
            $table->integer('steps')->nullable();
            $table->float('burned_calories')->nullable();
            $table->float('net_activity_calories')->nullable();
            $table->float('BMR_calories')->nullable();
            $table->integer('max_hr_bpm')->nullable();
            $table->integer('min_hr_bpm')->nullable();
            $table->integer('avg_hr_bpm')->nullable();
            $table->integer('active_duration_in_sec')->nullable();
            $table->float('avg_saturation_percentage')->nullable();
            $table->float('avg_stress_level')->nullable();
            $table->json('scores')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('get_daily');
    }
}
