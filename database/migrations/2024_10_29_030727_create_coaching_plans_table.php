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
        Schema::create('coaching_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('contract_terms');
            $table->decimal('price', 8, 2);
            $table->enum('status', ['pending', 'in progress', 'completed', 'cancelled']);
            $table->unsignedBigInteger('coach_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('coach_id')->references('id')->on('users');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coaching_plans');
    }
};
