<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assessment_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->nullable()->constrained('positions')->cascadeOnDelete();
            $table->foreignId('job_vacancy_id')->nullable()->constrained('job_vacancies')->cascadeOnDelete();
            $table->string('name');
            $table->string('profile_type', 30)->default('vacancy'); // position_default | vacancy
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['position_id', 'profile_type'], 'assessment_profiles_position_type_index');
            $table->unique('job_vacancy_id');
        });

        Schema::create('assessment_profile_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_profile_id')->constrained('assessment_profiles')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('criterion_key', 150);
            $table->unsignedTinyInteger('weight')->default(10);
            $table->string('importance', 20)->default('medium');
            $table->unsignedTinyInteger('minimum_score')->nullable();
            $table->boolean('is_required')->default(false);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['assessment_profile_id', 'criterion_key'], 'assessment_profile_criterion_key_unique');
        });

        Schema::create('assessment_field_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_profile_criterion_id');
            $table->foreign('assessment_profile_criterion_id', 'afm_criterion_fk')->references('id')->on('assessment_profile_criteria')->cascadeOnDelete();
            $table->foreignId('form_field_id')->constrained('form_fields')->cascadeOnDelete();
            $table->string('scoring_method', 30)->default('auto'); // auto | text_rubric | exact_choice | presence
            $table->unsignedTinyInteger('max_score')->default(10);
            $table->text('rubric')->nullable();
            $table->json('answer_key')->nullable();
            $table->boolean('is_knockout')->default(false);
            $table->timestamps();
            $table->unique(['assessment_profile_criterion_id', 'form_field_id'], 'assessment_mapping_unique');
        });

        Schema::create('job_vacancy_form_template', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_vacancy_id')->constrained('job_vacancies')->cascadeOnDelete();
            $table->foreignId('form_template_id')->constrained('form_templates')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            $table->unique(['job_vacancy_id', 'form_template_id'], 'vacancy_template_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_vacancy_form_template');
        Schema::dropIfExists('assessment_field_mappings');
        Schema::dropIfExists('assessment_profile_criteria');
        Schema::dropIfExists('assessment_profiles');
    }
};
