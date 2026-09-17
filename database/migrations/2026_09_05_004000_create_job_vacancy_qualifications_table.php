<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('job_vacancy_qualifications')) {
            Schema::create('job_vacancy_qualifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('job_vacancy_id');
                $table->text('qualification_text');
                $table->string('qualification_type', 30)->default('auto');
                $table->string('requirement_level', 30)->default('required');
                $table->decimal('minimum_value', 8, 2)->nullable();
                $table->string('minimum_unit', 20)->nullable();
                $table->string('evidence_source', 40)->default('auto');
                $table->string('importance', 20)->default('high');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('job_vacancy_id', 'jvq_vacancy_fk')
                    ->references('id')->on('job_vacancies')->cascadeOnDelete();
                $table->index(['job_vacancy_id', 'is_active'], 'jvq_vacancy_active_idx');
            });
        }

        // Backfill existing free-text qualifications so every current vacancy
        // immediately participates in qualification matching without data loss.
        if (Schema::hasTable('job_vacancies') && Schema::hasTable('job_vacancy_qualifications')) {
            DB::table('job_vacancies')->orderBy('id')->each(function ($vacancy) {
                $exists = DB::table('job_vacancy_qualifications')->where('job_vacancy_id', $vacancy->id)->exists();
                if ($exists) return;

                $lines = preg_split('/\r\n|\r|\n|;/', (string) ($vacancy->qualifications ?? '')) ?: [];
                $lines = array_values(array_filter(array_map(fn ($x) => trim(preg_replace('/^[\-•*\s]+/u', '', (string) $x)), $lines)));
                foreach ($lines as $i => $line) {
                    DB::table('job_vacancy_qualifications')->insert([
                        'job_vacancy_id' => $vacancy->id,
                        'qualification_text' => $line,
                        'qualification_type' => 'auto',
                        'requirement_level' => 'required',
                        'minimum_value' => null,
                        'minimum_unit' => null,
                        'evidence_source' => 'auto',
                        'importance' => 'high',
                        'sort_order' => $i + 1,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_vacancy_qualifications');
    }
};
