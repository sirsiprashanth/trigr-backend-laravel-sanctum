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
        Schema::table('users', function (Blueprint $table) {
            $table->integer('experience_years')->nullable();
            $table->text('brief_bio')->nullable();
            $table->integer('clients_coached')->nullable();
            $table->float('rating')->nullable();
            $table->text('client_reviews')->nullable();
            $table->string('photo')->nullable();
            $table->text('additional_info')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('experience_years');
            $table->dropColumn('brief_bio');
            $table->dropColumn('clients_coached');
            $table->dropColumn('rating');
            $table->dropColumn('client_reviews');
            $table->dropColumn('photo');
            $table->dropColumn('additional_info');
        });
    }
};
