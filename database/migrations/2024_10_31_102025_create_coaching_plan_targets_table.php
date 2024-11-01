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
        Schema::create('coaching_plan_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coaching_plan_id');
            $table->unsignedBigInteger('focus_area_id')->nullable();
            $table->unsignedBigInteger('goal_id')->nullable();
            $table->unsignedBigInteger('strategy_id')->nullable();
            $table->unsignedBigInteger('action_plan_id')->nullable();
            $table->timestamps();

            $table->foreign('coaching_plan_id')->references('id')->on('coaching_plans');
            $table->foreign('focus_area_id')->references('id')->on('focus_areas');
            $table->foreign('goal_id')->references('id')->on('goals');
            $table->foreign('strategy_id')->references('id')->on('strategies');
            $table->foreign('action_plan_id')->references('id')->on('action_plans');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coaching_plan_targets');
    }
};
