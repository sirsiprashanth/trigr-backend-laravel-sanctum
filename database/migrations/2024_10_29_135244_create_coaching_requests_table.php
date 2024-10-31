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
        Schema::create('coaching_requests', function (Blueprint $table) {
            $table->id();
            $table->string('topic');
            $table->date('preferred_date');
            $table->time('preferred_time');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('coach_id')->nullable();
            $table->enum('status', ['pending', 'accepted', 'cancelled']);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('coach_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coaching_requests');
    }
};
