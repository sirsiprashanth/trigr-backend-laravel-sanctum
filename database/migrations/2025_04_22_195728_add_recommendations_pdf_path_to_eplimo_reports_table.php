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
        Schema::table('eplimo_reports', function (Blueprint $table) {
            $table->string('recommendations_pdf_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eplimo_reports', function (Blueprint $table) {
            $table->dropColumn('recommendations_pdf_path');
        });
    }
};
