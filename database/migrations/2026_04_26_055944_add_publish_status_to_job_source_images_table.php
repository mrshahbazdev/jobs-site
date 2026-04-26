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
        Schema::table('job_source_images', function (Blueprint $table) {
            $table->string('publish_status')->default('pending')->after('is_processed');
            $table->unsignedBigInteger('published_job_id')->nullable()->after('publish_status');
            $table->timestamp('published_at')->nullable()->after('published_job_id');
        });
    }

    public function down(): void
    {
        Schema::table('job_source_images', function (Blueprint $table) {
            $table->dropColumn(['publish_status', 'published_job_id', 'published_at']);
        });
    }
};
