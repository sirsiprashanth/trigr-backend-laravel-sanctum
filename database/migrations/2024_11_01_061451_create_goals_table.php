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
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coaching_plan_id')->nullable();
            $table->unsignedBigInteger('focus_area_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('sequence')->default(1); // To track if it's the 1st, 2nd, or 3rd goal
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('coaching_plan_id')
                ->references('id')
                ->on('coaching_plans')
                ->onDelete('set null');

            $table->foreign('focus_area_id')
                ->references('id')
                ->on('focus_areas')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
