<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('model_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->string('algorithm');
            $table->string('artifact_path');
            $table->json('metadata')->nullable();
            $table->timestamp('trained_at')->nullable();
            $table->timestamps();
        });

        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->json('input_data');
            $table->string('result');
            $table->json('risk_scores')->nullable();
            $table->foreignId('model_version_id')->nullable()->constrained()->nullOnDelete();
            $table->string('model_version')->nullable();
            $table->string('label')->default('supporting information only');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('prediction_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prediction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dentist_id')->constrained('users')->restrictOnDelete();
            $table->string('decision');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['prediction_id', 'dentist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prediction_decisions');
        Schema::dropIfExists('predictions');
        Schema::dropIfExists('model_versions');
    }
};
