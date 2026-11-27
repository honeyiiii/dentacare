<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('assigned_dentist_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('avatar_path')->nullable();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable();
            $table->string('phone', 30)->nullable();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dentist_id')->constrained('users')->restrictOnDelete();
            $table->string('service', 100);
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->string('status', 20)->default('Booked');
            $table->string('slot_key')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'appointment_date', 'status']);
            $table->index(['dentist_id', 'appointment_date']);
        });

        Schema::create('queue_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dentist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('patient_name', 100);
            $table->string('service', 100);
            $table->date('queue_date')->index();
            $table->unsignedInteger('position');
            $table->string('status', 20)->default('Waiting');
            $table->string('chair_key')->nullable()->unique();
            $table->timestamps();
            $table->index(['queue_date', 'position']);
        });

        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 20)->default('email');
            $table->string('status', 20)->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['appointment_id', 'channel']);
            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('symptom_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->json('symptoms');
            $table->json('conditions');
            $table->string('area', 100)->nullable();
            $table->unsignedTinyInteger('pain_level');
            $table->string('duration', 30);
            $table->string('urgency', 20);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'created_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('subject_type', 80)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['actor_id', 'created_at']);
        });

        Schema::create('privacy_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('request_type', 30);
            $table->text('message')->nullable();
            $table->string('status', 20)->default('Open');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_requests');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('symptom_checks');
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('queue_entries');
        Schema::dropIfExists('appointments');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_path', 'phone']);
        });
        Schema::table('patients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_dentist_id');
        });
    }
};
