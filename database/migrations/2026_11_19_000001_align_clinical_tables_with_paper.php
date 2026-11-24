<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Adds what the research paper needs on top of the existing clinical tables. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_histories', function (Blueprint $table) {
            $table->string('category', 30)->nullable()->after('patient_id');
            $table->foreignId('flagged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('flagged_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('dental_histories', function (Blueprint $table) {
            $table->string('category', 30)->nullable()->after('patient_id');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // Visit history on the patient dashboard shows the dentist.
        Schema::table('treatments', function (Blueprint $table) {
            $table->foreignId('dentist_id')->nullable()->constrained('users')->nullOnDelete();
        });

        // Patient health data is encrypted at rest (RA 10173). Ciphertext is text, not JSON.
        // NOTE: any old plain-JSON prediction rows can no longer be read: use migrate:fresh in development.
        Schema::table('predictions', function (Blueprint $table) {
            $table->text('input_data')->change();
        });

        // The dentist's own conclusion when overriding (needed for Task 4 monitoring).
        Schema::table('prediction_decisions', function (Blueprint $table) {
            $table->string('final_condition', 60)->nullable()->after('decision');
        });

        // One decision per prediction (was: one per prediction per dentist, silently overwritten).
        // Add the new unique index first so the foreign key always has an index.
        Schema::table('prediction_decisions', function (Blueprint $table) {
            $table->unique('prediction_id');
        });
        Schema::table('prediction_decisions', function (Blueprint $table) {
            $table->dropUnique(['prediction_id', 'dentist_id']);
        });
    }

    public function down(): void
    {
        Schema::table('prediction_decisions', function (Blueprint $table) {
            $table->unique(['prediction_id', 'dentist_id']);
        });
        Schema::table('prediction_decisions', function (Blueprint $table) {
            $table->dropUnique(['prediction_id']);
            $table->dropColumn('final_condition');
        });
        Schema::table('treatments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dentist_id');
        });
        Schema::table('dental_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn('category');
        });
        Schema::table('medical_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('flagged_by');
            $table->dropColumn(['flagged_at', 'category']);
        });
    }
};
