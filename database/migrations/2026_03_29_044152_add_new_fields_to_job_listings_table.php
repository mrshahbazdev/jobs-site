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
        Schema::table('job_listings', function (Blueprint $table) {
            $table->string('whatsapp_number')->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_logo')->nullable();
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_number', 'company_name', 'company_logo', 'salary_min', 'salary_max']);
        });
    }
};
