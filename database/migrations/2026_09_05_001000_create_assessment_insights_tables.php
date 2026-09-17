<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assessment_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_vacancy_id')->constrained('job_vacancies')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('weight')->default(10);
            $table->json('field_keywords')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained('applications')->cascadeOnDelete();
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->string('fit_label')->default('Needs Review');
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->json('category_scores')->nullable();
            $table->json('strengths')->nullable();
            $table->json('gaps')->nullable();
            $table->json('interview_focus')->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_results');
        Schema::dropIfExists('assessment_criteria');
    }
};
