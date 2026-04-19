<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('seeker')->after('password')->index();
            $table->string('phone', 32)->nullable()->after('role');
            $table->string('cv_file_path')->nullable()->after('phone');
            $table->unsignedTinyInteger('profile_completion_percent')->default(0)->after('cv_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'cv_file_path', 'profile_completion_percent']);
        });
    }
};
