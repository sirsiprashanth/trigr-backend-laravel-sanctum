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
        Schema::create('action_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goal_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->integer('sequence')->default(1); // To maintain order of action plans
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('goal_id')
                ->references('id')
                ->on('goals')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_plans');
    }
};
