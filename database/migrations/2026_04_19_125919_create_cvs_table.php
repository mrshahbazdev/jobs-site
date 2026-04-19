<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('share_uuid')->unique();
            $table->string('title')->default('Untitled CV');
            $table->string('template', 32)->default('modern');
            $table->string('theme_color', 16)->default('#004b93');
            $table->string('font_family', 32)->default('Inter');

            $table->json('personal')->nullable();
            $table->text('summary')->nullable();
            $table->json('experience')->nullable();
            $table->json('education')->nullable();
            $table->json('skills')->nullable();
            $table->json('languages')->nullable();
            $table->json('certifications')->nullable();
            $table->json('projects')->nullable();
            $table->json('references_list')->nullable();
            $table->json('section_order')->nullable();

            $table->boolean('is_public')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cvs');
    }
};
