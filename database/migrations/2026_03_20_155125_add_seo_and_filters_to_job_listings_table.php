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
            $table->string('meta_description')->nullable()->after('description_html');
            $table->text('meta_keywords')->nullable()->after('meta_description');
            $table->string('experience')->nullable()->after('deadline');
            $table->string('job_type')->nullable()->after('experience');
            $table->boolean('is_premium')->default(false)->after('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            //
        });
    }
};
