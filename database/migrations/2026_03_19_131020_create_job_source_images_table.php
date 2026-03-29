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
        Schema::create('job_source_images', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('source_page_url')->unique();
            $table->string('source_image_url');
            $table->string('local_image_path')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->text('article_text')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_source_images');
    }
};
