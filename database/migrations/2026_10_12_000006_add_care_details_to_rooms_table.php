<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('bed', 30)->nullable()->after('care_class');
            $table->string('doctor_name')->nullable()->after('bed');
            $table->string('bg_path')->nullable()->after('logo_path');
            $table->string('visit_session_1', 50)->nullable()->after('visit_schedule');
            $table->string('visit_session_2', 50)->nullable()->after('visit_session_1');
            $table->text('visit_note')->nullable()->after('visit_session_2');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn([
                'bed', 'doctor_name', 'bg_path',
                'visit_session_1', 'visit_session_2', 'visit_note',
            ]);
        });
    }
};
