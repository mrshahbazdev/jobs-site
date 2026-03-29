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
        Schema::create('landing_links', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('landing_group_id')->constrained()->cascadeOnDelete();
            $blueprint->string('title');
            $blueprint->string('url')->nullable();
            $blueprint->string('route_name')->nullable();
            $blueprint->string('route_param')->nullable();
            $blueprint->string('icon')->nullable(); 
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
        Schema::dropIfExists('landing_links');
    }
};
