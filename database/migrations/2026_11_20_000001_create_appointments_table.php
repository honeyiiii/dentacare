<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('dentist_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('booked_by')->constrained('users')->restrictOnDelete();
            $table->string('service', 120);
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->string('status', 20)->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'appointment_date']);
            $table->index(['dentist_id', 'appointment_date']);
        });

        // A separate claim row enforces uniqueness only while an appointment is active.
        // Cancellation releases the slot without deleting the appointment or its audit trail.
        Schema::create('appointment_slot_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('dentist_id')->constrained('users')->restrictOnDelete();
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->timestamps();

            $table->unique(['dentist_id', 'appointment_date', 'appointment_time'], 'appointment_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_slot_claims');
        Schema::dropIfExists('appointments');
    }
};
