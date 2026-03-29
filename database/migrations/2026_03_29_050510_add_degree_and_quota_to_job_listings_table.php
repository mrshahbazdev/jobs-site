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
            $table->string('qualification_degree')->nullable()->index();
            $table->boolean('is_special_quota')->default(false)->index();
            $table->boolean('is_minority_quota')->default(false)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropColumn(['qualification_degree', 'is_special_quota', 'is_minority_quota']);
        });
    }
};
