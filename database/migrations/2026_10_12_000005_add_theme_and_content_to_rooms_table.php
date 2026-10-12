<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('theme_mode', 10)->default('auto')->after('theme');
            $table->string('light_start', 5)->default('06:00')->after('theme_mode');
            $table->string('dark_start', 5)->default('18:00')->after('light_start');
            $table->string('timezone', 50)->default('Asia/Jakarta')->after('dark_start');
            $table->text('health_message')->nullable()->after('timezone');
            $table->text('visit_schedule')->nullable()->after('health_message');
            $table->text('announcement')->nullable()->after('visit_schedule');
            $table->unsignedSmallInteger('rotation_seconds')->default(20)->after('announcement');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn([
                'theme_mode', 'light_start', 'dark_start', 'timezone',
                'health_message', 'visit_schedule', 'announcement', 'rotation_seconds',
            ]);
        });
    }
};
