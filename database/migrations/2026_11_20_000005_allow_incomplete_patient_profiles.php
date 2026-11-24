<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->change();
            $table->string('sex')->nullable()->change();
            $table->string('contact_number')->nullable()->change();
            $table->string('address')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('patients')->whereNull('date_of_birth')
            ->orWhereNull('sex')
            ->orWhereNull('contact_number')
            ->orWhereNull('address')
            ->exists()) {
            throw new RuntimeException('Complete all patient profiles before rolling back this migration.');
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable(false)->change();
            $table->string('sex')->nullable(false)->change();
            $table->string('contact_number')->nullable(false)->change();
            $table->string('address')->nullable(false)->change();
        });
    }
};
