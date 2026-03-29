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
            $table->string('list_source')->nullable()->after('type');
            $table->string('display_type')->default('list')->after('list_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_blocks', function (Blueprint $table) {
            $table->dropColumn(['list_source', 'display_type']);
        });
    }
};
