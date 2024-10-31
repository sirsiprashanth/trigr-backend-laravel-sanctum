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
        Schema::table('coaching_plans', function (Blueprint $table) {
            $table->unsignedBigInteger('focus_area_id')->nullable();
            $table->unsignedBigInteger('goal_id')->nullable();
            $table->unsignedBigInteger('strategy_id')->nullable();
            $table->unsignedBigInteger('action_plan_id')->nullable();

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
        Schema::table('coaching_plans', function (Blueprint $table) {
            Schema::table('coaching_plans', function (Blueprint $table) {
                $table->dropForeign(['focus_area_id']);
                $table->dropForeign(['goal_id']);
                $table->dropForeign(['strategy_id']);
                $table->dropForeign(['action_plan_id']);

                $table->dropColumn('focus_area_id');
                $table->dropColumn('goal_id');
                $table->dropColumn('strategy_id');
                $table->dropColumn('action_plan_id');
            });
        });
    }
};
