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
            $table->string('sub_sector')->nullable()->index();
            $table->string('contract_type')->nullable()->index();
            $table->text('skills')->nullable();
            $table->boolean('has_accommodation')->default(false)->index();
            $table->boolean('has_transport')->default(false)->index();
            $table->boolean('has_medical_insurance')->default(false)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropColumn(['sub_sector', 'contract_type', 'skills', 'has_accommodation', 'has_transport', 'has_medical_insurance']);
        });
    }
};
