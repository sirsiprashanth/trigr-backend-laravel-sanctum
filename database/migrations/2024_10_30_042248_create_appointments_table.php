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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('topic')->nullable();
            $table->text('description')->nullable();
            $table->string('meeting_link')->nullable();
            $table->date('preferred_date')->nullable();
            $table->time('preferred_time')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('created_for_user_id')->nullable();
            $table->enum('status', ['scheduled', 'pending', 'confirmed', 'cancelled', 'completed', 'no show', 'rescheduled'])->nullable();
            $table->unsignedBigInteger('coaching_plan_id')->nullable();
            $table->timestamps();

            $table->foreign('created_by_user_id')->references('id')->on('users');
            $table->foreign('created_for_user_id')->references('id')->on('users');
            $table->foreign('coaching_plan_id')->references('id')->on('coaching_plans');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
