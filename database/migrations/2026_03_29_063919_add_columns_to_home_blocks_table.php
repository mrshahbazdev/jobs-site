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
        Schema::table('home_blocks', function (Blueprint $table) {
            $table->string('heading_text')->nullable();
            $table->text('sub_text')->nullable();
            $table->integer('job_count')->nullable();
            $table->boolean('show_sidebar')->default(true);
            $table->string('variant')->nullable();
            $table->string('icon')->nullable();
            $table->json('cards')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_blocks', function (Blueprint $table) {
            $table->dropColumn(['heading_text', 'sub_text', 'job_count', 'show_sidebar', 'variant', 'icon', 'cards']);
        });
    }
};
