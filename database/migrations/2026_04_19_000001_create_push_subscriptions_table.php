<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->text('endpoint');
            $table->string('endpoint_hash', 64)->unique();
            $table->string('p256dh');
            $table->string('auth');
            $table->string('content_encoding')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('subscriber_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamps();

            $table->index('user_id');
            $table->index('subscriber_id');
            $table->index(['category_id', 'city_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
