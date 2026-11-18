<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['medical_histories', 'dental_histories'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
                $table->text('details');
                $table->boolean('is_alert')->default(false);
                $table->string('alert_note')->nullable();
                $table->timestamps();
                $table->index(['patient_id', 'is_alert']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_histories');
        Schema::dropIfExists('medical_histories');
    }
};
