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
            $blueprint->string('label');
            $blueprint->string('group_name'); // Testing Services, Overseas, Departments, Education, Industry, QuickLink
            $blueprint->string('route_name'); // jobs.testing_service, jobs.country, etc.
            $blueprint->string('route_param')->nullable(); // NTS, Saudi Arabia, etc.
            $blueprint->string('icon')->nullable(); // Material symbol name
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
