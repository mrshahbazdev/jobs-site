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
            $table->boolean('has_walkin_interview')->default(false)->index();
            $table->boolean('is_remote')->default(false)->index();
            $table->boolean('is_whatsapp_apply')->default(false)->index();
            $table->boolean('is_retired_army')->default(false)->index();
            $table->boolean('is_student_friendly')->default(false)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropColumn(['has_walkin_interview', 'is_remote', 'is_whatsapp_apply', 'is_retired_army', 'is_student_friendly']);
        });
    }
};
