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
            $table->string('name')->nullable()->change();
            $table->text('description')->nullable()->change();
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
            $table->text('contract_terms')->nullable()->change();
            $table->decimal('price', 8, 2)->nullable()->change();
            $table->enum('status', ['pending', 'in progress', 'completed', 'cancelled'])->nullable()->change();
            $table->unsignedBigInteger('coach_id')->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coaching_plans', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->text('description')->nullable(false)->change();
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
            $table->text('contract_terms')->nullable(false)->change();
            $table->decimal('price', 8, 2)->nullable(false)->change();
            $table->enum('status', ['pending', 'in progress', 'completed', 'cancelled'])->nullable(false)->change();
            $table->unsignedBigInteger('coach_id')->nullable(false)->change();
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
