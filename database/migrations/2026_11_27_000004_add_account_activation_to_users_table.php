<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->index();
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty: is_active was created by an earlier migration,
        // so this one must not drop it on rollback.
    }
};