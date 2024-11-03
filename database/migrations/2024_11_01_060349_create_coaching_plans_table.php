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
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('coach_id')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('contract_terms');
            $table->decimal('price', 10, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'in_progress', 'completed'])->default('draft');
            $table->timestamps();
            $table->softDeletes();

            // Add foreign key constraints after declaring nullable columns
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('coach_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
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
