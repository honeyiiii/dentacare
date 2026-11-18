<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tooth_charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('tooth_number');
            $table->string('condition');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['patient_id', 'tooth_number']);
        });

        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tooth_chart_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dentist_id')->constrained('users')->restrictOnDelete();
            $table->string('diagnosis');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tooth_chart_id')->nullable()->constrained()->nullOnDelete();
            $table->string('procedure');
            $table->string('status')->default('planned');
            $table->date('treatment_date')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('progress_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_id')->constrained()->cascadeOnDelete();
            $table->text('note');
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_notes');
        Schema::dropIfExists('treatments');
        Schema::dropIfExists('diagnoses');
        Schema::dropIfExists('tooth_charts');
    }
};
