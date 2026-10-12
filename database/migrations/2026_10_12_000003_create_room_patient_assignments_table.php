<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_patient_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('patient_name');
            $table->string('mrn', 50)->nullable()->index();
            $table->string('care_class', 50)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable()->index();
            $table->timestamps();

            $table->index(['room_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_patient_assignments');
    }
};
