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
        Schema::create('landing_groups', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name'); // e.g. Testing Services
            $blueprint->string('sub_label')->nullable(); // e.g. (Bahar ke Mulk)
            $blueprint->string('icon')->nullable(); // Material Symbols icon
            $blueprint->integer('sort_order')->default(0);
            $blueprint->boolean('is_active')->default(true);
            $blueprint->string('section_type')->default('grid'); // grid, strip, industry
            $blueprint->timestamps();
        });

        Schema::table('landing_links', function (Blueprint $table) {
            $table->foreignId('landing_group_id')->nullable()->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landing_links', function (Blueprint $table) {
            $table->dropForeign(['landing_group_id']);
            $table->dropColumn('landing_group_id');
        });
        Schema::dropIfExists('landing_groups');
    }
};
