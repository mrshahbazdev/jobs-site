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
        Schema::create('home_blocks', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('type'); // hero_cards, category_grids, featured_jobs, latest_jobs, whatsapp_cta, newsletter, heading
            $blueprint->string('title')->nullable();
            $blueprint->json('settings')->nullable();
            $blueprint->integer('sort_order')->default(0);
            $blueprint->boolean('is_active')->default(true);
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_blocks');
    }
};
