<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('applicants') || !Schema::hasColumn('applicants', 'user_id')) {
            return;
        }

        Schema::table('applicants', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE applicants MODIFY user_id BIGINT UNSIGNED NULL');

        Schema::table('applicants', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Public applicants may not have user accounts, so this migration is intentionally irreversible.
    }
};
