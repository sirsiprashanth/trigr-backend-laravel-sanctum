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
        Schema::create('strategies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goal_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('sequence')->default(1); // To maintain order of strategies
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
        Schema::dropIfExists('strategies');
    }
};
