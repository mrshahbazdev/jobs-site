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
        Schema::table('subscribers', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->unique(['email_or_whatsapp', 'category_id', 'city_id'], 'subscriber_pref_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            //
        });
    }
};
