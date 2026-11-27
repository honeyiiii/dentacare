<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queue_entries', function (Blueprint $table) {
            $table->unique('appointment_id');
            $table->unique(['queue_date', 'position']);
        });
    }

    public function down(): void
    {
        Schema::table('queue_entries', function (Blueprint $table) {
            $table->dropUnique(['appointment_id']);
            $table->dropUnique(['queue_date', 'position']);
        });
    }
};
